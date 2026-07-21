import type { Branch, Department, Position } from './organization';

export type EmploymentStatus =
    | 'regular'
    | 'probationary'
    | 'contractual'
    | 'part_time'
    | 'resigned'
    | 'terminated';

export type SalaryType = 'monthly' | 'daily' | 'hourly';

export interface SelectOption {
    value: string;
    label: string;
}

export interface EmployeeDocument {
    id: number;
    employee_id: number;
    name: string;
    type: string | null;
    file_path: string;
    file_name: string | null;
    /** Public URL, appended by the model. */
    url: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
}

export interface Employee {
    id: number;
    employee_no: string;
    /** Enrollment number on the biometric terminals. */
    biometric_id: string | null;
    branch_id: number;
    department_id: number | null;
    position_id: number | null;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    /** "First Last", appended by the model. */
    full_name: string;
    email: string | null;
    phone: string | null;
    photo: string | null;
    /** Public URL of the photo, appended by the model. */
    photo_url: string | null;
    employment_status: EmploymentStatus;
    hire_date: string | null;
    date_of_birth: string | null;
    gender: string | null;
    civil_status: string | null;
    nationality: string | null;
    address: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
    sss_no: string | null;
    tin_no: string | null;
    philhealth_no: string | null;
    pagibig_no: string | null;
    salary_type: SalaryType | null;
    basic_salary: string | null;
    allowance: string | null;
    bank_name: string | null;
    bank_account_no: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    branch?: Branch | null;
    department?: Department | null;
    position?: Position | null;
    documents?: EmployeeDocument[];
}

/** Shape bound to Inertia's useForm on the create/edit form. */
export interface EmployeeFormData {
    // Personal
    first_name: string;
    middle_name: string | null;
    last_name: string;
    date_of_birth: string | null;
    gender: string | null;
    civil_status: string | null;
    nationality: string | null;
    photo: File | null;
    // Employment
    branch_id: number | null;
    department_id: number | null;
    position_id: number | null;
    employment_status: EmploymentStatus;
    hire_date: string | null;
    biometric_id: string | null;
    // Contact & Emergency
    email: string | null;
    phone: string | null;
    address: string | null;
    emergency_contact_name: string | null;
    emergency_contact_phone: string | null;
    emergency_contact_relationship: string | null;
    // Government IDs
    sss_no: string | null;
    tin_no: string | null;
    philhealth_no: string | null;
    pagibig_no: string | null;
    // Salary
    salary_type: SalaryType | null;
    basic_salary: number | string | null;
    allowance: number | string | null;
    bank_name: string | null;
    bank_account_no: string | null;
}
