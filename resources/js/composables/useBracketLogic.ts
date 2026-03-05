import { MatchItem, BracketItem } from '@/types/bracket'

/**
 * Bracket Logic Composable
 * 
 * Provides utility functions for parsing, formatting, and rendering bracket structures.
 * This logic is shared across the Bracket View and other bracket-related components.
 */
export function useBracketLogic() {
    /**
     * Safely retrieves the awards object from a bracket.
     * Returns a default empty structure if awards are missing.
     */
    const safeAwards = (bracket: BracketItem) => {
        return bracket.awards ?? { gold: null, silver: null, bronze: [] }
    }

    /**
     * Safely retrieves the matches array from a bracket.
     */
    const safeMatches = (bracket: BracketItem): MatchItem[] => {
        return bracket.matches ?? []
    }

    /**
     * Groups matches by round number.
     * @param matches List of matches to group
     * @returns Array of round objects, each containing its matches sorted by match number
     */
    const roundsFor = (matches: MatchItem[]) => {
        const buckets: Record<number, MatchItem[]> = {}

        matches.forEach((match) => {
            if (!buckets[match.round_number]) buckets[match.round_number] = []
            buckets[match.round_number].push(match)
        })

        return Object.entries(buckets)
            .map(([round, items]) => ({
                round: Number(round),
                matches: [...items].sort((a, b) => a.match_number - b.match_number),
            }))
            .sort((a, b) => a.round - b.round)
    }

    /**
     * Convenience function to get rounds directly from a bracket item.
     */
    const roundsForBracket = (bracket: BracketItem) => roundsFor(safeMatches(bracket))

    /**
     * Determines the final round number for a bracket.
     */
    const finalRoundNumber = (bracket: BracketItem) => {
        const rounds = roundsForBracket(bracket)
        return rounds.length ? Math.max(...rounds.map((r) => r.round)) : 1
    }

    /**
     * Calculates the bracket size (power of 2) based on entrant count or total rounds.
     */
    const bracketSize = (entrants: number | undefined, totalRounds: number) => {
        if (entrants && entrants > 0) {
            let size = 1
            while (size < entrants) size *= 2
            return size
        }
        return Math.pow(2, totalRounds)
    }

    /**
     * Generates a descriptive label for a round (e.g., "Quarterfinal", "Final").
     * 
     * @param totalRounds Total number of rounds in the bracket
     * @param roundNumber Current round number
     * @param entrants Number of entrants (used to calculate round of X)
     * @param format Bracket format (single_elimination, round_robin)
     */
    const roundLabel = (
        totalRounds: number,
        roundNumber: number,
        entrants?: number,
        format?: BracketItem['format']
    ) => {
        if (format !== 'single_elimination') {
            return `Round ${roundNumber}`
        }

        const size = bracketSize(entrants, totalRounds)
        const remaining = size / Math.pow(2, roundNumber - 1)
        if (remaining <= 2) return `Final (${remaining} -> ${remaining / 2})`
        if (remaining === 4) return 'Semifinal (4 -> 2)'
        if (remaining === 8) return 'Quarterfinal (8 -> 4)'
        if (remaining >= 16) return `Round of ${remaining} (${remaining} -> ${remaining / 2})`

        return `Round ${roundNumber}`
    }

    /**
     * Formats the bracket format key into a human-readable string.
     */
    const formatLabel = (format: BracketItem['format']) => {
        if (format === 'single_elimination') return 'Single Elimination'
        if (format === 'round_robin') return 'Round Robin'
        return 'Unknown'
    }

    /**
     * Calculates CSS styles for the bracket tree layout.
     * Ensures correct vertical spacing and alignment of match cards across rounds.
     * 
     * @param roundNumber The round number (1-based)
     */
    const roundColumnStyle = (roundNumber: number) => {
        // Base values
        const cardHeight = 74 // Updated height for new match card (2 fighters + borders)
        const baseGap = 32 // gap between matches in round 1

        if (roundNumber === 1) {
            return {
                marginTop: '0px',
                rowGap: `${baseGap}px`,
                '--row-gap': `${baseGap}px`
            }
        }

        // For subsequent rounds
        const power = Math.pow(2, roundNumber - 1)
        const marginTop = ((cardHeight + baseGap) * (power - 1)) / 2
        const gap = (cardHeight + baseGap) * power - cardHeight

        return {
            marginTop: `${marginTop}px`,
            rowGap: `${gap}px`,
            '--row-gap': `${gap}px`
        }
    }

    /**
     * Retrieves the bronze match for a bracket if it exists.
     */
    const bronzeMatchFor = (bracket: BracketItem) => {
        return bracket.bronze_match ?? null
    }

    /**
     * Checks if a match is a bye (one player present, one missing).
     */
    const isBye = (match: MatchItem) => {
        return (match.player_one_id === null && match.player_two_id !== null) ||
               (match.player_one_id !== null && match.player_two_id === null)
    }

    /**
     * Checks if a match is ready to be played (both players are present).
     */
    const matchReady = (match: MatchItem) => {
        return match.player_one_id !== null && match.player_two_id !== null
    }

    return {
        safeAwards,
        safeMatches,
        roundsFor,
        roundsForBracket,
        finalRoundNumber,
        bracketSize,
        roundLabel,
        formatLabel,
        roundColumnStyle,
        bronzeMatchFor,
        isBye,
        matchReady
    }
}
