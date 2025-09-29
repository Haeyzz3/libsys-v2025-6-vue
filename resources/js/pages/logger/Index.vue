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
import { h, ref, computed, watch } from 'vue';
import { format, parseISO } from 'date-fns';
import { route } from 'ziggy-js';
import DropdownAction from './DataTableDemoColumn.vue';
import axios from 'axios';
import type { Column, ColumnDef, Row, SortingState, Table, ColumnFiltersState } from '@tanstack/vue-table';

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

// Dropdown + filters
const showDownload = ref(false);
const activeFilter = ref<'day' | 'week' | 'month' | 'custom'>('day');
// Custom date range
const customFrom = ref<string | null>(null);
const customTo = ref<string | null>(null);

// Program selections
const selectedVisitProgram = ref('all');
const selectedLibraryProgram = ref('all');

// Dynamic program list & stats (replacing static demo data)
const visitPrograms = ref<{ code: string; name: string }[]>([]);
const currentlyInLibraryMap = ref<Record<string, number>>({ all: 0 });
const visitsMap = ref<Record<string, number>>({ all: 0 });
const statsLoading = ref(false);
const statsError = ref<string | null>(null);

// Filters for Visits Card Section
const filters = [
    { label: 'Day', value: 'day' },
    { label: 'Week', value: 'week' },
    { label: 'Month', value: 'month' },
    { label: 'Custom', value: 'custom' },
];

const period = ref<{ filter: string; start: string; end: string } | null>(null);

async function fetchVisitCardStats() {
    statsLoading.value = true;
    statsError.value = null;
    try {
        const params: any = { filter: activeFilter.value };
        if (activeFilter.value === 'custom') {
            if (!customFrom.value || !customTo.value) {
                statsLoading.value = false; // wait for both dates
                return;
            }
            params.custom_from = customFrom.value;
            params.custom_to = customTo.value;
        }
        const { data } = await axios.get(route('logger.api.visitCardStats'), { params });
        if (data.success) {
            visitPrograms.value = data.data.programs;
            currentlyInLibraryMap.value = data.data.currently_in_library;
            visitsMap.value = data.data.visits;
            period.value = data.data.period;
            const codes = visitPrograms.value.map((p) => p.code);
            if (selectedVisitProgram.value !== 'all' && !codes.includes(selectedVisitProgram.value)) selectedVisitProgram.value = 'all';
            if (selectedLibraryProgram.value !== 'all' && !codes.includes(selectedLibraryProgram.value)) selectedLibraryProgram.value = 'all';
        } else {
            statsError.value = 'Failed to load visit statistics';
        }
    } catch (e: any) {
        statsError.value = e?.response?.data?.message || e.message || 'Error loading statistics';
    } finally {
        statsLoading.value = false;
    }
}

// Added back: filter change handler (was removed inadvertently)
function setFilter(filter: string) {
    if (activeFilter.value === filter) return;
    activeFilter.value = filter as any;
    if (filter !== 'custom') {
        customFrom.value = null;
        customTo.value = null;
        fetchVisitCardStats();
    } else {
        // Clear current period until both dates set
        period.value = null;
    }
}

// Initial load
fetchVisitCardStats();

// Remove redundant activeFilter watcher (setFilter handles non-custom fetches)
// Custom range watcher remains to trigger fetch only after both dates selected
watch([customFrom, customTo], () => {
    if (activeFilter.value === 'custom' && customFrom.value && customTo.value) {
        fetchVisitCardStats();
    }
});

// Computed for "Currently in Library" (dynamic)
const currentLibraryCount = computed(() => {
    if (selectedLibraryProgram.value === 'all') {
        return currentlyInLibraryMap.value.all ?? 0;
    }
    return currentlyInLibraryMap.value[selectedLibraryProgram.value] ?? 0;
});

// Computed for "Visits" (dynamic)
const visitCount = computed(() => {
    if (selectedVisitProgram.value === 'all') {
        return visitsMap.value.all ?? 0;
    }
    return visitsMap.value[selectedVisitProgram.value] ?? 0;
});

