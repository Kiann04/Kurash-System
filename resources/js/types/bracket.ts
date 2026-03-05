export interface MatchItem {
    id: number
    round_number: number
    match_number: number
    status: 'scheduled' | 'completed'
    player_one_id: number | null
    player_one: string | null
    player_one_seed?: number | null
    player_two_id: number | null
    player_two: string | null
    player_two_seed?: number | null
    winner_id: number | null
    winner: string | null
}

export interface BracketItem {
    id: number
    gender: string | null
    age_category: string | null
    weight_category: string | null
    format: 'round_robin' | 'single_elimination' | null
    rounds: number
    entrant_count?: number
    champion: string | null
    awards?: {
        gold: string | null
        silver: string | null
        bronze: string[]
    }
    bronze_match?: MatchItem | null
    matches?: MatchItem[]
}

export interface TournamentItem {
    id: number
    name: string
    tournament_date: string
    status: 'draft' | 'open' | 'ongoing' | 'completed'
    registrations_count?: number
}

export interface CategoryParticipant {
    gender: string
    age_category: string
    weight_category: string
    participant_count: number
}
