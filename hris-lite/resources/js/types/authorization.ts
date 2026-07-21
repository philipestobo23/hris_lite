export interface PermissionModule {
    key: string;
    label: string;
    abilities: string[];
}

export interface PermissionAbility {
    key: string;
    label: string;
}

export interface Role {
    id: number;
    name: string;
    users_count?: number;
    permissions: string[];
    is_protected: boolean;
}

export interface RoleFormData {
    name: string;
    permissions: string[];
}
