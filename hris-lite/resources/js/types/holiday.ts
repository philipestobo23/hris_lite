import type { Branch } from './organization';

export type HolidayType =
    | 'regular'
    | 'special_non_working'
    | 'special_working';

/** Holiday type option, carrying the default pay rule so the form can fill it. */
export interface HolidayTypeOption {
    value: HolidayType;
    label: string;
    pay_rule: number;
}

export interface Holiday {
    id: number;
    /** Null means the holiday applies company-wide (every branch). */
    branch_id: number | null;
    /** ISO date (Y-m-d). */
    date: string;
    name: string;
    type: HolidayType;
    /** Multiplier applied to hours worked, e.g. "2.00" = 200%. */
    pay_rule: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    branch?: Branch | null;
}

/** Shape bound to Inertia's useForm on the holiday create/edit pages. */
export interface HolidayFormData {
    branch_id: number | null;
    date: string;
    name: string;
    type: HolidayType | '';
    pay_rule: number | string;
}
