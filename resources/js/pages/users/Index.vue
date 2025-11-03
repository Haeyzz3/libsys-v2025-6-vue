<script setup lang="ts">
// Imports
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import Layout from '@/layouts/users/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from '@radix-icons/vue';
import type { Column, ColumnDef, ColumnFiltersState, SortingState, VisibilityState } from '@tanstack/vue-table';
import {
    FlexRender,
    getCoreRowModel,
    getExpandedRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table';
import { ArrowUpDown, ChevronDown, X, Loader2, Eye, Search, Plus } from 'lucide-vue-next';
import { DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuRoot, DropdownMenuTrigger } from 'radix-vue';
import { h, ref, onMounted, watch, nextTick, computed } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Badge } from '@/components/ui/badge';

// API Data Interface
interface ApiResponse {
    data: any[];
    current_page: number;
    per_page: number;
    last_page: number;
    total: number;
}

// Reactive data state
const data = ref<any[]>([]);
const isLoading = ref(false);
const currentPage = ref(1);
const perPage = ref(10);
const lastPage = ref(1);
const total = ref(0);
const error = ref<string | null>(null);

// Scroll position preservation
const scrollPosition = ref(0);

// Search input ref for focus preservation
const searchInputRef = ref(null);

// Show handler function
const isDialogOpen = ref(false);
const selectedUser = ref<any | null>(null);

const handleShow = (user: any) => {
    selectedUser.value = user;
    isDialogOpen.value = true;
};

const handleEdit = (id) => {
    router.get(route('users.edit', id));
};

// Table columns definition
const columns: ColumnDef<any>[] = [
    {
        accessorKey: 'library_id',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Lib ID', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('library_id')),
        enableHiding: false,
    },
    {
        accessorKey: 'card_number',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Card #', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('card_number')),
    },
    {
        accessorKey: 'school_id',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Scl ID', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase' }, row.getValue('school_id')),
    },
    {
        accessorKey: 'first_name',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['First Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('first_name')),
        enableHiding: false,
    },
    {
        accessorKey: 'middle_initial',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['M.I', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('middle_initial') ? row.getValue('middle_initial') + '.' : ''),
    },
    {
        accessorKey: 'last_name',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Last Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'capitalize' }, row.getValue('last_name')),
        enableHiding: false,
    },
    {
        accessorKey: 'sex',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Sex', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const sex = row.getValue('sex');
            const displayValue = sex === 'm' ? 'Male' : sex === 'f' ? 'Female' : sex;
            return h('div', displayValue);
        },
    },
    {
        accessorKey: 'email',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['Email', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => h('div', { class: 'lowercase max-w-52 truncate' }, row.getValue('email')),
        enableHiding: false,
    },
    {
        accessorKey: 'user_type',
        header: ({ column }) =>
            h(Button, { variant: 'ghost', onClick: () => cycleSort(column) }, () => ['User Type', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })]),
        cell: ({ row }) => {
            const userType = row.getValue('user_type');
            return h('div', { class: 'capitalize' }, userType?.name || 'N/A');
        },
        enableHiding: true,
    },
    {
        id: 'action',
        header: 'Action',
        enableHiding: false,
        cell: ({ row }) =>
            h(Button,
                {
                    variant: 'outline',
                    size: 'sm',
                    onClick: () => handleShow(row.original),
                    class: 'flex items-center gap-2'
                },
                () => [
                    h(Eye, { class: 'h-4 w-4 text-muted-foreground' }),
                    'Show'
                ]
            )
    }
];

// Sorting helper
function cycleSort(column: Column<any, any>) {
    const currentSort = column.getIsSorted();
    if (currentSort === false) column.toggleSorting(false);
    else if (currentSort === 'asc') column.toggleSorting(true);
    else column.clearSorting();
}

// Table state
const sorting = ref<SortingState>([]);
const columnFilters = ref<ColumnFiltersState>([]);
const columnVisibility = ref<VisibilityState>({
    school_id: false,
    sex: false,
    middle_initial: false,
});
const expanded = ref({});
const pageSizes = [5, 10, 20, 30, 40, 50];
const pagination = ref({
    pageIndex: 0,
    pageSize: 10,
});

// Scroll position management functions
const saveScrollPosition = () => {
    scrollPosition.value = window.pageYOffset || document.documentElement.scrollTop;
};

