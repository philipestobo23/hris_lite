export type SortDirection = 'asc' | 'desc';

export interface DataTableColumn {
    /** Key on each row object (supports dot notation, e.g. "branch.name"). */
    key: string;
    /** Column header label. */
    label: string;
    /** Whether the column can be sorted. Defaults to false. */
    sortable?: boolean;
    /** Cell text alignment. Defaults to "left". */
    align?: 'left' | 'center' | 'right';
    /** Extra classes applied to each body cell in this column. */
    class?: string;
    /** Extra classes applied to the header cell. */
    headerClass?: string;
}

export interface DataTableFilters {
    search?: string | null;
    sort?: string | null;
    direction?: SortDirection | null;
    [key: string]: unknown;
}

export interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

/**
 * Shape of a Laravel length-aware paginator when serialized to JSON
 * (i.e. what `->paginate()` returns to an Inertia response).
 */
export interface Paginator<T = Record<string, unknown>> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    from: number | null;
    to: number | null;
    total: number;
    path: string;
    first_page_url: string | null;
    last_page_url: string | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: PaginatorLink[];
}
