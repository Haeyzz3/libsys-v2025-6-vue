<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { valueUpdater } from '@/lib/utils';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from '@radix-icons/vue';
import {
    FlexRender,
    getCoreRowModel,
    getExpandedRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
    VisibilityState,
} from '@tanstack/vue-table';
import { ArrowUpDown, ChevronDown, ListFilter, Plus, X } from 'lucide-vue-next';
import { h, ref } from 'vue';
import { route } from 'ziggy-js';
import DropdownAction from './DataTableDemoColumn.vue';

interface Props {
    data?: {
        data: any[];
        current_page?: number;
        per_page?: number;
        last_page?: number;
    };
    filter?: any[];
    currentSortField?: string;
    currentSortDirection?: string;
    ddcClasses?: any[];
    availablePurposes?: { value: string; label: string }[];
}

const props = withDefaults(defineProps<Props>(), {
    data: () => ({ data: [], current_page: 1, per_page: 10, last_page: 1 }),
    filter: () => [],
    currentSortField: undefined,
    currentSortDirection: 'asc',
    ddcClasses: () => [],
    availablePurposes: () => [],
});

import type { Column, ColumnDef, ColumnFiltersState, Row, SortingState, Table } from '@tanstack/vue-table';
type RowData = any;
const data = props.data.data;
const columns: ColumnDef<RowData>[] = [
    {
        id: 'search',
        accessorFn: (row) => `${row.id} ${row.user?.first_name} ${row.user?.last_name}`,
        enableSorting: false,
        enableHiding: false,
    },
    {
        id: 'select',
        header: ({ table }: { table: Table<RowData> }) =>
            h(Checkbox, {
                checked: table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
                'onUpdate:checked': (value: boolean) => table.toggleAllPageRowsSelected(!!value),
                ariaLabel: 'Select all',
            }),
        cell: ({ row }: { row: Row<RowData> }) =>
            h(Checkbox, {
                checked: row.getIsSelected(),
                'onUpdate:checked': (value: boolean) => row.toggleSelected(!!value),
                ariaLabel: 'Select row',
            }),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'id',
        header: ({ column }: { column: Column<RowData, any> }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const currentSort = column.getIsSorted();
                        if (currentSort === false) {
                            column.toggleSorting(false);
                        } else if (currentSort === 'asc') {
                            column.toggleSorting(true);
                        } else {
                            column.clearSorting();
                        }
                    },
                },
                () => ['ID', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }: { row: Row<RowData> }) => h('div', { class: 'max-w-48 whitespace-normal break-words' }, row.getValue('id')),
        enableHiding: false,
    },
    {
        accessorKey: 'client',
        header: ({ column }: { column: Column<RowData, any> }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const currentSort = column.getIsSorted();
                        if (currentSort === false) {
                            column.toggleSorting(false);
                        } else if (currentSort === 'asc') {
                            column.toggleSorting(true);
                        } else {
                            column.clearSorting();
                        }
                    },
                },
                () => ['Client', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }: { row: Row<RowData> }) => {
            const user = row.original.user;
            if (user) {
                return h('div', user.first_name + ' ' + user.last_name || 'Unknown');
            } else {
                return h('div', 'no user');
            }
        },
        enableHiding: false,
    },
    {
        accessorKey: 'entry_time',
        header: ({ column }: { column: Column<RowData, any> }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const currentSort = column.getIsSorted();
                        if (currentSort === false) {
                            column.toggleSorting(false);
                        } else if (currentSort === 'asc') {
                            column.toggleSorting(true);
                        } else {
                            column.clearSorting();
                        }
                    },
                },
                () => ['Entry Time', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }: { row: Row<RowData> }) => h('div', { class: 'max-w-48 whitespace-normal break-words' }, row.getValue('entry_time')),
        enableHiding: false,
    },
    {
        accessorKey: 'exit_time',
        header: ({ column }: { column: Column<RowData, any> }) => {
            return h(
                Button,
                {
                    variant: 'ghost',
                    onClick: () => {
                        const currentSort = column.getIsSorted();
                        if (currentSort === false) {
                            column.toggleSorting(false);
                        } else if (currentSort === 'asc') {
                            column.toggleSorting(true);
                        } else {
                            column.clearSorting();
                        }
                    },
                },
                () => ['Exit Time', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })],
            );
        },
        cell: ({ row }: { row: Row<RowData> }) => h('div', { class: 'max-w-48 whitespace-normal break-words' }, row.getValue('exit_time')),
        enableHiding: false,
    },
    {
        accessorKey: 'visit_purpose_id',
        header: 'Purpose',
        cell: ({ row }: { row: Row<RowData> }) => {
            const purpose = row.original.visit_purpose;
            if (purpose) {
                return h('div', purpose.name || 'Unknown');
            } else {
                return h('div', 'not specified');
            }
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }: { row: Row<RowData> }) => {
            const payment = row.original;
            return h(
                'div',
                { class: 'relative' },
                h(DropdownAction, {
                    payment,
                    onExpand: row.toggleExpanded,
                }),
            );
        },
    },
];

