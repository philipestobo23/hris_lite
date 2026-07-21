export interface Branch {
    id: number;
    name: string;
    code: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    /** Present when the controller eager-loads withCount('departments'). */
    departments_count?: number;
    departments?: Department[];
}

export interface Department {
    id: number;
    /** Null means the department is shared across all branches. */
    branch_id: number | null;
    name: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    branch?: Branch | null;
    /** Present when the controller eager-loads withCount('positions'). */
    positions_count?: number;
    positions?: Position[];
}

export interface Position {
    id: number;
    department_id: number;
    name: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    department?: Department | null;
}

// Shapes bound to Inertia's useForm on the create/edit pages.

export interface BranchFormData {
    name: string;
    code: string;
    is_active: boolean;
}

export interface DepartmentFormData {
    branch_id: number | null;
    name: string;
    is_active: boolean;
}

export interface PositionFormData {
    department_id: number | null;
    name: string;
    is_active: boolean;
}
