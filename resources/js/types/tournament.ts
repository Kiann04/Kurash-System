export interface Player {
    id: number
    full_name: string
    gender: 'male' | 'female' | 'Male' | 'Female' | 'M' | 'F'
    club: string
    status: string
    membership_expires_at?: string
    dob?: string
}

export interface TournamentWeightCategory {
    id: number
    gender: 'male' | 'female' | 'Male' | 'Female' | 'M' | 'F'
    age_category_id: number
    age_category_name: string
    name: string
}

export interface TournamentRegistration {
    player_id: number
    weight_category_id: number | null
}

export interface Registration {
    player_id: number
    tournament_weight_category_id: number
}

// Interface: Represents a row in the import file analysis
export interface ImportRegistration {
    player_id: number
    tournament_weight_category_id: number
    player_name: string
    category_name: string
}

// Interface: Detailed result for each row in the import file
export interface ImportRowResult {
    row: number
    status: 'matched' | 'unmatched_player' | 'unresolved_category' | 'duplicate'
    player: string
    category: string | null
    reason: string
}

// Interface: Summary of the file import analysis
export interface ImportAnalysis {
    total_rows: number
    matched_count: number
    unmatched_player_count: number
    unresolved_category_count: number
    duplicate_count: number
    registrations: ImportRegistration[]
    rows: ImportRowResult[]
}

export interface TournamentFormState {
    name: string
    location: string | null
    tournament_date: string
    status: string
    registrations: Registration[]
}
