<?php

namespace App\Services;

use App\Models\Player;
use App\Models\WeightCategory;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SimpleXMLElement;
use ZipArchive;

class PlayerListImportService
{
    public function analyze(UploadedFile $file, ?int $fallbackCategoryId = null): array
    {
        $rows = $this->readRows($file);
        $normalizedRows = $this->normalizeRows($rows);

        $players = Player::query()
            ->select('id', 'full_name', 'email', 'club', 'gender', 'birthday')
            ->get();

        $playersById = $players->keyBy('id');
        $playersByEmail = $players->filter(fn (Player $p) => filled($p->email))
            ->keyBy(fn (Player $p) => Str::lower(trim((string) $p->email)));
        $playersByName = collect();
        foreach ($players as $player) {
            foreach ($this->buildNameKeys((string) $player->full_name) as $nameKey) {
                $bucket = $playersByName->get($nameKey, collect());
                $bucket->push($player);
                $playersByName->put($nameKey, $bucket->unique('id')->values());
            }
        }

        $categories = WeightCategory::query()
            ->with('ageCategory:id,name,min_age,max_age')
            ->select('id', 'name', 'gender', 'age_category_id', 'min_weight', 'max_weight')
            ->get();

        $categoriesById = $categories->keyBy('id');
        $categoriesByName = $categories->groupBy(fn (WeightCategory $c) => $this->normalizeString($c->name));

        $fallbackCategory = $fallbackCategoryId ? $categoriesById->get($fallbackCategoryId) : null;

        $registrations = [];
        $rowResults = [];

        foreach ($normalizedRows as $index => $row) {
            $sourceRow = $index + 1;
            $player = $this->resolvePlayer($row, $playersById, $playersByEmail, $playersByName);

            if (!$player) {
                $rowResults[] = [
                    'row' => $sourceRow,
                    'status' => 'unmatched_player',
                    'player' => $row['full_name'] ?? $row['email'] ?? $row['player_id'] ?? '(unknown)',
                    'category' => $row['category'] ?? $row['uweight'] ?? $row['weight'] ?? $row['weight_category_id'] ?? null,
                    'reason' => 'Player not found in database.',
                ];
                continue;
            }

            [$categoryId, $categoryReason] = $this->resolveCategoryId(
                $row,
                $player,
                $categoriesById,
                $categoriesByName,
                $categories,
                $fallbackCategory
            );

            if (!$categoryId) {
                $rowResults[] = [
                    'row' => $sourceRow,
                    'status' => 'unresolved_category',
                    'player' => $player->full_name,
                    'category' => $row['category'] ?? $row['uweight'] ?? $row['weight'] ?? $row['weight_category_id'] ?? null,
                    'reason' => $categoryReason ?? 'Weight category could not be resolved.',
                ];
                continue;
            }

            $key = $player->id.'-'.$categoryId;

            if (isset($registrations[$key])) {
                $rowResults[] = [
                    'row' => $sourceRow,
                    'status' => 'duplicate',
                    'player' => $player->full_name,
                    'category' => $categoriesById->get($categoryId)?->name,
                    'reason' => 'Duplicate player/category pair skipped.',
                ];
                continue;
            }

            $registrations[$key] = [
                'player_id' => $player->id,
                'tournament_weight_category_id' => $categoryId,
                'player_name' => $player->full_name,
                'category_name' => $categoriesById->get($categoryId)?->name ?? (string) $categoryId,
            ];

            $rowResults[] = [
                'row' => $sourceRow,
                'status' => 'matched',
                'player' => $player->full_name,
                'category' => $categoriesById->get($categoryId)?->name,
                'reason' => 'Matched and queued for registration.',
            ];
        }

        return [
            'total_rows' => count($normalizedRows),
            'matched_count' => count($registrations),
            'unmatched_player_count' => collect($rowResults)->where('status', 'unmatched_player')->count(),
            'unresolved_category_count' => collect($rowResults)->where('status', 'unresolved_category')->count(),
            'duplicate_count' => collect($rowResults)->where('status', 'duplicate')->count(),
            'registrations' => array_values($registrations),
            'rows' => $rowResults,
        ];
    }

