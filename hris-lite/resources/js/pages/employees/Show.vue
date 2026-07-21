<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, FileText, Pencil } from '@lucide/vue';
import InfoItem from '@/components/InfoItem.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Employee } from '@/types';

const props = defineProps<{
    employee: Employee;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Employees', href: '/employees' },
            { title: '201 File', href: '#' },
        ],
    },
});

const inactiveStatuses = ['resigned', 'terminated'];
const isActive = !inactiveStatuses.includes(props.employee.employment_status);

function label(value: string | null): string {
    return value
        ? value.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase())
        : '—';
}
function date(value: string | null): string {
    return value ? value.substring(0, 10) : '—';
}
function money(value: string | null): string {
    if (value === null || value === '') {
        return '—';
    }
    return Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
    });
}
</script>

<template>
    <Head :title="`201 File — ${employee.full_name}`" />

    <div class="flex flex-col gap-6">
        <!-- Header -->
        <Card>
            <CardContent class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div
                    class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded-full border bg-muted"
                >
                    <img
                        v-if="employee.photo_url"
                        :src="employee.photo_url"
                        :alt="employee.full_name"
                        class="size-full object-cover"
                    />
                    <span v-else class="text-xs text-muted-foreground">No photo</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold tracking-tight">
                            {{ employee.full_name }}
                        </h2>
                        <Badge :variant="isActive ? 'default' : 'secondary'">
                            {{ label(employee.employment_status) }}
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ employee.employee_no }}
                        <span v-if="employee.position"> · {{ employee.position.name }}</span>
                        <span v-if="employee.branch"> · {{ employee.branch.name }}</span>
                    </p>
                </div>
                <Button as-child>
                    <Link :href="`/employees/${employee.id}/edit`">
                        <Pencil class="size-4" />
                        Edit
                    </Link>
                </Button>
            </CardContent>
        </Card>

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Personal -->
            <Card>
                <CardHeader><CardTitle>Personal</CardTitle></CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <InfoItem label="First name" :value="employee.first_name" />
                        <InfoItem label="Middle name" :value="employee.middle_name" />
                        <InfoItem label="Last name" :value="employee.last_name" />
                        <InfoItem label="Date of birth" :value="date(employee.date_of_birth)" />
                        <InfoItem label="Gender" :value="label(employee.gender)" />
                        <InfoItem label="Civil status" :value="label(employee.civil_status)" />
                        <InfoItem label="Nationality" :value="employee.nationality" />
                    </dl>
                </CardContent>
            </Card>

            <!-- Employment -->
            <Card>
                <CardHeader><CardTitle>Employment</CardTitle></CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <InfoItem label="Employee no." :value="employee.employee_no" />
                        <InfoItem label="Status" :value="label(employee.employment_status)" />
                        <InfoItem label="Branch" :value="employee.branch?.name" />
                        <InfoItem label="Department" :value="employee.department?.name" />
                        <InfoItem label="Position" :value="employee.position?.name" />
                        <InfoItem label="Hire date" :value="date(employee.hire_date)" />
                    </dl>
                </CardContent>
            </Card>

            <!-- Contact & Emergency -->
            <Card>
                <CardHeader><CardTitle>Contact &amp; Emergency</CardTitle></CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <InfoItem label="Email" :value="employee.email" />
                        <InfoItem label="Phone" :value="employee.phone" />
                        <InfoItem label="Address" :value="employee.address" class="sm:col-span-2" />
                        <InfoItem label="Emergency contact" :value="employee.emergency_contact_name" />
                        <InfoItem label="Emergency phone" :value="employee.emergency_contact_phone" />
                        <InfoItem label="Relationship" :value="employee.emergency_contact_relationship" />
                    </dl>
                </CardContent>
            </Card>

            <!-- Government IDs -->
            <Card>
                <CardHeader><CardTitle>Government IDs</CardTitle></CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <InfoItem label="SSS no." :value="employee.sss_no" />
                        <InfoItem label="TIN" :value="employee.tin_no" />
                        <InfoItem label="PhilHealth no." :value="employee.philhealth_no" />
                        <InfoItem label="Pag-IBIG no." :value="employee.pagibig_no" />
                    </dl>
                </CardContent>
            </Card>

            <!-- Salary -->
            <Card>
                <CardHeader><CardTitle>Salary</CardTitle></CardHeader>
                <CardContent>
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <InfoItem label="Salary type" :value="label(employee.salary_type)" />
                        <InfoItem label="Basic salary" :value="money(employee.basic_salary)" />
                        <InfoItem label="Allowance" :value="money(employee.allowance)" />
                        <InfoItem label="Bank" :value="employee.bank_name" />
                        <InfoItem label="Bank account no." :value="employee.bank_account_no" />
                    </dl>
                </CardContent>
            </Card>

            <!-- Documents -->
            <Card>
                <CardHeader><CardTitle>Documents</CardTitle></CardHeader>
                <CardContent>
                    <div
                        v-if="employee.documents && employee.documents.length"
                        class="divide-y rounded-md border"
                    >
                        <div
                            v-for="doc in employee.documents"
                            :key="doc.id"
                            class="flex items-center gap-3 p-3"
                        >
                            <FileText class="size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ doc.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">
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
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        No documents on file.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