const sorting = ref<SortingState>(
    props.currentSortField
        ? [
              {
                  id: props.currentSortField,
                  desc: props.currentSortDirection === 'desc',
              },
          ]
        : [],
);
const columnFilters = ref<ColumnFiltersState>(props.filter ? props.filter.map((f) => ({ id: f.id, value: f.value })) : []);
const columnVisibility = ref<VisibilityState>({
    search: false,
});
const rowSelection = ref({});
const expanded = ref({});
const pageSizes = [1, 2, 3, 5, 10, 15, 30, 40, 50, 100];
const pagination = ref({
    pageIndex: (props.data?.current_page ?? 1) - 1,
    pageSize: props.data?.per_page ?? 10,
});

const table = useVueTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    pageCount: props.data?.last_page ?? 1,
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
    onPaginationChange: (updater) => {
        if (typeof updater === 'function') {
            pagination.value = updater(pagination.value);
        } else {
            pagination.value = updater;
        }

        let filters: Record<string, any> = {};
        if (columnFilters.value && columnFilters.value.length > 0) {
            filters = columnFilters.value.reduce((acc: Record<string, any>, filter) => {
                if (Array.isArray(filter.value) && filter.value.length > 0) {
                    acc[filter.id] = filter.value;
                } else if (!Array.isArray(filter.value) && filter.value !== '' && filter.value !== null && filter.value !== undefined) {
                    acc[filter.id] = filter.value;
                }
                return acc;
            }, {});
        }

        router.get(
            route('logger.index'),
            {
                page: pagination.value.pageIndex + 1,
                per_page: pagination.value.pageSize,
                sort_field: sorting.value[0]?.id,
                sort_direction: sorting.value.length == 0 ? undefined : sorting.value[0]?.desc ? 'desc' : 'asc',
                search: filters.search,
                visit_purpose_id: filters.visit_purpose_id,
            },
            { preserveState: false, preserveScroll: true },
        );
    },
    onSortingChange: (updaterOrValue) => {
        if (typeof updaterOrValue === 'function') {
            sorting.value = updaterOrValue(sorting.value);
        } else {
            sorting.value = updaterOrValue;
        }

        let filters: Record<string, any> = {};
        if (columnFilters.value && columnFilters.value.length > 0) {
            filters = columnFilters.value.reduce((acc: Record<string, any>, filter) => {
                if (Array.isArray(filter.value) && filter.value.length > 0) {
                    acc[filter.id] = filter.value;
                } else if (!Array.isArray(filter.value) && filter.value !== '' && filter.value !== null && filter.value !== undefined) {
                    acc[filter.id] = filter.value;
                }
                return acc;
            }, {});
        }

        router.get(
            route('logger.index'),
            {
                page: 1,
                per_page: pagination.value.pageSize,
                sort_field: sorting.value[0]?.id,
                sort_direction: sorting.value.length == 0 ? undefined : sorting.value[0]?.desc ? 'desc' : 'asc',
                search: filters.search,
                visit_purpose_id: filters.visit_purpose_id,
            },
            { preserveState: false, preserveScroll: true },
        );
    },
    onColumnFiltersChange: (updaterOrValue) => {
        if (typeof updaterOrValue === 'function') {
            columnFilters.value = updaterOrValue(columnFilters.value);
        } else {
            columnFilters.value = updaterOrValue;
        }

        let filters: Record<string, any> = {};
        if (columnFilters.value && columnFilters.value.length > 0) {
            filters = columnFilters.value.reduce((acc: Record<string, any>, filter) => {
                if (Array.isArray(filter.value) && filter.value.length > 0) {
                    acc[filter.id] = filter.value;
                } else if (!Array.isArray(filter.value) && filter.value !== '' && filter.value !== null && filter.value !== undefined) {
                    acc[filter.id] = filter.value;
                }
                return acc;
            }, {});
        }

        router.get(
            route('logger.index'),
            {
                page: 1,
                per_page: pagination.value.pageSize,
                sort_field: sorting.value[0]?.id,
                sort_direction: sorting.value.length == 0 ? undefined : sorting.value[0]?.desc ? 'desc' : 'asc',
                search: filters.search,
                visit_purpose_id: filters.visit_purpose_id,
            },
            { preserveState: false, preserveScroll: true },
        );
    },
    onColumnVisibilityChange: (updaterOrValue) => {
        if (typeof updaterOrValue === 'function') {
            columnVisibility.value = updaterOrValue(columnVisibility.value);
        } else {
            columnVisibility.value = updaterOrValue;
        }
    },
    onRowSelectionChange: (updaterOrValue) => valueUpdater(updaterOrValue, rowSelection),
    onExpandedChange: (updaterOrValue) => valueUpdater(updaterOrValue, expanded),
    state: {
        get sorting() {
            return sorting.value;
        },
        get columnFilters() {
            return columnFilters.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get rowSelection() {
            return rowSelection.value;
        },
        get expanded() {
            return expanded.value;
        },
        get pagination() {
            return pagination.value;
        },
    },
});

