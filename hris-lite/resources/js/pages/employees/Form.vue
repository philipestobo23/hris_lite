<script setup lang="ts">
import { Link, router, useForm } from '@inertiajs/vue3';
import type { InertiaForm } from '@inertiajs/vue3';
import {
    CircleAlert,
    Download,
    FileText,
    Paperclip,
    Trash2,
    Upload,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import type { Employee, EmployeeFormData, SelectOption } from '@/types';

const props = defineProps<{
    form: InertiaForm<EmployeeFormData>;
    mode: 'create' | 'edit';
    employee?: Employee;
    branches: { id: number; name: string }[];
    departments: { id: number; name: string; branch_id: number | null }[];
    positions: { id: number; name: string; department_id: number }[];
    employmentStatuses: SelectOption[];
    salaryTypes: SelectOption[];
    submitLabel: string;
}>();

const emit = defineEmits<{ submit: [] }>();

const genderOptions: SelectOption[] = [
    { value: 'male', label: 'Male' },
    { value: 'female', label: 'Female' },
    { value: 'other', label: 'Other' },
];
const civilStatusOptions: SelectOption[] = [
    { value: 'single', label: 'Single' },
    { value: 'married', label: 'Married' },
    { value: 'widowed', label: 'Widowed' },
    { value: 'separated', label: 'Separated' },
];

const tabs = [
    {
        key: 'personal',
        label: 'Personal',
        fields: [
            'photo',
            'first_name',
            'middle_name',
            'last_name',
            'date_of_birth',
            'gender',
            'civil_status',
            'nationality',
        ],
    },
    {
        key: 'employment',
        label: 'Employment',
        fields: [
            'branch_id',
            'department_id',
            'position_id',
            'employment_status',
            'hire_date',
            'biometric_id',
        ],
    },
    {
        key: 'contact',
        label: 'Contact & Emergency',
        fields: [
            'email',
            'phone',
            'address',
            'emergency_contact_name',
            'emergency_contact_phone',
            'emergency_contact_relationship',
        ],
    },
    {
        key: 'gov',
        label: 'Government IDs',
        fields: ['sss_no', 'tin_no', 'philhealth_no', 'pagibig_no'],
    },
    {
        key: 'salary',
        label: 'Salary',
        fields: [
            'salary_type',
            'basic_salary',
            'allowance',
            'bank_name',
            'bank_account_no',
        ],
    },
    { key: 'documents', label: 'Documents', fields: [] as string[] },
] as const;

const activeTab = ref<string>('personal');

function tabHasErrors(fields: readonly string[]): boolean {
    const errors = props.form.errors as Record<string, string | undefined>;

    return fields.some((field) => errors[field]);
}

// After a failed submit, jump to the first tab that has an error.
watch(
    () => props.form.errors,
    (errors) => {
        if (Object.keys(errors).length === 0) {
            return;
        }

        const firstBad = tabs.find((tab) => tabHasErrors(tab.fields));

        if (firstBad) {
            activeTab.value = firstBad.key;
        }
    },
    { deep: true },
);

// --- select proxies (Reka Select works with string values) ---
function numberSelect(key: 'branch_id' | 'department_id' | 'position_id') {
    return computed<string>({
        get: () => (props.form[key] == null ? '' : String(props.form[key])),
        set: (value) => {
            props.form[key] = value === '' ? null : Number(value);
        },
    });
}
function stringSelect(
    key: 'gender' | 'civil_status' | 'salary_type' | 'employment_status',
) {
    return computed<string>({
        get: () => (props.form[key] == null ? '' : String(props.form[key])),
        set: (value) => {
            (props.form as unknown as Record<string, unknown>)[key] =
                value === '' ? null : value;
        },
    });
}

const branchModel = numberSelect('branch_id');
const departmentModel = numberSelect('department_id');
const positionModel = numberSelect('position_id');
const genderModel = stringSelect('gender');
const civilStatusModel = stringSelect('civil_status');
const employmentStatusModel = stringSelect('employment_status');
const salaryTypeModel = stringSelect('salary_type');

// --- cascading options ---
const availableDepartments = computed(() =>
    props.departments.filter(
        (d) => d.branch_id === props.form.branch_id || d.branch_id === null,
    ),
);
const availablePositions = computed(() =>
    props.positions.filter((p) => p.department_id === props.form.department_id),
);

watch(
    () => props.form.branch_id,
    () => {
        if (
            !availableDepartments.value.some(
                (d) => d.id === props.form.department_id,
            )
        ) {
            props.form.department_id = null;
            props.form.position_id = null;
        }
    },
);
watch(
    () => props.form.department_id,
    () => {
        if (
            !availablePositions.value.some(
                (p) => p.id === props.form.position_id,
            )
        ) {
            props.form.position_id = null;
        }
    },
);

// --- photo ---
const photoPreview = ref<string | null>(props.employee?.photo_url ?? null);
function onPhotoChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    props.form.photo = file;
    photoPreview.value = file
        ? URL.createObjectURL(file)
        : (props.employee?.photo_url ?? null);
}