const visitsTitle = computed(() => {
    const baseProgram = selectedVisitProgram.value === 'all' ? 'Visits' : `${selectedVisitProgram.value} Visits`;
    if (activeFilter.value === 'day') {
        return selectedVisitProgram.value === 'all' ? 'Visits Today' : `${selectedVisitProgram.value} Visits`;
    }
    if (activeFilter.value === 'custom') {
        if (customFrom.value && customTo.value) {
            return `${baseProgram} (${customFrom.value} → ${customTo.value})`;
        }
        return `${baseProgram} (Custom Range)`;
    }
    if (period.value && period.value.start && period.value.end) {
        const start = parseISO(period.value.start);
        const end = parseISO(period.value.end);
        const sameMonth = start.getMonth() === end.getMonth();
        const sameYear = start.getFullYear() === end.getFullYear();
        let rangeLabel = '';
        if (sameMonth && sameYear) rangeLabel = `${format(start, 'MMM d')}–${format(end, 'd')}`;
        else if (sameYear) rangeLabel = `${format(start, 'MMM d')}–${format(end, 'MMM d')}`;
        else rangeLabel = `${format(start, 'MMM d, yyyy')}–${format(end, 'MMM d, yyyy')}`;
        const filterLabel = activeFilter.value === 'week' ? 'Week' : 'Month';
        return `${baseProgram} (${filterLabel}: ${rangeLabel})`;
    }
    const filterLabel = activeFilter.value.charAt(0).toUpperCase() + activeFilter.value.slice(1);
    return `${baseProgram} (${filterLabel})`;
});
const visitTitle = visitsTitle; // alias for template compatibility

// Tab state
const activeTab = ref<'all-logs' | 'logout-by-system'>('all-logs');

// System Logout Violations dynamic data
const systemViolations = ref<any[]>([]);
const systemLoading = ref(false);
const systemError = ref<string|null>(null);
const systemPage = ref(1);
const systemPerPage = ref(10);
const systemTotal = ref(0);
const systemLastPage = ref(1);
const systemSearch = ref('');
const systemFetchedOnce = ref(false);
const systemTodayHours = ref<{open:string;close:string}|null>(null);
const systemPerPageOptions = [5,10,15,25,50];
let systemSearchDebounce: any = null;

function buildSystemParams(extra: Record<string, any> = {}) {
    const params: Record<string, any> = {
        page: systemPage.value,
        per_page: systemPerPage.value,
        filter: activeFilter.value,
        program: selectedVisitProgram.value,
        search: systemSearch.value || undefined,
    };
    if (activeFilter.value === 'custom' && customFrom.value && customTo.value) {
        params.custom_from = customFrom.value;
        params.custom_to = customTo.value;
    }
    return { ...params, ...extra };
}

async function fetchSystemViolations(force = false) {
    if (activeTab.value !== 'logout-by-system') return;
    if (!force && activeFilter.value === 'custom' && (!customFrom.value || !customTo.value)) {
        return; // wait until both dates set
    }
    systemLoading.value = true;
    systemError.value = null;
    try {
        const { data } = await axios.get(route('logger.api.systemLogoutViolations'), { params: buildSystemParams() });
        if (data.success) {
            systemViolations.value = data.data.rows;
            systemPage.value = data.data.pagination.current_page;
            systemPerPage.value = data.data.pagination.per_page;
            systemTotal.value = data.data.pagination.total;
            systemLastPage.value = data.data.pagination.last_page;
            systemTodayHours.value = data.data.today_hours || null;
            systemFetchedOnce.value = true;
        } else {
            systemError.value = 'Failed to load system logout violations';
        }
    } catch (e: any) {
        systemError.value = e?.response?.data?.message || e.message || 'Error loading violations';
    } finally {
        systemLoading.value = false;
    }
}

function systemGoToPage(p: number) {
    if (p < 1 || p > systemLastPage.value) return;
    systemPage.value = p;
    fetchSystemViolations(true);
}
function systemChangePerPage(size: number) {
    systemPerPage.value = size;
    systemPage.value = 1;
    fetchSystemViolations(true);
}
function onSystemSearchInput() {
    clearTimeout(systemSearchDebounce);
    systemSearchDebounce = setTimeout(() => {
        systemPage.value = 1;
        fetchSystemViolations(true);
    }, 400);
}