const filterInput = ref<string>((table.getColumn('search')?.getFilterValue() as string) ?? '');
const applyFilter = () => {
    table.getColumn('search')?.setFilterValue(filterInput.value);
};
const clearFilter = () => {
    filterInput.value = '';
    table.getColumn('search')?.setFilterValue('');
};

import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import Filter from './Filter.vue';

// Filter for visit purpose
const filter_purposes = {
    title: 'Filter Purpose',
    column: 'visit_purpose_id',
    data: props.availablePurposes.map((purpose) => ({
        value: purpose.value,
        label: purpose.label,
        icon: h(ListFilter),
    })),
};

const filter_toolbar = [filter_purposes];

const showDialog = ref(false);
const showDialogCreate = () => {
    showDialog.value = true;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Logger',
        href: '/logger',
    },
];

import { computed } from 'vue';

// Dropdown + filters
const showDownload = ref(false);
const activeFilter = ref('day');

// Program selections
const selectedVisitProgram = ref('all');
const selectedLibraryProgram = ref('all');

// Fake data
const libraryData = {
    all: 128,
    BSIT: 52,
    BSABE: 38,
    BSNED: 15,
    BSED: 23,
};

const visitData = {
    all: 45,
    BSIT: 20,
    BSABE: 15,
    BSNED: 10,
    BSED: 5,
};

const filters = [
    { label: 'Day', value: 'day' },
    { label: 'Week', value: 'week' },
    { label: 'Month', value: 'month' },
    { label: 'Custom', value: 'custom' },
];

function toggleDropdown() {
    showDownload.value = !showDownload.value;
}

function setFilter(filter) {
    activeFilter.value = filter;
}

function download(type) {
    alert(`Downloading ${type}...`);
}

// Computed for "Currently in Library"
const currentLibraryCount = computed(() => {
    return libraryData[selectedLibraryProgram.value] ?? libraryData.all;
});

// Computed for "Visits Today"
const visitCount = computed(() => {
    return visitData[selectedVisitProgram.value] ?? visitData.all;
});

