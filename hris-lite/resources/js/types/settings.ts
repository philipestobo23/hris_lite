export interface SettingFieldOption {
    value: string;
    label: string;
}

export type SettingFieldType =
    | 'number'
    | 'text'
    | 'textarea'
    | 'select'
    | 'image';

export interface SettingField {
    key: string;
    label: string;
    type: SettingFieldType;
    help?: string | null;
    min?: number | null;
    max?: number | null;
    step?: number | null;
    options?: SettingFieldOption[] | null;
}

export interface SettingGroup {
    key: string;
    label: string;
    description?: string | null;
    fields: SettingField[];
}

/** Nested "{group}.{field}" => value map, e.g. { payroll: { ot_multiplier: 1.25 } }. */
export type SettingsValues = Record<string, Record<string, unknown>>;
