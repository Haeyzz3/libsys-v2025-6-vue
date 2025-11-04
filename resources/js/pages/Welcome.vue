<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import WelcomeRecordDialog from '@/components/WelcomeRecordDialog.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, DoubleArrowLeftIcon, DoubleArrowRightIcon } from '@radix-icons/vue';
import { Book, AlertCircle, PlusCircle, Repeat, X } from 'lucide-vue-next';
import { onMounted, ref, watch, nextTick } from 'vue';
import WelcomeFooter from '@/components/WelcomeFooter.vue';
import CollectionSearchComboBox from '@/components/CollectionSearchComboBox.vue';


// Page and alert handling
const page = usePage();
const name = page.props.name;
const showAlert = ref(true);

// Types definition
interface Flash {
    success?: string | null;
    error?: string | null;
}

interface Config {
    registration_enabled?: boolean | null;
    login_enabled?: boolean | null;
}

interface CollectionRecord {
    id: number;
    [key: string]: any;
}

// API Response type for collections
interface ApiResponse {
    data: CollectionRecord[];
    current_page: number;
    per_page: number;
    last_page: number;
    total: number;
}

declare module '@inertiajs/core' {
    interface PageProps {
        flash: Flash;
        config: Config;
    }
}

// Reactive state for collections data
const collections = ref<CollectionRecord[]>([]);
const isLoadingCollections = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const collectionsError = ref<string | null>(null);

// Updated filter state with all resource types
const filterType = ref('all');
const filterOptions = [
    { value: 'all', label: 'All Collections' },
    { value: 'books', label: 'Books' },
    { value: 'digital_resources', label: 'Multimedia Collections' },
    { value: 'periodicals', label: 'Periodicals/Magazines' },
    { value: 'theses', label: 'Theses/Dissertations' }
];

// Pagination state for collections
const pageSizes = [3, 6, 9, 12]; // Multiples of 3 for grid layout
const pagination = ref({
    pageIndex: 0,
    pageSize: 6, // Default to 6 items per page
});

// Scroll position tracking
const scrollPosition = ref(0);

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