const visitTitle = computed(() => {
    const filterLabel = activeFilter.value === 'day' ? 'Today' : activeFilter.value.charAt(0).toUpperCase() + activeFilter.value.slice(1);

    if (selectedVisitProgram.value === 'all') {
        return activeFilter.value === 'day' ? 'Visits Today' : `Visits (${filterLabel})`;
    } else {
        return activeFilter.value === 'day' ? `${selectedVisitProgram.value} Visits` : `${selectedVisitProgram.value} Visits (${filterLabel})`;
    }
});

// Tab state
const activeTab = ref<'all-logs' | 'logout-by-system'>('all-logs');

// Static demo data
const violations = ref([
    {
        studentId: '2021001',
        name: 'Juan Dela Cruz',
        course: 'BSIT',
        logoutTime: '8:45 PM',
        violation: 'Auto Logout after hours',
    },
    {
        studentId: '2021042',
        name: 'Maria Santos',
        course: 'BSABE',
        logoutTime: '9:10 PM',
        violation: 'Auto Logout after hours',
    },
    {
        studentId: '2021089',
        name: 'Pedro Reyes',
        course: 'BSNED',
        logoutTime: '8:30 PM',
        violation: 'Auto Logout after hours',
    },
    {
        studentId: '2021056',
        name: 'Yahzee Jon',
        course: 'BSED',
        logoutTime: '8:21 PM',
        violation: 'Auto Logout after hours',
    },
]);
</script>

