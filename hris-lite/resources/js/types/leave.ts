import type { Employee } from './employee';

export type LeaveType =
    | 'vacation'
    | 'sick'
    | 'emergency'
    | 'maternity'
    | 'paternity'
    | 'unpaid';

export type LeaveStatus = 'pending' | 'approved' | 'rejected' | 'cancelled';

export interface Leave {
    id: number;
    branch_id: number;
    employee_id: number;
    type: LeaveType;
    /** ISO date (Y-m-d). */
    start_date: string;
    end_date: string;
    /** Inclusive day count, derived server-side. */
    days: string;
    is_paid: boolean;
    reason: string | null;
    status: LeaveStatus;
    approved_by: number | null;
    approved_at: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    employee?: Pick<
        Employee,
        'id' | 'first_name' | 'last_name' | 'full_name' | 'employee_no'
    > | null;
    approver?: { id: number; name: string } | null;
}

/** Shape bound to Inertia's useForm on the leave create/edit pages. */
export interface LeaveFormData {
    employee_id: number | null;
    type: LeaveType | '';
    start_date: string;
    end_date: string;
    is_paid: boolean;
    reason: string;
}