const restoreScrollPosition = () => {
    nextTick(() => {
        window.scrollTo({
            top: scrollPosition.value,
            behavior: 'instant'
        });
    });
};

// Debounce utility
function debounce<T extends (...args: any[]) => any>(func: T, wait: number): T {
    let timeout: ReturnType<typeof setTimeout>;
    return ((...args: any[]) => {
        clearTimeout(timeout);
        // eslint-disable-next-line prefer-spread
        timeout = setTimeout(() => func.apply(null, args), wait);
    }) as T;
}

// API fetch function
const fetchData = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        // Build query parameters
        const params = new URLSearchParams();

        // Pagination
        params.append('page', (pagination.value.pageIndex + 1).toString());
        params.append('per_page', pagination.value.pageSize.toString());

        // Sorting
        if (sorting.value.length > 0) {
            params.append('sort_field', sorting.value[0].id);
            params.append('sort_direction', sorting.value[0].desc ? 'desc' : 'asc');
        }

        // Filters
        columnFilters.value.forEach(filter => {
            if (Array.isArray(filter.value) && filter.value.length > 0) {
                params.append(filter.id, filter.value.join(','));
            } else if (filter.value !== '' && filter.value !== null && filter.value !== undefined) {
                params.append(filter.id, filter.value.toString());
            }
        });

        // Column visibility
        Object.entries(columnVisibility.value).forEach(([key, value]) => {
            if (value === true) {
                params.append(`show_${key}`, '1');
            } else {
                params.append(`hide_${key}`, '1');
            }
        });

        // Make API request
        const response = await fetch(`/api/users?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result: ApiResponse = await response.json();

        // Update reactive data
        data.value = result.data || [];
        currentPage.value = result.current_page || 1;
        perPage.value = result.per_page || 10;
        lastPage.value = result.last_page || 1;
        total.value = result.total || 0;

        // Update pagination state to match API response
        pagination.value.pageIndex = (result.current_page || 1) - 1;
        pagination.value.pageSize = result.per_page || 10;

    } catch (err) {
        console.error('API fetch error:', err);
        error.value = err instanceof Error ? err.message : 'An error occurred while fetching data';
        data.value = [];
    } finally {
        isLoading.value = false;
    }
};

// Debounced fetch for immediate UI feedback
const debouncedFetch = debounce(fetchData, 300);

// Table instance
const table = useVueTable({
    get data() {
        return data.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    get pageCount() {
        return lastPage.value;
    },
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
    onPaginationChange: handlePaginationChange,
    onSortingChange: handleSortingChange,
    onColumnFiltersChange: handleFilterChange,
    onColumnVisibilityChange: handleColumnVisibilityChange,
    onExpandedChange: (updater) => {
        expanded.value = typeof updater === 'function' ? updater(expanded.value) : updater;
    },
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
        get expanded() {
            return expanded.value;
        },
        get pagination() {
            return pagination.value;
        },
    },
});

// Enhanced event handlers with scroll preservation
function handlePaginationChange(updater) {
    // Save current scroll position before pagination change
    saveScrollPosition();

    pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater;

    // Fetch data and restore scroll position after DOM update
    fetchData().then(() => {
        restoreScrollPosition();
    });
}

function handleSortingChange(updaterOrValue) {
    sorting.value = typeof updaterOrValue === 'function' ? updaterOrValue(sorting.value) : updaterOrValue;
    // Reset to first page when sorting changes
    pagination.value.pageIndex = 0;
    // Don't preserve scroll position for sorting - user expects to see top
    fetchData();
}

function handleFilterChange(updaterOrValue) {
    columnFilters.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnFilters.value) : updaterOrValue;
    // Reset to first page when filters change
    pagination.value.pageIndex = 0;
    // Don't preserve scroll position for filtering - user expects to see top
    debouncedFetch(); // Use debounced version for filters
}

function handleColumnVisibilityChange(updaterOrValue) {
    columnVisibility.value = typeof updaterOrValue === 'function' ? updaterOrValue(columnVisibility.value) : updaterOrValue;
    fetchData();
}

// Enhanced pagination navigation functions with scroll preservation
const goToFirstPage = () => {
    if (table.getCanPreviousPage() && !isLoading.value) {
        saveScrollPosition();
        table.setPageIndex(0);
        fetchData().then(() => {
            restoreScrollPosition();
        });
    }
};

const goToPreviousPage = () => {
    if (table.getCanPreviousPage() && !isLoading.value) {
        saveScrollPosition();
        table.previousPage();
        fetchData().then(() => {
            restoreScrollPosition();
        });
    }
};

const goToNextPage = () => {
    if (table.getCanNextPage() && !isLoading.value) {
        saveScrollPosition();
        table.nextPage();
        fetchData().then(() => {
            restoreScrollPosition();
        });
    }
};

const goToLastPage = () => {
    if (table.getCanNextPage() && !isLoading.value) {
        saveScrollPosition();
        table.setPageIndex(table.getPageCount() - 1);
        fetchData().then(() => {
            restoreScrollPosition();
        });
    }
};

const handlePageSizeChange = (value: string) => {
    if (!isLoading.value) {
        saveScrollPosition();
        table.setPageSize(Number(value));
        fetchData().then(() => {
            restoreScrollPosition();
        });
    }
};

// Search functionality
const filterInput = ref<string>('');

const applyFilter = () => {
    const newFilters = columnFilters.value.filter((f) => f.id !== 'search');
    if (filterInput.value.trim()) {
        newFilters.push({ id: 'search', value: filterInput.value.trim() });
    }
    table.setColumnFilters(newFilters);
};

const clearFilter = () => {
    filterInput.value = '';
    const newFilters = columnFilters.value.filter((f) => f.id !== 'search');
    table.setColumnFilters(newFilters);
};

// Debounced search - automatically triggers on input change
const debouncedApplyFilter = debounce(() => {
    applyFilter();
}, 300);

// Watch for search input changes with focus preservation
watch(filterInput, (newValue, oldValue) => {
    if (newValue !== oldValue) {
        // Store focus state before applying filter
        const hadFocus = document.activeElement === searchInputRef.value;

        debouncedApplyFilter();

        // Restore focus after next DOM update
        if (hadFocus) {
            nextTick(() => {
                searchInputRef.value?.focus();
            });
        }
    }
});

// Initialize URL parameters from current page URL
const initializeFromURL = () => {
    const urlParams = new URLSearchParams(window.location.search);

    // Initialize pagination
    const page = parseInt(urlParams.get('page') || '1');
    const perPageParam = parseInt(urlParams.get('per_page') || '10');
    pagination.value.pageIndex = page - 1;
    pagination.value.pageSize = perPageParam;

    // Initialize sorting
    const sortField = urlParams.get('sort_field');
    const sortDirection = urlParams.get('sort_direction');
    if (sortField) {
        sorting.value = [{ id: sortField, desc: sortDirection === 'desc' }];
    }

    // Initialize search filter
    const searchParam = urlParams.get('search');
    if (searchParam) {
        filterInput.value = searchParam;
        columnFilters.value = [{ id: 'search', value: searchParam }];
    }

    // Initialize column visibility
    urlParams.forEach((value, key) => {
        if (key.startsWith('show_')) {
            const columnKey = key.replace('show_', '');
            columnVisibility.value[columnKey] = value === '1';
        } else if (key.startsWith('hide_')) {
            const columnKey = key.replace('hide_', '');
            columnVisibility.value[columnKey] = value !== '1';
        }
    });
};

const userTypes = [
    { value: 'undergraduate_student', label: 'Undergraduate Student', color: 'bg-blue-100 text-blue-800' },
    { value: 'graduate_student', label: 'Graduate Student', color: 'bg-green-100 text-green-800' },
    { value: 'faculty', label: 'Faculty', color: 'bg-purple-100 text-purple-800' },
    { value: 'staff', label: 'Staff', color: 'bg-orange-100 text-orange-800' },
    { value: 'library_staff', label: 'Library Staff', color: 'bg-pink-100 text-pink-800' },
];

const getUserTypeInfo = (type: string) => {
    return userTypes.find((ut) => ut.value === type) || { value: type, label: type, color: 'bg-gray-100 text-gray-800' };
};

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'All', href: '/users/all' },
];

// Lifecycle
onMounted(() => {
    initializeFromURL();
    fetchData();
});

// Watch for external changes that might require refetch
watch(() => window.location.search, () => {
    initializeFromURL();
    fetchData();
});

// Add this import for the toggle button icon
import { EyeOff } from 'lucide-vue-next';

// Add this import for the data
import { useUserStatistics } from '@/composables/useUserStatistics';

// Carousel functionality
const showCarousel = ref(true)

// Use the composable to get real user statistics
const { statistics, loading } = useUserStatistics()

const carouselCards = computed(() => [
    {
        label: 'Total Patrons',
        value: statistics.value.total_patrons,
        color: 'border-[#800000]',
        bgColor: 'bg-[#800000]/10',
        textColor: 'text-[#800000]',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2a2 2 0 012-2h4a2 2 0 012 2z',
    },
    {
        label: 'Undergraduate Students',
        value: statistics.value.undergraduate_students,
        color: 'border-[#FFD700]',
        bgColor: 'bg-[#FFD700]/20',
        textColor: 'text-[#B8860B]',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2a2 2 0 012-2h4a2 2 0 012 2z',
    },
    {
        label: 'Graduate Students',
        value: statistics.value.graduate_students,
        color: 'border-[#800000]',
        bgColor: 'bg-[#800000]/10',
        textColor: 'text-[#800000]',
        icon: 'M12 14l9-5-9-5-9 5 9 5zm0 0v6m0 0H6m6 0h6',
    },
    {
        label: 'All Faculty',
        value: statistics.value.all_faculty,
        color: 'border-[#FFD700]',
        bgColor: 'bg-[#FFD700]/20',
        textColor: 'text-[#B8860B]',
        icon: 'M9 6V4a2 2 0 012-2h2a2 2 0 012 2v2m-8 0h12a2 2 0 012 2v2H3V8a2 2 0 012-2zm16 4v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6m16 0H3',
    },
    {
        label: 'All Staff',
        value: statistics.value.all_staff,
        color: 'border-[#800000]',
        bgColor: 'bg-[#800000]/10',
        textColor: 'text-[#800000]',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2a2 2 0 012-2h4a2 2 0 012 2z',
    },
    {
        label: 'All Library Staff',
        value: statistics.value.all_library_staff,
        color: 'border-[#FFD700]',
        bgColor: 'bg-[#FFD700]/20',
        textColor: 'text-[#B8860B]',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2a2 2 0 012-2h4a2 2 0 012 2z',
    }
]);

// Carousel dynamic width logic
const cardSetRef = ref<HTMLElement | null>(null)
const loopCardsRef = ref<HTMLElement | null>(null)
const animationDistance = ref(0)

function updateAnimationDistance() {
    nextTick(() => {
        if (cardSetRef.value && loopCardsRef.value) {
            const width = cardSetRef.value.offsetWidth
            animationDistance.value = width
            loopCardsRef.value.style.setProperty('--cards-loop-distance', width + 'px')
        }
    })
}

onMounted(() => {
    updateAnimationDistance()
})

watch(carouselCards, () => {
    updateAnimationDistance()
}, { deep: true })

</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <Layout>
            <div class="w-full">

                <!-- Error message -->
                <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    <p>{{ error }}</p>
                    <Button variant="outline" size="sm" @click="fetchData" class="mt-2">
                        Retry
                    </Button>
                </div>

                <div class="flex justify-end mb-2">
                    <button
                        @click="showCarousel = !showCarousel"
                        class="px-3 py-1 rounded-lg text-sm font-medium flex items-center gap-1"
                        :class="showCarousel ? 'bg-[#800000] text-white' : 'bg-[#FFD700] text-[#800000]'"
                    >
                        <EyeOff v-if="!showCarousel" class="h-4 w-4" />
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        {{ showCarousel ? 'Hide Cards' : 'Show Cards' }}
                    </button>
                </div>

                <div v-if="showCarousel" class="relative w-full overflow-x-hidden flex items-center mb-6" style="min-height: 100px;">
                    <div ref="loopCardsRef" class="loop-cards flex flex-row flex-nowrap gap-4 animate-cards-loop items-center" style="will-change: transform;">
                        <div ref="cardSetRef" class="flex flex-row flex-nowrap gap-4 items-center">
                            <div v-for="(card, index) in carouselCards" :key="index" class="flex-shrink-0 min-w-[220px] bg-white rounded-2xl shadow-lg p-3 border-2 flex items-center gap-3 hover:shadow-xl transition-shadow relative" :class="card.color">
                                <div class="flex items-center justify-center h-8 w-8 rounded-full" :class="card.bgColor">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" :class="card.textColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold mb-1 tracking-wide whitespace-nowrap" :class="card.textColor">{{ card.label }}</div>
                                    <div class="text-2xl font-extrabold" :class="card.textColor">{{ card.value }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-row flex-nowrap gap-4 items-center">
                            <div v-for="(card, index) in carouselCards" :key="'dup-' + index" class="flex-shrink-0 min-w-[220px] bg-white rounded-2xl shadow-lg p-3 border-2 flex items-center gap-3 hover:shadow-xl transition-shadow relative" :class="card.color">
                                <div class="flex items-center justify-center h-8 w-8 rounded-full" :class="card.bgColor">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" :class="card.textColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold mb-1 tracking-wide whitespace-nowrap" :class="card.textColor">{{ card.label }}</div>
                                    <div class="text-2xl font-extrabold" :class="card.textColor">{{ card.value }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 py-4">
                    <div class="flex gap-2">
                        <div class="relative">
                            <Input
                                ref="searchInputRef"
                                class="w-[380px] pr-8"
                                placeholder="Search by lib id, card #, first name, or last name ..."
                                v-model="filterInput"
                            />
                            <Button v-if="filterInput" variant="ghost" class="absolute top-0 right-0 h-full px-2" @click="clearFilter">
                                <X class="h-4 w-4" />
                            </Button>
                            <div v-else class="absolute top-0 right-0 h-full px-2 flex items-center justify-center pointer-events-none">
                                <Search class="h-4 w-4 text-foreground" />
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">

                            <Button variant="secondary">
                                <Plus class="w-4 h-4" /> Import Users
                            </Button>

                        <DropdownMenuRoot>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="ml-auto" :disabled="isLoading">
                                    Columns
                                    <ChevronDown class="ml-2 h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" class="z-50 min-w-[220px] rounded-md border border-gray-200 bg-white p-1 shadow-lg">
                                <DropdownMenuCheckboxItem
                                    v-for="column in table.getAllColumns().filter((col) => col.getCanHide())"
                                    :key="column.id"
                                    :checked="column.getIsVisible()"
                                    @update:checked="(value) => column.toggleVisibility(!!value)"
                                    class="relative flex cursor-pointer items-center rounded-sm py-1.5 pr-2 pl-8 text-sm outline-none select-none hover:bg-gray-100"
                                >
                                    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
                                        <svg v-if="column.getIsVisible()" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    {{ column.id }}
                                </DropdownMenuCheckboxItem>
                            </DropdownMenuContent>
                        </DropdownMenuRoot>
                    </div>
                </div>

                <div class="rounded-md border">
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                <TableHead v-for="header in headerGroup.headers" :key="header.id">
                                    <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header" :props="header.getContext()" />
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-if="table.getRowModel().rows?.length && !isLoading">
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

                            <TableRow v-else-if="isLoading">
                                <TableCell :colspan="columns.length" class="h-24 text-center">
                                    <div class="flex justify-center items-center">
                                        <Loader2 class="h-4 w-4 animate-spin mr-2" />
                                        Loading...
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-else>
                                <TableCell :colspan="columns.length" class="h-24 text-center">
                                    No results found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="flex items-center justify-end space-x-2 py-4">
                    <div class="flex-1 text-sm text-muted-foreground">
                        Showing page {{ currentPage }} of {{ lastPage }} in {{ total }} {{ total === 1 || total === 0 ? 'item' : 'items' }}.
                    </div>
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium">Rows per page</p>
                        <Select
                            :model-value="table.getState().pagination.pageSize.toString()"
                            @update:model-value="handlePageSizeChange"
                            :disabled="isLoading"
                        >
                            <SelectTrigger class="h-8 w-[80px]">
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
                                :disabled="!table.getCanPreviousPage() || isLoading"
                                @click="goToFirstPage"
                            >
                                <DoubleArrowLeftIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                class="h-8 w-8 p-0"
                                :disabled="!table.getCanPreviousPage() || isLoading"
                                @click="goToPreviousPage"
                            >
                                <ChevronLeftIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                class="h-8 w-8 p-0"
                                :disabled="!table.getCanNextPage() || isLoading"
                                @click="goToNextPage"
                            >
                                <ChevronRightIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                class="hidden h-8 w-8 p-0 lg:flex"
                                :disabled="!table.getCanNextPage() || isLoading"
                                @click="goToLastPage"
                            >
                                <DoubleArrowRightIcon class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <Dialog v-model:open="isDialogOpen">
                <DialogContent class="sm:max-w-4xl max-h-[95dvh] p-0 bg-background rounded-lg shadow-xl overflow-x-auto">
                    <!-- Header -->
                    <DialogHeader class="px-4 pt-4 pb-4 border-b">
                        <DialogTitle class="text-xl font-semibold text-foreground">
                            User Details
                            <Badge v-if="selectedUser?.user_type.key" :class="getUserTypeInfo(selectedUser.user_type.key).color + ' border-0'">
                                {{ getUserTypeInfo(selectedUser.user_type.key).label }}
                            </Badge>
                        </DialogTitle>
                    </DialogHeader>

                    <!-- Main Content -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 py-0 overflow-y-auto">
                        <!-- Profile Card -->
                        <div class="flex flex-col items-center gap-4 bg-card rounded-lg p-4 border">
                            <!-- Profile Image -->
                            <div class="relative">
                                <img
                                    v-if="selectedUser?.profile_image"
                                    :src="'/storage/profile_images/' + selectedUser.profile_image"
                                    alt="Profile Image"
                                    class="h-36 w-36 rounded-full object-cover border-4 border-background shadow-lg"
                                />
                                <div
                                    v-else
                                    class="h-36 w-36 rounded-full flex items-center justify-center bg-muted text-muted-foreground border-4 border-background shadow-lg"
                                >
                                    <span class="text-sm font-medium">No Image</span>
                                </div>
                            </div>

                            <!-- Barcode -->
                            <div class="flex flex-col items-center gap-2 w-full">
                                <div v-if="selectedUser?.barcode_path" class="w-full">
                                    <img
                                        :src="'/storage/' + selectedUser.barcode_path"
                                        alt="User Barcode"
                                        class="h-16 w-auto mx-auto border rounded shadow-sm"
                                    />
                                    <span class="text-xs text-muted-foreground mt-2 block text-center">
                            Barcode: {{ selectedUser.card_number }}
                        </span>
                                </div>
                                <div
                                    v-else
                                    class="h-16 w-full flex items-center justify-center bg-muted text-muted-foreground border rounded shadow-sm"
                                >
                                    <span class="text-xs font-medium">No Barcode</span>
                                </div>
                            </div>
                        </div>

                        <!-- Details Section -->
                        <div class="md:col-span-2 space-y-4">
                            <div v-if="selectedUser" class="space-y-4">
                                <!-- Personal Information -->
                                <div class="bg-card rounded-lg p-5 border">
                                    <h3 class="font-semibold text-lg mb-4 text-foreground">Personal Information</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                        <p><strong class="text-foreground">Library ID:</strong> {{ selectedUser.library_id }}</p>
                                        <p><strong class="text-foreground">Card #:</strong> {{ selectedUser.card_number }}</p>
                                        <p><strong class="text-foreground">School ID:</strong> {{ selectedUser.school_id }}</p>
                                        <p>
                                            <strong class="text-foreground">Name:</strong>
                                            {{ selectedUser.first_name }} {{ selectedUser.middle_initial ? selectedUser.middle_initial + '.' : '' }} {{ selectedUser.last_name }}
                                        </p>
                                        <p><strong class="text-foreground">Email:</strong> {{ selectedUser.email }}</p>
                                        <p><strong class="text-foreground">Contact:</strong> {{ selectedUser.contact_number || 'Not provided' }}</p>
                                        <p><strong class="text-foreground">Sex:</strong> {{ selectedUser.sex === 'm' ? 'Male' : selectedUser.sex === 'f' ? 'Female' : selectedUser.sex }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-muted-foreground p-4 text-center">
                                <p class="text-sm">No user selected.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <DialogFooter class="p-6 border-t bg-background">
                        <div class="flex justify-between w-full">
                            <Button variant="outline" @click="isDialogOpen = false" class="px-6">Close</Button>
                            <Button @click="handleEdit(selectedUser.id)" class="px-6">Edit User Details</Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </Layout>
    </AppLayout>
</template>

<style scoped>
.loop-cards {
    animation: cards-loop 40s linear infinite;
}

@keyframes cards-loop {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(calc(-1 * var(--cards-loop-distance, 800px)));
    }
}

.animate-cards-loop {
    animation: cards-loop 40s linear infinite;
}

/* Pause animation on hover */
.loop-cards:hover {
    animation-play-state: paused;
}
</style>
