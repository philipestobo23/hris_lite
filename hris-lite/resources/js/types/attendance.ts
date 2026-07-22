import type { Employee } from './employee';
import type { Branch } from './organization';

export type DeviceModel = 'K30' | 'K40';

export type DeviceMode = 'push' | 'pull';

export type PunchStatus = 'in' | 'out';

/** A single raw punch pulled from a biometric terminal. */
export interface AttendanceLog {
    id: number;
    branch_id: number;
    biometric_device_id: number | null;
    employee_id: number | null;
    /** Enrollment number recorded on the device. */
    device_user_id: string;
    /** Name enrolled on the terminal for this device user, if the roster had it. */
    device_user_name: string | null;
    /** ISO timestamp of the punch. */
    punched_at: string;
    status: PunchStatus | null;
    /** fingerprint | password | card, when the device reports it. */
    verify_mode: string | null;
    work_code: string | null;
    is_processed: boolean;
    created_at: string;
    updated_at: string;
    employee?: Pick<
        Employee,
        'id' | 'first_name' | 'last_name' | 'full_name' | 'employee_no'
    > | null;
    device?: Pick<BiometricDevice, 'id' | 'name'> | null;
}

export interface BiometricDevice {
    id: number;
    branch_id: number;
    name: string;
    model: DeviceModel;
    ip_address: string | null;
    port: number;
    serial_number: string | null;
    mode: DeviceMode;
    is_active: boolean;
    last_synced_at: string | null;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    branch?: Branch | null;
}

/** Shape bound to Inertia's useForm on the device create/edit pages. */
export interface BiometricDeviceFormData {
    branch_id: number | null;
    name: string;
    model: DeviceModel | '';
    ip_address: string;
    port: number;
    serial_number: string;
    mode: DeviceMode | '';
    is_active: boolean;
}