<template>
    <Head title="Borrowings" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="w-full">
                <!-- Visits Card Section -->
                <div class="mb-6 flex flex-row flex-wrap gap-4">
                    <div class="flex flex-1 gap-4">
                        <!-- Currently in Library Card -->
                        <div class="flex-1 rounded-2xl border-2 border-[#800000] bg-white p-3 shadow-lg">
                            <div class="mb-1 flex items-center justify-between">
                                <span class="text-sm font-bold text-[#800000]">Currently in Library</span>

                                <!-- Program dropdown -->
                                <select
                                    v-model="selectedLibraryProgram"
                                    class="rounded border border-[#FFD700] px-2 py-0.5 text-xs outline-none focus:ring-1 focus:ring-[#FFD700]"
                                >
                                    <option value="all">All Programs</option>
                                    <option value="BSIT">BSIT</option>
                                    <option value="BSABE">BSABE</option>
                                    <option value="BSNED">BSNED</option>
                                    <option value="BSED">BSED</option>
                                </select>
                            </div>

                            <!-- Centered larger number -->
                            <div class="flex items-center justify-center py-1">
                                <span
                                    class="flex h-14 w-14 items-center justify-center rounded-full bg-[#FFD700]/20 text-3xl font-extrabold text-[#800000]"
                                >
                                    {{ currentLibraryCount }}
                                </span>
                            </div>
                        </div>

                        <!-- Visits Today Card -->
                        <div class="flex-1 rounded-2xl border-2 border-[#FFD700] bg-white p-3 shadow-lg">
                            <div class="mb-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-[#B8860B]">
                                        {{ visitTitle }}
                                    </span>
                                    <span class="rounded bg-[#800000]/30 px-2 py-0.5 text-sm font-bold text-[#FFD700]">
                                        {{ visitCount }}
                                    </span>
                                </div>

                                <!-- Download dropdown -->
                                <div class="relative">
                                    <button
                                        @click="toggleDropdown"
                                        class="flex h-7 items-center rounded bg-[#FFD700] px-2 py-0.5 text-xs text-[#800000] hover:bg-[#B8860B] hover:text-white"
                                    >
                                        Download
                                        <span class="ml-1">▼</span>
                                    </button>
                                    <div
                                        v-if="showDownload"
                                        class="absolute right-0 mt-1 w-36 rounded border border-gray-200 bg-white text-sm shadow-lg"
                                    >
                                        <button @click="download('CSV')" class="block w-full px-3 py-1.5 text-left hover:bg-gray-100">
                                            Download CSV
                                        </button>
                                        <button @click="download('Excel')" class="block w-full px-3 py-1.5 text-left hover:bg-gray-100">
                                            Download Excel
                                        </button>
                                        <button @click="download('PDF')" class="block w-full px-3 py-1.5 text-left hover:bg-gray-100">
                                            Download PDF
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <div class="flex flex-wrap gap-1">
                                    <button
                                        v-for="option in filters"
                                        :key="option.value"
                                        @click="setFilter(option.value)"
                                        class="rounded px-1.5 py-0.5 text-xs"
                                        :class="
                                            activeFilter === option.value
                                                ? 'bg-[#FFD700] font-semibold text-[#800000]'
                                                : 'bg-[#FFD700]/20 text-[#B8860B]'
                                        "
                                    >
                                        {{ option.label }}
                                    </button>
                                </div>

                                <!-- Program dropdown -->
                                <select
                                    v-model="selectedVisitProgram"
                                    class="ml-auto rounded border border-[#FFD700] px-2 py-0.5 text-xs outline-none focus:ring-1 focus:ring-[#FFD700]"
                                >
                                    <option value="all">All Programs</option>
                                    <option value="BSIT">BSIT</option>
                                    <option value="BSABE">BSABE</option>
                                    <option value="BSNED">BSNED</option>
                                    <option value="BSED">BSED</option>
                                </select>
                            </div>

                            <!-- Show only if "custom" selected -->
                            <div v-if="activeFilter === 'custom'" class="mt-2 flex items-center gap-2">
                                <input
                                    type="date"
                                    class="flex-1 rounded border border-[#FFD700] px-2 py-0.5 text-xs outline-none focus:ring-1 focus:ring-[#FFD700]"
                                />
                                <span class="text-xs text-[#B8860B]">to</span>
                                <input
                                    type="date"
                                    class="flex-1 rounded border border-[#FFD700] px-2 py-0.5 text-xs outline-none focus:ring-1 focus:ring-[#FFD700]"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Visits Card Section -->

                <!-- Tabs Section -->
                <div class="mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                            <button
                                @click="activeTab = 'all-logs'"
                                :class="[
                                    activeTab === 'all-logs'
                                        ? 'border-[#800000] text-[#800000]'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                    'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                                ]"
                            >
                                All Logs
                            </button>
                            <button
                                @click="activeTab = 'logout-by-system'"
                                :class="[
                                    activeTab === 'logout-by-system'
                                        ? 'border-[#800000] text-[#800000]'
                                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700',
                                    'border-b-2 px-1 py-2 text-sm font-medium whitespace-nowrap',
                                ]"
                            >
                                Logout by System
                            </button>
                        </nav>
                    </div>
                </div>
                <!-- End Tabs Section -->

                <div v-if="activeTab === 'all-logs'">
                    <div class="flex items-center justify-between gap-2 py-4">
                        <div class="flex gap-2">
                            <div class="relative">
                                <Input
                                    class="w-[320px] pr-8"
                                    placeholder="Search by ID or Client..."
                                    v-model="filterInput"
                                    @keyup.enter="applyFilter"
                                    @blur="applyFilter"
                                />
                                <Button v-if="filterInput" variant="ghost" class="absolute top-0 right-0 h-full px-2" @click="clearFilter">
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                            <div v-for="filter in filter_toolbar" :key="filter.title">
                                <Filter :column="table.getColumn(filter.column)" :title="filter.title" :options="filter.data"></Filter>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Button variant="outline" @click="showDialogCreate">
                                <Plus class="h-4"></Plus>
                                Create New
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="outline" class="ml-auto">
                                        Columns
                                        <ChevronDown class="ml-2 h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuCheckboxItem
                                        v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
                                        :key="column.id"
                                        class="capitalize"
                                        :checked="column.getIsVisible()"
                                        @update:checked="
                                            (value: boolean | 'indeterminate') => {
                                                column.toggleVisibility(!!value);
                                            }
                                        "
                                    >
                                        {{ column.id }}
                                    </DropdownMenuCheckboxItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    <div class="rounded-md border">
                        <Table class="w-full">
                            <TableHeader>
                                <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                    <TableHead v-for="header in headerGroup.headers" :key="header.id">
                                        <FlexRender
                                            v-if="!header.isPlaceholder"
                                            :render="header.column.columnDef.header"
                                            :props="header.getContext()"
                                        />
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="table.getRowModel().rows?.length">
                                    <template v-for="row in table.getRowModel().rows" :key="row.id">
                                        <TableRow :data-state="row.getIsSelected() && 'selected'">
                                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="row.getIsExpanded()">
                                            <TableCell :colspan="row.getAllCells().length">
                                                {{ JSON.stringify(row.original) }}
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                </template>
                                <TableRow v-else>
                                    <TableCell :colspan="columns.length" class="h-24 text-center"> No results. </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <div class="flex items-center justify-end space-x-2 py-4">
                        <div class="flex-1 text-sm text-muted-foreground">
                            {{ table.getFilteredSelectedRowModel().rows.length }} of {{ table.getFilteredRowModel().rows.length }} row(s) selected.
                        </div>
                        <div class="flex items-center space-x-2">
                            <p class="text-sm font-medium">Rows per page</p>
                            <Select
                                :model-value="table.getState().pagination.pageSize.toString()"
                                @update:model-value="(value) => table.setPageSize(Number(value))"
                            >
                                <SelectTrigger class="h-8 w-[70px]">
                                    <SelectValue :placeholder="table.getState().pagination.pageSize.toString()" />
                                </SelectTrigger>
                                <SelectContent side="top">
                                    <SelectItem v-for="pageSize in pageSizes" :key="pageSize" :value="pageSize.toString()">
                                        {{ pageSize }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-x-2">
                            <div class="flex items-center space-x-2">
                                <Button
                                    variant="outline"
                                    class="hidden h-8 w-8 p-0 lg:flex"
                                    :disabled="!table.getCanPreviousPage()"
                                    @click="table.setPageIndex(0)"
                                >
                                    <DoubleArrowLeftIcon class="h-4 w-4" />
                                </Button>
                                <Button variant="outline" class="h-8 w-8 p-0" :disabled="!table.getCanPreviousPage()" @click="table.previousPage()">
                                    <ChevronLeftIcon class="h-4 w-4" />
                                </Button>
                                <Button variant="outline" class="h-8 w-8 p-0" :disabled="!table.getCanNextPage()" @click="table.nextPage()">
                                    <ChevronRightIcon class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="outline"
                                    class="hidden h-8 w-8 p-0 lg:flex"
                                    :disabled="!table.getCanNextPage()"
                                    @click="table.setPageIndex(table.getPageCount() - 1)"
                                >
                                    <DoubleArrowRightIcon class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="activeTab === 'logout-by-system'">
                    <!-- Logout by System Tab Content -->
                    <div class="mb-4">
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Library Hours: 8:00 AM - 8:00 PM</h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>
                                            Users who remain logged in after 8:00 PM will be automatically logged out by the system and recorded as
                                            violations.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Static Table -->
                    <div class="rounded-md border">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-3 py-2 text-left">Student ID</th>
                                    <th class="px-3 py-2 text-left">Name</th>
                                    <th class="px-3 py-2 text-left">Course/Program</th>
                                    <th class="px-3 py-2 text-left">Logout Time</th>
                                    <th class="px-3 py-2 text-left">Violation</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="violations.length === 0">
                                    <td colspan="5" class="h-24 text-center text-gray-500">No system logout violations found.</td>
                                </tr>
                                <tr v-for="(item, index) in violations" :key="index" class="border-t">
                                    <td class="px-3 py-2">{{ item.studentId }}</td>
                                    <td class="px-3 py-2">{{ item.name }}</td>
                                    <td class="px-3 py-2">{{ item.course }}</td>
                                    <td class="px-3 py-2">{{ item.logoutTime }}</td>
                                    <td class="px-3 py-2 font-semibold text-red-600">
                                        {{ item.violation }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
