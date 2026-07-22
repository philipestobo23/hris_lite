import type { Holiday } from './holiday';

export type DtrStatus =
    | 'present'
    | 'absent'
    | 'on_leave'
    | 'holiday'
    | 'rest_day';

export interface DailyTimeRecord {
    id: number;
    branch_id: number;
    employee_id: number;
    holiday_id: number | null;
    /** ISO date (Y-m-d). */
    work_date: string;
    time_in: string | null;
    break_out: string | null;
    break_in: string | null;
    time_out: string | null;
    hours_worked: string;
    late_minutes: number;
    undertime_minutes: number;
    overtime_minutes: number;
    night_differential_minutes: number;
    is_rest_day: boolean;
    status: DtrStatus;
    remarks: string | null;
    created_at: string;
    updated_at: string;
    holiday?: Pick<Holiday, 'id' | 'name' | 'type'> | null;
}

/** Month totals shown above the DTR table. */
export interface DtrTotals {
    days: number;
    hours: number;
    late: number;
    undertime: number;
    overtime: number;
    night: number;
}