    private function readRows(UploadedFile $file): array
    {
        $ext = Str::lower((string) $file->getClientOriginalExtension());

        return match ($ext) {
            'csv' => $this->readCsvRows($file->getRealPath()),
            'xlsx' => $this->readXlsxRows($file->getRealPath()),
            'docx' => $this->readDocxRows($file->getRealPath()),
            default => [],
        };
    }

    private function readCsvRows(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if (!$handle) {
            return $rows;
        }

        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = array_map(
                fn ($value) => is_string($value) ? trim($value) : (string) $value,
                $data
            );
        }

        fclose($handle);

        return $rows;
    }

    private function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sharedStrings = $this->parseSharedStrings($sharedXml);
        }

        $sheetPath = $this->resolveFirstWorksheetPath($zip);
        $sheetXml = $sheetPath ? $zip->getFromName($sheetPath) : false;
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        return $this->parseSheetRows($sheetXml, $sharedStrings);
    }

    private function readDocxRows(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return [];
        }

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($documentXml === false) {
            return [];
        }

        $dom = new DOMDocument();
        if (!@$dom->loadXML($documentXml, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET)) {
            return [];
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $rows = [];
        $tableRows = $xpath->query('//w:tbl/w:tr');
        if ($tableRows !== false) {
            foreach ($tableRows as $tableRow) {
                $cells = [];
                $cellNodes = $xpath->query('./w:tc', $tableRow);
                if ($cellNodes === false) {
                    continue;
                }

                foreach ($cellNodes as $cellNode) {
                    $cells[] = trim($this->extractWordText($xpath, $cellNode));
                }

                if ($this->isNonEmptyRow($cells)) {
                    $rows[] = $cells;
                }
            }
        }

        if (!empty($rows)) {
            return $rows;
        }

        $paragraphs = $xpath->query('//w:p');
        if ($paragraphs !== false) {
            foreach ($paragraphs as $paragraph) {
                $text = trim($this->extractWordText($xpath, $paragraph));
                if ($text !== '') {
                    $rows[] = [$text];
                }
            }
        }

        return $rows;
    }

    private function extractWordText(DOMXPath $xpath, DOMNode $node): string
    {
        $parts = [];
        $textNodes = $xpath->query('.//w:t', $node);

        if ($textNodes === false) {
            return '';
        }

        foreach ($textNodes as $textNode) {
            $parts[] = $textNode->textContent ?? '';
        }

        return implode(' ', $parts);
    }

    private function parseSharedStrings(string $xml): array
    {
        $shared = [];
        $sx = @simplexml_load_string($xml);
        if (!$sx) {
            return $shared;
        }

        foreach ($this->safeXPath($sx, '//x:si', 'x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main') as $si) {
            $parts = $this->safeXPath($si, './/x:t', 'x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $shared[] = trim(implode('', array_map(fn ($node) => (string) $node, $parts)));
        }

        return $shared;
    }

    private function resolveFirstWorksheetPath(ZipArchive $zip): ?string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($relsXml);

        if (!$workbook || !$rels) {
            return 'xl/worksheets/sheet1.xml';
        }

        $firstSheet = ($this->safeXPath(
            $workbook,
            '//x:sheets/x:sheet[1]',
            'x',
            'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
        ) ?: [])[0] ?? null;
        if (!$firstSheet) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationshipId = (string) ($firstSheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'] ?? '');
        if ($relationshipId === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        foreach ($this->safeXPath($rels, '//r:Relationship', 'r', 'http://schemas.openxmlformats.org/package/2006/relationships') as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = (string) $relationship['Target'];
            if ($target === '') {
                break;
            }

            if (str_starts_with($target, '/')) {
                return ltrim($target, '/');
            }

            return 'xl/'.ltrim($target, '/');
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function parseSheetRows(string $sheetXml, array $sharedStrings): array
    {
        $sx = @simplexml_load_string($sheetXml);
        if (!$sx) {
            return [];
        }

        $rows = [];

        foreach ($this->safeXPath($sx, '//x:sheetData/x:row', 'x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main') as $rowNode) {
            $row = [];

            foreach ($this->safeXPath($rowNode, './x:c', 'x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main') as $cell) {
                $reference = (string) $cell['r'];
                preg_match('/([A-Z]+)/', $reference, $matches);
                $columnLetters = $matches[1] ?? 'A';
                $index = $this->columnLettersToIndex($columnLetters);

                $type = (string) $cell['t'];
                $value = '';

                if ($type === 's') {
                    $sharedIndex = (int) ($cell->v ?? 0);
                    $value = $sharedStrings[$sharedIndex] ?? '';
                } elseif ($type === 'inlineStr') {
                    $parts = $this->safeXPath($cell, './/x:t', 'x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                    $value = implode('', array_map(fn ($node) => (string) $node, $parts));
                } else {
                    $value = (string) ($cell->v ?? '');
                }

                $row[$index] = trim($value);
            }

            if (empty($row)) {
                continue;
            }

            ksort($row);
            $filled = [];
            $max = max(array_keys($row));
            for ($i = 1; $i <= $max; $i++) {
                $filled[] = $row[$i] ?? '';
            }

            if ($this->isNonEmptyRow($filled)) {
                $rows[] = $filled;
            }
        }

        return $rows;
    }

    private function safeXPath(SimpleXMLElement $element, string $path, ?string $prefix = null, ?string $namespace = null): array
    {
        if ($prefix && $namespace) {
            $element->registerXPathNamespace($prefix, $namespace);
        }

        $result = @$element->xpath($path);
        if (($result === false || $result === []) && $prefix) {
            $fallbackPath = str_replace($prefix.':', '', $path);
            $result = @$element->xpath($fallbackPath);
        }

        return $result ?: [];
    }

    private function normalizeRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $headerIndex = $this->detectHeaderRow($rows);
        if ($headerIndex === null) {
            return collect($rows)
                ->filter(fn (array $row) => $this->isNonEmptyRow($row))
                ->map(fn (array $row) => $this->mapHeaderlessRow($row))
                ->filter(fn (array $row) => filled($row['full_name']))
                ->reject(fn (array $row) => $this->isIgnorableMetaRow($row))
                ->values()
                ->all();
        }

        $headers = array_map(fn ($h) => $this->normalizeHeader((string) $h), $rows[$headerIndex]);

        return collect(array_slice($rows, $headerIndex + 1))
            ->filter(fn (array $row) => $this->isNonEmptyRow($row))
            ->map(function (array $row) use ($headers) {
                $mapped = [];
                foreach ($headers as $i => $header) {
                    if (!$header) {
                        continue;
                    }

                    $mapped[$header] = trim((string) ($row[$i] ?? ''));
                }

                if (!isset($mapped['full_name']) && isset($mapped['name'])) {
                    $mapped['full_name'] = $mapped['name'];
                }

                if (!isset($mapped['weight']) && isset($mapped['uweight'])) {
                    $mapped['weight'] = $mapped['uweight'];
                }

                return $mapped;
            })
            ->filter(function (array $row) {
                return filled($row['full_name'] ?? null)
                    || filled($row['email'] ?? null)
                    || filled($row['player_id'] ?? null);
            })
            ->reject(fn (array $row) => $this->isIgnorableMetaRow($row))
            ->values()
            ->all();
    }

    private function detectHeaderRow(array $rows): ?int
    {
        $candidates = ['full_name', 'name', 'player_id', 'email', 'category', 'weight_category_id', 'club', 'gender', 'age_category', 'weight', 'uweight'];

        foreach (array_slice($rows, 0, 5, true) as $index => $row) {
            $hits = 0;
            foreach ($row as $cell) {
                $header = $this->normalizeHeader((string) $cell);
                if (in_array($header, $candidates, true)) {
                    $hits++;
                }
            }

            if ($hits >= 2) {
                return $index;
            }
        }

        return null;
    }

    private function mapHeaderlessRow(array $row): array
    {
        $cells = array_values(array_map(fn ($value) => trim((string) $value), $row));

        if (count($cells) === 1) {
            $split = $this->splitWordLine((string) ($cells[0] ?? ''));
            if (count($split) > 1) {
                $cells = $split;
            }
        }

        // Expected format example:
        // [no, full_name, M/F, age_category, U50kg, ...]
        if (count($cells) >= 5 && is_numeric($cells[0]) && filled($cells[1])) {
            return [
                'full_name' => $cells[1],
                'gender' => $cells[2] ?? '',
                'age_category' => $cells[3] ?? '',
                'uweight' => $cells[4] ?? '',
                'weight' => $cells[4] ?? '',
            ];
        }

        // Fallback when first column is already the player name.
        if (count($cells) >= 4 && filled($cells[0])) {
            return [
                'full_name' => $cells[0],
                'gender' => $cells[1] ?? '',
                'age_category' => $cells[2] ?? '',
                'uweight' => $cells[3] ?? '',
                'weight' => $cells[3] ?? '',
            ];
        }

        return ['full_name' => $cells[0] ?? ''];
    }

    private function normalizeHeader(string $value): string
    {
        $key = $this->normalizeString($value);

        return match ($key) {
            'player id', 'playerid', 'id' => 'player_id',
            'email', 'e mail', 'email address' => 'email',
            'full name', 'fullname', 'player', 'player name', 'athlete', 'athlete name', 'participant', 'participant name', 'name' => 'full_name',
            'weight category id', 'category id', 'division id' => 'weight_category_id',
            'weight category', 'weight class', 'uweight class', 'u category', 'category', 'division' => 'category',
            'club', 'team', 'team name', 'teamname', 'school', 'affiliation' => 'club',
            'coach', 'coach name', 'trainer' => 'coach',
            'gender', 'sex' => 'gender',
            'age category', 'age group', 'age division', 'u age' => 'age_category',
            'weight', 'body weight', 'actual weight', 'weigh in', 'weigh in weight' => 'weight',
            'uweight', 'u weight', 'under weight', 'limit weight', 'entry weight' => 'uweight',
            default => '',
        };
    }

    private function resolvePlayer(
        array $row,
        Collection $playersById,
        Collection $playersByEmail,
        Collection $playersByName
    ): ?Player {
        $playerId = $row['player_id'] ?? null;
        if (filled($playerId) && is_numeric($playerId)) {
            $player = $playersById->get((int) $playerId);
            if ($player) {
                return $player;
            }
        }

        $email = $row['email'] ?? null;
        if (filled($email)) {
            $player = $playersByEmail->get(Str::lower(trim((string) $email)));
            if ($player) {
                return $player;
            }
        }

        $name = $row['full_name'] ?? $row['name'] ?? null;
        if (!filled($name)) {
            return null;
        }

        $matches = collect();
        foreach ($this->buildNameKeys((string) $name) as $nameKey) {
            $hits = $playersByName->get($nameKey, collect());
            if ($hits->isNotEmpty()) {
                $matches = $matches->merge($hits);
            }
        }
        $matches = $matches->unique('id')->values();

        if (!$matches || $matches->isEmpty()) {
            $tokenMatched = $this->findByTokenSimilarity((string) $name, $playersByName);
            if ($tokenMatched) {
                return $tokenMatched;
            }

            return null;
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        $club = $this->normalizeString((string) ($row['club'] ?? ''));
        if ($club !== '') {
            $clubMatched = $matches->first(fn (Player $p) => $this->normalizeString((string) $p->club) === $club);
            if ($clubMatched) {
                return $clubMatched;
            }
        }

        return null;
    }

    private function findByTokenSimilarity(string $name, Collection $playersByName): ?Player
    {
        $needleTokens = $this->nameTokens($name);
        if (count($needleTokens) < 2) {
            return null;
        }

        $candidates = $playersByName->flatten(1)->unique('id')->values();
        $best = null;
        $bestScore = 0.0;

        foreach ($candidates as $candidate) {
            $candidateTokens = $this->nameTokens((string) $candidate->full_name);
            if (empty($candidateTokens)) {
                continue;
            }

            $intersection = count(array_intersect($needleTokens, $candidateTokens));
            $denominator = max(count($needleTokens), count($candidateTokens));
            $score = $denominator > 0 ? $intersection / $denominator : 0.0;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $candidate;
            }
        }

        return $bestScore >= 0.75 ? $best : null;
    }

    private function resolveCategoryId(
        array $row,
        Player $player,
        Collection $categoriesById,
        Collection $categoriesByName,
        Collection $allCategories,
        ?WeightCategory $fallbackCategory
    ): array {
        $id = $row['weight_category_id'] ?? null;
        if (filled($id) && is_numeric($id) && $categoriesById->has((int) $id)) {
            return [(int) $id, null];
        }

        $gender = $this->normalizeGender($row['gender'] ?? $player->gender ?? null);
        $ageCategoryId = $this->resolveAgeCategoryIdFromRow($row['age_category'] ?? null, $allCategories, $player);
        $weightValue = $this->extractWeightValue($row['weight'] ?? $row['uweight'] ?? $row['category'] ?? null);

        $name = $row['category'] ?? null;
        if (filled($name)) {
            $matched = $categoriesByName->get($this->normalizeString((string) $name), collect());

            if ($matched->isEmpty()) {
                $uWeight = $this->extractWeightValue($name);
                if ($uWeight !== null && $gender !== '' && $ageCategoryId !== null) {
                    $fromUWeight = $allCategories->first(function (WeightCategory $category) use ($uWeight, $gender, $ageCategoryId) {
                        return $this->normalizeGender($category->gender) === $gender
                            && (int) $category->age_category_id === $ageCategoryId
                            && $uWeight >= (float) $category->min_weight
                            && $uWeight <= (float) $category->max_weight;
                    });

                    if ($fromUWeight) {
                        return [(int) $fromUWeight->id, null];
                    }
                }
            }

            if ($matched->count() === 1) {
                return [(int) $matched->first()->id, null];
            }

            if ($matched->count() > 1) {
                if ($ageCategoryId !== null) {
                    $ageFiltered = $matched->first(
                        fn (WeightCategory $category) => (int) $category->age_category_id === $ageCategoryId
                    );
                    if ($ageFiltered) {
                        return [(int) $ageFiltered->id, null];
                    }
                }

                if ($gender !== '') {
                    $genderMatch = $matched->first(
                        fn (WeightCategory $category) => $this->normalizeGender($category->gender) === $gender
                    );
                    if ($genderMatch) {
                        return [(int) $genderMatch->id, null];
                    }
                }

                return [null, 'Multiple categories matched this name. Add category ID or gender to disambiguate.'];
            }
        }

        if ($weightValue !== null && $gender !== '' && $ageCategoryId !== null) {
            $rangeMatch = $allCategories->first(function (WeightCategory $category) use ($weightValue, $gender, $ageCategoryId) {
                return $this->normalizeGender($category->gender) === $gender
                    && (int) $category->age_category_id === $ageCategoryId
                    && $weightValue >= (float) $category->min_weight
                    && $weightValue <= (float) $category->max_weight;
            });

            if ($rangeMatch) {
                return [(int) $rangeMatch->id, null];
            }
        }

        if ($fallbackCategory) {
            return [(int) $fallbackCategory->id, null];
        }

        return [null, 'No category match from gender/age category/weight, and no fallback category selected.'];
    }

    private function normalizeString(string $value): string
    {
        $value = Str::lower(trim(Str::ascii($value)));
        $value = preg_replace('/[^\pL\pN\s]/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        $value = str_replace(['-', '_'], ' ', $value);
        return trim($value);
    }

    private function buildNameKeys(string $name): array
    {
        $keys = [];
        $normalized = $this->normalizeString($name);
        if ($normalized !== '') {
            $keys[] = $normalized;
        }

        if (str_contains($name, ',')) {
            $parts = array_map('trim', explode(',', $name, 2));
            if (count($parts) === 2) {
                $swapped = $this->normalizeString($parts[1].' '.$parts[0]);
                if ($swapped !== '') {
                    $keys[] = $swapped;
                }
            }
        }

        $tokenKey = implode(' ', $this->nameTokens($name));
        if ($tokenKey !== '') {
            $keys[] = $tokenKey;
        }

        return array_values(array_unique($keys));
    }

    private function nameTokens(string $name): array
    {
        $normalized = $this->normalizeString($name);
        if ($normalized === '') {
            return [];
        }

        $tokens = array_filter(explode(' ', $normalized), function ($token) {
            return $token !== '' && !in_array($token, ['jr', 'sr', 'ii', 'iii', 'iv'], true);
        });

        sort($tokens);

        return array_values(array_unique($tokens));
    }

    private function normalizeGender(?string $value): string
    {
        $value = $this->normalizeString((string) $value);
        if ($value === 'm' || $value === 'male') {
            return 'male';
        }
        if ($value === 'f' || $value === 'female') {
            return 'female';
        }

        return '';
    }

    private function resolveAgeCategoryIdFromRow(?string $ageCategory, Collection $allCategories, ?Player $player = null): ?int
    {
        $ageCategories = $allCategories
            ->map(function (WeightCategory $category) {
                return $category->ageCategory;
            })
            ->filter()
            ->unique('id')
            ->values();

        $value = $this->normalizeString((string) $ageCategory);
        if ($value === '') {
            if ($player) {
                $age = $this->resolvePlayerAge($player);
                if ($age !== null) {
                    $ageMatched = $ageCategories->first(function ($category) use ($age) {
                        return $age >= (int) $category->min_age && $age <= (int) $category->max_age;
                    });
                    if ($ageMatched) {
                        return (int) $ageMatched->id;
                    }
                }
            }
            return null;
        }

        $exact = $ageCategories->first(function ($age) use ($value) {
            return $this->normalizeString((string) $age->name) === $value;
        });
        if ($exact) {
            return (int) $exact->id;
        }

        $contains = $ageCategories->first(function ($age) use ($value) {
            $name = $this->normalizeString((string) $age->name);
            return str_contains($name, $value) || str_contains($value, $name);
        });
        if ($contains) {
            return (int) $contains->id;
        }

        if (str_contains($value, 'junior')) {
            $junior = $ageCategories->first(function ($age) {
                return str_contains($this->normalizeString((string) $age->name), 'junior');
            });
            return $junior ? (int) $junior->id : null;
        }

        if (str_contains($value, 'senior')) {
            $senior = $ageCategories->first(function ($age) {
                return str_contains($this->normalizeString((string) $age->name), 'senior');
            });
            return $senior ? (int) $senior->id : null;
        }

        if (str_contains($value, 'cadet')) {
            $cadet = $ageCategories->first(function ($age) {
                return str_contains($this->normalizeString((string) $age->name), 'cadet');
            });
            return $cadet ? (int) $cadet->id : null;
        }

        if (str_contains($value, 'kid')) {
            $kids = $ageCategories->filter(function ($age) {
                return str_contains($this->normalizeString((string) $age->name), 'kid');
            })->values();

            if ($kids->isEmpty()) {
                return null;
            }

            $age = $this->resolvePlayerAge($player);
            if ($age !== null) {
                $ageMatched = $kids->first(function ($category) use ($age) {
                    return $age >= (int) $category->min_age && $age <= (int) $category->max_age;
                });
                if ($ageMatched) {
                    return (int) $ageMatched->id;
                }
            }

            if ($kids->count() === 1) {
                return (int) $kids->first()->id;
            }

            return null;
        }

        return null;
    }

    private function resolvePlayerAge(?Player $player): ?int
    {
        if (!$player || !$player->birthday) {
            return null;
        }

        return (int) $player->birthday->age;
    }

    private function extractWeightValue(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return (float) $raw;
        }

        if (preg_match('/(\d+(?:\.\d+)?)/', $raw, $match) === 1) {
            return (float) $match[1];
        }

        return null;
    }

    private function splitWordLine(string $line): array
    {
        $line = str_replace("\xC2\xA0", ' ', $line);
        $line = trim($line);
        if ($line === '') {
            return [];
        }

        if (str_contains($line, "\t")) {
            return array_values(array_map('trim', explode("\t", $line)));
        }

        $parts = preg_split('/\s{2,}/', $line) ?: [$line];
        return array_values(array_map('trim', $parts));
    }

    private function isIgnorableMetaRow(array $row): bool
    {
        $name = $this->normalizeString((string) ($row['full_name'] ?? ''));
        if ($name === '') {
            return true;
        }

        $ignoredStarts = ['team name', 'coach', 'athletes', 'athlete name', 'participant'];
        foreach ($ignoredStarts as $ignoredStart) {
            if (str_starts_with($name, $ignoredStart)) {
                return true;
            }
        }

        $weightValue = $this->extractWeightValue($row['weight'] ?? $row['uweight'] ?? $row['category'] ?? null);
        $gender = $this->normalizeGender($row['gender'] ?? null);
        $ageCategory = $this->normalizeString((string) ($row['age_category'] ?? ''));

        return $weightValue === null && $gender === '' && $ageCategory === '';
    }

    private function isNonEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return true;
            }
        }

        return false;
    }

    private function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $value = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $value = ($value * 26) + (ord($letters[$i]) - ord('A') + 1);
        }

        return max(1, $value);
    }
}
