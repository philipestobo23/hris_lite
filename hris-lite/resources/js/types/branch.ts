export interface BranchOption {
    id: number;
    name: string;
}

/** Shared `branch` page prop: the current user's branch context. */
export interface BranchBar {
    /** Active branch id, or null for "all branches". */
    active: number | null;
    /** Whether an "All branches" option is offered. */
    canSwitchAll: boolean;
    /** Branches the user may switch between. */
    accessible: BranchOption[];
}