// Utility function for debouncing
function debounce(func: Function, wait: number) {
    let timeout: ReturnType<typeof setTimeout>;
    return function executedFunction(...args: any[]) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Helper function to get display name for current filter
const getFilterDisplayName = () => {
    const option = filterOptions.find(opt => opt.value === filterType.value);
    return option ? option.label : 'Collections';
};

// Fetch latest collections data
const fetchLatestCollections = async () => {
    isLoadingCollections.value = true;
    collectionsError.value = null;

    try {
        // Build query parameters for collections
        const params = new URLSearchParams();
        params.append('page', (pagination.value.pageIndex + 1).toString());
        params.append('per_page', pagination.value.pageSize.toString());
        params.append('sort_field', 'created_at');
        params.append('sort_direction', 'desc');

        // Add filter parameter - now supports all relation types
        if (filterType.value !== 'all') {
            params.append('filter', filterType.value);
        }

        // Make API request for collections
        const response = await fetch(`/api/welcome_records?${params.toString()}`, {
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
        collections.value = result.data || [];
        currentPage.value = result.current_page || 1;
        lastPage.value = result.last_page || 1;
        total.value = result.total || 0;

        // Update pagination state to match API response
        pagination.value.pageIndex = (result.current_page || 1) - 1;
        pagination.value.pageSize = result.per_page || 6;

    } catch (err) {
        console.error('Collections API fetch error:', err);
        collectionsError.value = err instanceof Error ? err.message : 'An error occurred while fetching collections';
        collections.value = [];
    } finally {
        isLoadingCollections.value = false;
    }
};

// Debounced fetch for immediate UI feedback
const debouncedFetch = debounce(fetchLatestCollections, 300);

// Handle filter change
const handleFilterChange = (value: string) => {
    if (!isLoadingCollections.value) {
        saveScrollPosition();
        filterType.value = value;
        pagination.value.pageIndex = 0; // Reset to first page on filter change
        fetchLatestCollections().then(() => {
            restoreScrollPosition();
        });
    }
};

// Pagination navigation functions with scroll preservation
const goToFirstPage = () => {
    if (pagination.value.pageIndex > 0 && !isLoadingCollections.value) {
        saveScrollPosition();
        pagination.value.pageIndex = 0;
        fetchLatestCollections().then(() => {
            restoreScrollPosition();
        });
    }
};

const goToPreviousPage = () => {
    if (pagination.value.pageIndex > 0 && !isLoadingCollections.value) {
        saveScrollPosition();
        pagination.value.pageIndex -= 1;
        fetchLatestCollections().then(() => {
            restoreScrollPosition();
        });
    }
};

const goToNextPage = () => {
    if (pagination.value.pageIndex < lastPage.value - 1 && !isLoadingCollections.value) {
        saveScrollPosition();
        pagination.value.pageIndex += 1;
        fetchLatestCollections().then(() => {
            restoreScrollPosition();
        });
    }
};

const goToLastPage = () => {
    if (pagination.value.pageIndex < lastPage.value - 1 && !isLoadingCollections.value) {
        saveScrollPosition();
        pagination.value.pageIndex = lastPage.value - 1;
        fetchLatestCollections().then(() => {
            restoreScrollPosition();
        });
    }
};

const handlePageSizeChange = (value: string) => {
    if (!isLoadingCollections.value) {
        saveScrollPosition();
        pagination.value.pageSize = Number(value);
        pagination.value.pageIndex = 0; // Reset to first page on page size change
        fetchLatestCollections().then(() => {
            restoreScrollPosition();
        });
    }
};

// Initialize from URL
const initializeFromURL = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const page = parseInt(urlParams.get('page') || '1');
    const perPageParam = parseInt(urlParams.get('per_page') || '6');
    const filterParam = urlParams.get('filter') || 'all';

    // Validate filter parameter against available options
    const validFilter = filterOptions.some(opt => opt.value === filterParam) ? filterParam : 'all';

    pagination.value.pageIndex = page - 1;
    pagination.value.pageSize = perPageParam;
    filterType.value = validFilter;
};

// Statistics data
const stats = [
    {
        title: 'Collections',
        value: page.props.totalCollections?.toLocaleString() || '0',
        change: 'Book, CDs, Magazines, Thesis, etc.',
        icon: Book,
    },
    {
        title: 'New Arrivals',
        value: '+' + (page.props.newThisMonth?.toLocaleString() || '0'),
        change: `${page.props.percentChangeThisMonth > 0 ? '+' : ''}${page.props.percentChangeThisMonth}% from last month`,
        icon: PlusCircle,
    },
    {
        title: 'Borrowing Transactions',
        value: '+' + (page.props.totalBorrowings?.toLocaleString() || '0'),
        change: `+${page.props.weekChange} since last week`,
        icon: Repeat,
    },
];
//branding
const branding = {
    logoUrl: '/images/projectStatic/eagle.jpg', // or dynamic source
    headerLinkText: 'ULRC Tagum-Mabini'
};

const heroVisible = ref(false);

onMounted(() => {
    nextTick(() => {
        heroVisible.value = true; // trigger one-time fade in
    });
});
// Lifecycle
onMounted(() => {
    if (page.props.flash.error) {
        setTimeout(() => {
            showAlert.value = false;
        }, 5000);
    }
    initializeFromURL();
    fetchLatestCollections();
});

// Watch for URL changes and refetch data
watch(() => window.location.search, () => {
    initializeFromURL();
    fetchLatestCollections();
});
</script>

<template>
    <Head title="Welcome" />

    <div class="flex flex-col bg-background text-[#1b1b18] lg:justify-center dark:bg-[#0a0a0a]">
        <!-- Hidden logger link -->
        <Link :href="route('logger.create')" class="fixed top-0 left-0 bg-green-500 opacity-0">hi</Link>

        <!-- Error alert -->
        <Alert v-if="page.props.flash.error && showAlert" class="absolute top-5 right-5 w-fit pr-8 z-50" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <button @click="showAlert = false" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                <X class="h-4 w-4" />
            </button>
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>{{ page.props.flash.error }}</AlertDescription>
        </Alert>

        <!-- Collections API Error alert -->
        <Alert v-if="collectionsError && showAlert" class="absolute top-20 right-5 w-fit pr-8 z-50" variant="destructive">
            <AlertCircle class="h-4 w-4" />
            <button @click="collectionsError = null" class="absolute top-2 right-2 rounded-full p-1 transition-colors hover:bg-red-100">
                <X class="h-4 w-4" />
            </button>
            <AlertTitle>Collections Error</AlertTitle>
            <AlertDescription>{{ collectionsError }}</AlertDescription>
        </Alert>

        <!-- Header section -->
        <header class="flex w-full items-center justify-between p-4 px-16 text-sm not-has-[nav]:hidden">
            <div class="relative z-20 flex items-center text-lg font-medium dark:text-foreground">
                <AppLogoIcon class="mr-2 size-8 fill-current text-white" />
                {{ name }}
            </div>
            <nav class="flex items-center justify-end gap-4">
                <AppearanceTabs />
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        v-if="$page.props.config.login_enabled"
                        :href="route('login')"
                        class="inline-block rounded-sm border border-transparent text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        <Button class="w-[120px]">Log in</Button>
                    </Link>
                    <Link
                        v-if="$page.props.config.registration_enabled"
                        :href="route('register')"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        <Button class="w-[120px]">Register</Button>
                    </Link>
                </template>
            </nav>
        </header>



        <div class="px-4">
            <div class="relative flex h-[360px] min-w-full items-center justify-center overflow-visible rounded-2xl">
                <!-- Background image layer -->
                <div class="absolute inset-0 z-0 overflow-hidden rounded-2xl">
                    <img
                        src="/storage/system_images/eagle.jpg"
                        alt="Library banner"
                        class="h-full w-full object-cover scale-105 blur-sm brightness-75"
                        @error="(e) => { (e.target as HTMLImageElement).src = '/images/projectStatic/eagle.jpg'; }"
                    />
                </div>

                <!-- Gradient overlay -->
                <div class="absolute inset-0 z-10 bg-gradient-to-b from-black/40 via-black/20 to-black/30 rounded-2xl overflow-hidden"></div>

                <!-- Heading -->
                <div class="absolute top-10 left-1/2 z-20 -translate-x-1/2 text-center drop-shadow">
                    <h1 class="text-4xl font-bold text-white dark:text-white">ULRC Tagum-Mabini</h1>
                    <p class="text-lg text-white dark:text-white">Your gateway to knowledge and discovery</p>
                </div>

                <!-- Search -->
                <div class="z-20 w-full max-w-xl p-1 rounded-xl bg-background/80 backdrop-blur border border-white/10 shadow-lg">
                    <CollectionSearchComboBox class="w-full rounded-lg" />
                </div>
            </div>


            <!-- Statistics section -->
            <div class="flex gap-8 px-12 py-8 pb-0 justify-center">
                <Card v-for="stat in stats" :key="stat.title" class="p-4 w-80 transition hover:shadow-md">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 p-0">
                        <CardTitle class="text-sm font-medium text-muted-foreground">
                            {{ stat.title }}
                        </CardTitle>
                        <component :is="stat.icon" class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="text-4xl text-primary text-center font-bold">{{ stat.value }}</div>
                        <p class="text-sm mt-2 text-center text-muted-foreground">{{ stat.change }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Latest collections section -->
            <div class="w-full px-16 py-12" v-if="collections.length > 0 || isLoadingCollections">
                <!-- Section header with filter -->
                <div class="flex items-center justify-between pb-12">
                    <CardTitle class="text-2xl font-medium text-foreground">
                        Latest in {{ getFilterDisplayName() }}
                    </CardTitle>

                    <!-- Filter dropdown -->
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium text-muted-foreground">Show:</p>
                        <Select
                            :model-value="filterType"
                            @update:model-value="handleFilterChange"
                            :disabled="isLoadingCollections"
                        >
                            <SelectTrigger class="h-9 w-[180px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in filterOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Loading state -->
                <div v-if="isLoadingCollections" class="text-center py-8">
                    <p class="text-muted-foreground">Loading latest {{ getFilterDisplayName().toLowerCase() }}...</p>
                </div>

                <!-- Collections grid -->
                <div v-else class="grid grid-cols-3 gap-4">
                    <div v-for="record in collections" :key="record.id">
                        <WelcomeRecordDialog :record="record" />
                    </div>
                </div>

                <!-- Pagination Controls -->
                <div class="flex items-center justify-end space-x-4 pt-8">
                    <div class="flex-1 text-sm text-muted-foreground">
                        Showing page {{ currentPage }} of {{ lastPage }} in {{ total }} {{ total === 1 || total === 0 ? 'item' : 'items' }}.
                    </div>

                    <!-- Combine select + buttons in one flex group -->
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center space-x-2">
                            <p class="text-sm font-medium">Items per page</p>
                            <Select
                                :model-value="pagination.pageSize.toString()"
                                @update:model-value="handlePageSizeChange"
                                :disabled="isLoadingCollections"
                            >
                                <SelectTrigger class="h-8 w-[80px]">
                                    <SelectValue :placeholder="pagination.pageSize.toString()" />
                                </SelectTrigger>
                                <SelectContent side="top">
                                    <SelectItem
                                        v-for="pageSize in pageSizes"
                                        :key="pageSize"
                                        :value="pageSize.toString()"
                                    >
                                        {{ pageSize }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Pagination buttons -->
                        <div class="flex items-center space-x-2">
                            <Button
                                variant="outline"
                                class="hidden h-8 w-8 p-0 lg:flex"
                                :disabled="pagination.pageIndex === 0 || isLoadingCollections"
                                @click="goToFirstPage"
                                aria-label="Go to first page"
                            >
                                <DoubleArrowLeftIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                class="h-8 w-8 p-0"
                                :disabled="pagination.pageIndex === 0 || isLoadingCollections"
                                @click="goToPreviousPage"
                                aria-label="Go to previous page"
                            >
                                <ChevronLeftIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                class="h-8 w-8 p-0"
                                :disabled="pagination.pageIndex >= lastPage - 1 || isLoadingCollections"
                                @click="goToNextPage"
                                aria-label="Go to next page"
                            >
                                <ChevronRightIcon class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                class="hidden h-8 w-8 p-0 lg:flex"
                                :disabled="pagination.pageIndex >= lastPage - 1 || isLoadingCollections"
                                @click="goToLastPage"
                                aria-label="Go to last page"
                            >
                                <DoubleArrowRightIcon class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Empty state for collections -->
            <div v-else-if="!isLoadingCollections && collections.length === 0" class="w-full px-16 py-12">
                <div class="flex items-center justify-between pb-12">
                    <CardTitle class="text-2xl font-medium text-foreground">
                        Latest in {{ getFilterDisplayName() }}
                    </CardTitle>

                    <!-- Filter dropdown for empty state -->
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium text-muted-foreground">Show:</p>
                        <Select
                            :model-value="filterType"
                            @update:model-value="handleFilterChange"
                            :disabled="isLoadingCollections"
                        >
                            <SelectTrigger class="h-9 w-[180px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in filterOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <div class="text-center py-8">
                    <p class="text-muted-foreground">No {{ getFilterDisplayName().toLowerCase() }} available at the moment.</p>
                </div>
            </div>
        </div>

        <!-- Footer spacing -->
        <div class="w-full p-4">
            <WelcomeFooter :appName="name" class="rounded-2xl"/>
        </div>
    </div>
</template>