// --- documents (edit mode) ---
const docFileInput = ref<HTMLInputElement | null>(null);
const docForm = useForm<{ name: string; type: string; file: File | null }>({
    name: '',
    type: '',
    file: null,
});
function onDocFileChange(event: Event): void {
    docForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}
function uploadDocument(): void {
    if (!props.employee) {
        return;
    }

    docForm.post(`/employees/${props.employee.id}/documents`, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            docForm.reset();

            if (docFileInput.value) {
                docFileInput.value.value = '';
            }
        },
    });
}
function deleteDocument(documentId: number): void {
    if (!props.employee) {
        return;
    }

    if (confirm('Delete this document?')) {
        router.delete(
            `/employees/${props.employee.id}/documents/${documentId}`,
            { preserveScroll: true },
        );
    }
}

const documents = computed(() => props.employee?.documents ?? []);
</script>

<template>
    <form @submit.prevent="emit('submit')">
        <Tabs v-model="activeTab">
            <TabsList class="h-auto">
                <TabsTrigger
                    v-for="tab in tabs"
                    :key="tab.key"
                    :value="tab.key"
                >
                    {{ tab.label }}
                    <span
                        v-if="tabHasErrors(tab.fields)"
                        class="ml-1 size-1.5 rounded-full bg-destructive"
                    />
                </TabsTrigger>
            </TabsList>

            <!-- Tab 1: Personal -->
            <TabsContent value="personal">
                <Card>
                    <CardHeader><CardTitle>Personal</CardTitle></CardHeader>
                    <CardContent class="grid gap-6 sm:grid-cols-2">
                        <div class="flex items-center gap-4 sm:col-span-2">
                            <div
                                class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-muted"
                            >
                                <img
                                    v-if="photoPreview"
                                    :src="photoPreview"
                                    alt="Photo"
                                    class="size-full object-cover"
                                />
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                >
                                    No photo
                                </span>
                            </div>
                            <FormField
                                label="Photo"
                                for="photo"
                                :error="form.errors.photo"
                                hint="JPG or PNG, up to 2 MB."
                            >
                                <Input
                                    id="photo"
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    class="max-w-xs"
                                    @change="onPhotoChange"
                                />
                            </FormField>
                        </div>

                        <FormField
                            label="First name"
                            for="first_name"
                            :error="form.errors.first_name"
                        >
                            <Input
                                id="first_name"
                                v-model="form.first_name"
                                required
                            />
                        </FormField>
                        <FormField
                            label="Middle name"
                            for="middle_name"
                            :error="form.errors.middle_name"
                        >
                            <Input
                                id="middle_name"
                                v-model="form.middle_name"
                            />
                        </FormField>
                        <FormField
                            label="Last name"
                            for="last_name"
                            :error="form.errors.last_name"
                        >
                            <Input
                                id="last_name"
                                v-model="form.last_name"
                                required
                            />
                        </FormField>
                        <FormField
                            label="Date of birth"
                            for="date_of_birth"
                            :error="form.errors.date_of_birth"
                        >
                            <Input
                                id="date_of_birth"
                                v-model="form.date_of_birth"
                                type="date"
                            />
                        </FormField>
                        <FormField label="Gender" :error="form.errors.gender">
                            <Select v-model="genderModel">
                                <SelectTrigger class="w-full"
                                    ><SelectValue placeholder="Select"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="o in genderOptions"
                                        :key="o.value"
                                        :value="o.value"
                                        >{{ o.label }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Civil status"
                            :error="form.errors.civil_status"
                        >
                            <Select v-model="civilStatusModel">
                                <SelectTrigger class="w-full"
                                    ><SelectValue placeholder="Select"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="o in civilStatusOptions"
                                        :key="o.value"
                                        :value="o.value"
                                        >{{ o.label }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Nationality"
                            for="nationality"
                            :error="form.errors.nationality"
                        >
                            <Input
                                id="nationality"
                                v-model="form.nationality"
                                placeholder="e.g. Filipino"
                            />
                        </FormField>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab 2: Employment -->
            <TabsContent value="employment">
                <Card>
                    <CardHeader><CardTitle>Employment</CardTitle></CardHeader>
                    <CardContent class="grid gap-6 sm:grid-cols-2">
                        <FormField
                            label="Employee no."
                            hint="Auto-generated on save."
                        >
                            <Input
                                :model-value="
                                    employee?.employee_no ?? 'Auto-generated'
                                "
                                disabled
                            />
                        </FormField>
                        <FormField
                            label="Employment status"
                            :error="form.errors.employment_status"
                        >
                            <Select v-model="employmentStatusModel">
                                <SelectTrigger class="w-full"
                                    ><SelectValue placeholder="Select"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="o in employmentStatuses"
                                        :key="o.value"
                                        :value="o.value"
                                        >{{ o.label }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Branch"
                            :error="form.errors.branch_id"
                        >
                            <Select v-model="branchModel">
                                <SelectTrigger class="w-full"
                                    ><SelectValue placeholder="Select a branch"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="o in branches"
                                        :key="o.id"
                                        :value="String(o.id)"
                                        >{{ o.name }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Department"
                            :error="form.errors.department_id"
                            hint="Filtered by the selected branch."
                        >
                            <Select
                                v-model="departmentModel"
                                :disabled="form.branch_id === null"
                            >
                                <SelectTrigger class="w-full"
                                    ><SelectValue
                                        placeholder="Select a department"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="o in availableDepartments"
                                        :key="o.id"
                                        :value="String(o.id)"
                                        >{{ o.name }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Position"
                            :error="form.errors.position_id"
                            hint="Filtered by the selected department."
                        >
                            <Select
                                v-model="positionModel"
                                :disabled="form.department_id === null"
                            >
                                <SelectTrigger class="w-full"
                                    ><SelectValue
                                        placeholder="Select a position"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="o in availablePositions"
                                        :key="o.id"
                                        :value="String(o.id)"
                                        >{{ o.name }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Hire date"
                            for="hire_date"
                            :error="form.errors.hire_date"
                        >
                            <Input
                                id="hire_date"
                                v-model="form.hire_date"
                                type="date"
                            />
                        </FormField>
                        <FormField
                            label="Biometric ID"
                            for="biometric_id"
                            hint="Enrollment number on the attendance terminals."
                            :error="form.errors.biometric_id"
                        >
                            <Input
                                id="biometric_id"
                                v-model="form.biometric_id"
                            />
                        </FormField>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab 3: Contact & Emergency -->
            <TabsContent value="contact">
                <Card>
                    <CardHeader
                        ><CardTitle
                            >Contact &amp; Emergency</CardTitle
                        ></CardHeader
                    >
                    <CardContent class="grid gap-6 sm:grid-cols-2">
                        <FormField
                            label="Email"
                            for="email"
                            :error="form.errors.email"
                        >
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                            />
                        </FormField>
                        <FormField
                            label="Phone"
                            for="phone"
                            :error="form.errors.phone"
                        >
                            <Input id="phone" v-model="form.phone" />
                        </FormField>
                        <FormField
                            label="Address"
                            for="address"
                            :error="form.errors.address"
                            class="sm:col-span-2"
                        >
                            <Textarea
                                id="address"
                                v-model="form.address"
                                rows="2"
                            />
                        </FormField>
                        <FormField
                            label="Emergency contact name"
                            for="ecn"
                            :error="form.errors.emergency_contact_name"
                        >
                            <Input
                                id="ecn"
                                v-model="form.emergency_contact_name"
                            />
                        </FormField>
                        <FormField
                            label="Emergency contact phone"
                            for="ecp"
                            :error="form.errors.emergency_contact_phone"
                        >
                            <Input
                                id="ecp"
                                v-model="form.emergency_contact_phone"
                            />
                        </FormField>
                        <FormField
                            label="Relationship"
                            for="ecr"
                            :error="form.errors.emergency_contact_relationship"
                        >
                            <Input
                                id="ecr"
                                v-model="form.emergency_contact_relationship"
                                placeholder="e.g. Spouse"
                            />
                        </FormField>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab 4: Government IDs -->
            <TabsContent value="gov">
                <Card>
                    <CardHeader
                        ><CardTitle>Government IDs</CardTitle></CardHeader
                    >
                    <CardContent class="grid gap-6 sm:grid-cols-2">
                        <FormField
                            label="SSS no."
                            for="sss"
                            :error="form.errors.sss_no"
                        >
                            <Input id="sss" v-model="form.sss_no" />
                        </FormField>
                        <FormField
                            label="TIN"
                            for="tin"
                            :error="form.errors.tin_no"
                        >
                            <Input id="tin" v-model="form.tin_no" />
                        </FormField>
                        <FormField
                            label="PhilHealth no."
                            for="phic"
                            :error="form.errors.philhealth_no"
                        >
                            <Input id="phic" v-model="form.philhealth_no" />
                        </FormField>
                        <FormField
                            label="Pag-IBIG no."
                            for="hdmf"
                            :error="form.errors.pagibig_no"
                        >
                            <Input id="hdmf" v-model="form.pagibig_no" />
                        </FormField>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab 5: Salary -->
            <TabsContent value="salary">
                <Card>
                    <CardHeader><CardTitle>Salary</CardTitle></CardHeader>
                    <CardContent class="grid gap-6 sm:grid-cols-2">
                        <FormField
                            label="Salary type"
                            :error="form.errors.salary_type"
                        >
                            <Select v-model="salaryTypeModel">
                                <SelectTrigger class="w-full"
                                    ><SelectValue placeholder="Select"
                                /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="o in salaryTypes"
                                        :key="o.value"
                                        :value="o.value"
                                        >{{ o.label }}</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </FormField>
                        <FormField
                            label="Basic salary"
                            for="basic_salary"
                            :error="form.errors.basic_salary"
                        >
                            <Input
                                id="basic_salary"
                                v-model.number="form.basic_salary"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                        </FormField>
                        <FormField
                            label="Allowance"
                            for="allowance"
                            :error="form.errors.allowance"
                        >
                            <Input
                                id="allowance"
                                v-model.number="form.allowance"
                                type="number"
                                min="0"
                                step="0.01"
                            />
                        </FormField>
                        <FormField
                            label="Bank name"
                            for="bank_name"
                            :error="form.errors.bank_name"
                        >
                            <Input id="bank_name" v-model="form.bank_name" />
                        </FormField>
                        <FormField
                            label="Bank account no."
                            for="bank_account_no"
                            :error="form.errors.bank_account_no"
                        >
                            <Input
                                id="bank_account_no"
                                v-model="form.bank_account_no"
                            />
                        </FormField>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Tab 6: Documents -->
            <TabsContent value="documents">
                <Card>
                    <CardHeader><CardTitle>Documents</CardTitle></CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <div
                            v-if="mode === 'create'"
                            class="flex items-center gap-2 rounded-md border border-dashed p-4 text-sm text-muted-foreground"
                        >
                            <CircleAlert class="size-4" />
                            Save the employee first, then upload documents here.
                        </div>

                        <template v-else>
                            <!-- upload -->
                            <div
                                class="grid gap-3 rounded-md border p-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end"
                            >
                                <FormField
                                    label="Document name"
                                    for="doc_name"
                                    :error="docForm.errors.name"
                                >
                                    <Input
                                        id="doc_name"
                                        v-model="docForm.name"
                                        placeholder="e.g. Contract"
                                    />
                                </FormField>
                                <FormField
                                    label="File"
                                    for="doc_file"
                                    :error="docForm.errors.file"
                                    hint="PDF/JPG/PNG/DOC, up to 5 MB."
                                >
                                    <input
                                        id="doc_file"
                                        ref="docFileInput"
                                        type="file"
                                        class="text-sm"
                                        @change="onDocFileChange"
                                    />
                                </FormField>
                                <Button
                                    type="button"
                                    :disabled="
                                        docForm.processing ||
                                        !docForm.name ||
                                        !docForm.file
                                    "
                                    @click="uploadDocument"
                                >
                                    <Upload class="size-4" /> Upload
                                </Button>
                            </div>

                            <!-- list -->
                            <div
                                v-if="documents.length"
                                class="divide-y rounded-md border"
                            >
                                <div
                                    v-for="doc in documents"
                                    :key="doc.id"
                                    class="flex items-center gap-3 p-3"
                                >
                                    <FileText
                                        class="size-4 shrink-0 text-muted-foreground"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium">
                                            {{ doc.name }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ doc.file_name }}
                                        </p>
                                    </div>
                                    <a
                                        v-if="doc.url"
                                        :href="doc.url"
                                        target="_blank"
                                        class="text-muted-foreground hover:text-foreground"
                                        title="Download"
                                    >
                                        <Download class="size-4" />
                                    </a>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        title="Delete"
                                        @click="deleteDocument(doc.id)"
                                    >
                                        <Trash2
                                            class="size-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </div>
                            <div
                                v-else
                                class="flex items-center gap-2 p-4 text-sm text-muted-foreground"
                            >
                                <Paperclip class="size-4" /> No documents yet.
                            </div>
                        </template>
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>

        <div class="mt-6 flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">{{
                submitLabel
            }}</Button>
            <Button variant="ghost" as-child>
                <Link href="/employees">Cancel</Link>
            </Button>
        </div>
    </form>
</template>