watch(activeTab, (val) => {
    if (val === 'logout-by-system' && !systemFetchedOnce.value) {
        fetchSystemViolations(true);
    }
});
watch([activeFilter, customFrom, customTo, selectedVisitProgram], () => {
    if (activeTab.value === 'logout-by-system') {
        systemPage.value = 1;
        fetchSystemViolations(true);
    }
});

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
                        <div class="flex-1 rounded-2xl border-2 border-[#800000] bg-white p-3 shadow-lg relative">
                            <div v-if="statsLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 text-xs font-medium">Loading...</div>
                            <div class="mb-1 flex items-center justify-between">
                                <span class="text-sm font-bold text-[#800000]">Currently in Library</span>
                                <select
                                    v-model="selectedLibraryProgram"
                                    class="rounded border border-[#FFD700] px-2 py-0.5 text-xs outline-none focus:ring-1 focus:ring-[#FFD700]"
                                >
                                    <option value="all">All Programs</option>
                                    <option v-for="p in visitPrograms" :key="p.code" :value="p.code">{{ p.code }}</option>
                                </select>
                            </div>
                            <div class="flex items-center justify-center py-1">
                                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-[#FFD700]/20 text-3xl font-extrabold text-[#800000]">
                                    {{ currentLibraryCount }}
                                </span>
                            </div>
                            <p v-if="statsError" class="mt-1 text-center text-[10px] text-red-600">{{ statsError }}</p>
                        </div>

                        <!-- Visits Today / Period Card -->
                        <div class="flex-1 rounded-2xl border-2 border-[#FFD700] bg-white p-3 shadow-lg relative">
                            <div v-if="statsLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 text-xs font-medium">Loading...</div>
                            <div class="mb-2 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-[#B8860B]">
                                        {{ visitTitle }}
                                    </span>
                                    <span class="rounded bg-[#800000]/30 px-2 py-0.5 text-sm font-bold text-[#FFD700]">
                                        {{ visitCount }}
                                    </span>
                                </div>
                                <div class="relative">
                                    <button
                                        @click="toggleDropdown"
                                        class="flex h-7 items-center rounded bg-[#FFD700] px-2 py-0.5 text-xs text-[#800000] hover:bg-[#B8860B] hover:text-white"
                                    >
                                        Download
                                        <span class="ml-1">▼</span>
                                    </button>
                                    <div v-if="showDownload" class="absolute right-0 mt-1 w-36 rounded border border-gray-200 bg-white text-sm shadow-lg">
                                        <button @click="download('CSV')" class="block w-full px-3 py-1.5 text-left hover:bg-gray-100">Download CSV</button>
                                        <button @click="download('Excel')" class="block w-full px-3 py-1.5 text-left hover:bg-gray-100">Download Excel</button>
                                        <button @click="download('PDF')" class="block w-full px-3 py-1.5 text-left hover:bg-gray-100">Download PDF</button>
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
                                        :class="activeFilter === option.value ? 'bg-[#FFD700] font-semibold text-[#800000]' : 'bg-[#FFD700]/20 text-[#B8860B]'"
                                    >
                                        {{ option.label }}
                                    </button>
                                </div>
                                <select
                                    v-model="selectedVisitProgram"
                                    class="ml-auto rounded border border-[#FFD700] px-2 py-0.5 text-xs outline-none focus:ring-1 focus:ring-[#FFD700]"
                                >
                                    <option value="all">All Programs</option>
                                    <option v-for="p in visitPrograms" :key="p.code" :value="p.code">{{ p.code }}</option>
                                </select>
                            </div>
                            <div v-if="activeFilter === 'custom'" class="mt-2 flex items-center gap-2">
                                <input type="date" v-model="customFrom" class="flex-1 rounded border border-[#FFD700] px-2 py-0.5 text-xs outline-none focus:ring-1 focus:ring-[#FFD700]" />
                                <span class="text-xs text-[#B8860B]">to</span>
                                <input type="date" v-model="customTo" :min="customFrom || undefined" class="flex-1 rounded border border-[#FFD700] px-2 py-0.5 text-xs outline-none focus:ring-1 focus:ring-[#FFD700]" />
                            </div>
                            <p v-if="statsError" class="mt-1 text-center text-[10px] text-red-600">{{ statsError }}</p>
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

                <!-- All Logs Tab Contents -->
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
                <!-- End All Logs Tab Contents -->

                <!-- Logout by System Tab Content -->
                <div v-else-if="activeTab === 'logout-by-system'">
                    <div class="mb-4">
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 relative">
                            <div v-if="systemLoading && !systemFetchedOnce" class="absolute inset-0 flex items-center justify-center bg-white/60 text-xs font-medium">Loading...</div>
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3 w-full">
                                    <h3 class="text-sm font-medium text-yellow-800">
                                        Library Hours
                                        <span v-if="systemTodayHours" class="font-semibold">: {{ systemTodayHours.open }} - {{ systemTodayHours.close }}</span>
                                        <span v-else>: (Unavailable)</span>
                                    </h3>
                                    <div class="mt-2 text-sm text-yellow-700">
                                        <p>
                                            Users who remain logged in past closing time are flagged as automatic logout violations.
                                        </p>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2 items-center">
                                        <div class="relative">
                                            <input
                                                type="text"
                                                v-model="systemSearch"
                                                @input="onSystemSearchInput"
                                                placeholder="Search ID / Name"
                                                class="rounded border border-yellow-300 px-2 py-1 text-xs outline-none focus:ring-1 focus:ring-yellow-500"
                                            />
                                            <button v-if="systemSearch" @click="systemSearch=''; onSystemSearchInput();" class="absolute right-1 top-1 text-xs text-gray-500">×</button>
                                        </div>
                                        <select
                                            v-model.number="systemPerPage"
                                            @change="systemChangePerPage(Number(systemPerPage))"
                                            class="rounded border border-yellow-300 px-2 py-1 text-xs outline-none focus:ring-1 focus:ring-yellow-500"
                                        >
                                            <option v-for="n in systemPerPageOptions" :key="n" :value="n">{{ n }} / page</option>
                                        </select>
                                        <div class="flex gap-1 items-center text-xs font-medium">
                                            <button class="px-2 py-1 rounded border" :disabled="systemPage===1 || systemLoading" @click="systemGoToPage(1)">«</button>
                                            <button class="px-2 py-1 rounded border" :disabled="systemPage===1 || systemLoading" @click="systemGoToPage(systemPage-1)">‹</button>
                                            <span>Page {{ systemPage }} / {{ systemLastPage }}</span>
                                            <button class="px-2 py-1 rounded border" :disabled="systemPage===systemLastPage || systemLoading" @click="systemGoToPage(systemPage+1)">›</button>
                                            <button class="px-2 py-1 rounded border" :disabled="systemPage===systemLastPage || systemLoading" @click="systemGoToPage(systemLastPage)">»</button>
                                        </div>
                                        <div class="ml-auto text-xs text-yellow-700" v-if="systemTotal">Total: {{ systemTotal }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Table -->
                    <div class="rounded-md border relative">
                        <div v-if="systemLoading" class="absolute inset-0 flex items-center justify-center bg-white/60 text-sm">Loading...</div>
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
                                <tr v-if="systemError && !systemLoading">
                                    <td colspan="5" class="h-24 text-center text-red-600">{{ systemError }}</td>
                                </tr>
                                <tr v-else-if="!systemLoading && systemViolations.length === 0">
                                    <td colspan="5" class="h-24 text-center text-gray-500">No system logout violations found.</td>
                                </tr>
                                <tr v-for="(item, index) in systemViolations" :key="index" class="border-t">
                                    <td class="px-3 py-2">{{ item.studentId }}</td>
                                    <td class="px-3 py-2">{{ item.name }}</td>
                                    <td class="px-3 py-2">{{ item.course }}</td>
                                    <td class="px-3 py-2">{{ item.logoutTime }}</td>
                                    <td class="px-3 py-2 font-semibold text-red-600">{{ item.violation }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- End Logout by System Tab Content -->
            </div>
        </div>
    </AppLayout>
</template>
