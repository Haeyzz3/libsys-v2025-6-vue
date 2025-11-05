<script setup lang="ts">
import { ref, watch } from 'vue'
import { X, Book } from "lucide-vue-next"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { debounce } from 'lodash-es'
import WelcomeSearchDialog from '@/components/WelcomeSearchDialog.vue';

// Types for grouped search results
interface CollectionRecord {
    id: number;
    title: string;
    accession_number: string;
    status: string;
    book?: any;
    digital_resource?: any;
    periodical?: any;
    thesis?: any;
    [key: string]: any;
}

interface GroupedCollectionRecord {
    id: number;
    title: string;
    accession_number: string;
    status: string;
    book?: any;
    digital_resource?: any;
    periodical?: any;
    thesis?: any;
    copy_count: number;
    copies: CollectionRecord[];
}

// Reactive state
const searchQuery = ref('')
const rawSearchResults = ref<CollectionRecord[]>([])
const searchResults = ref<GroupedCollectionRecord[]>([])
const isLoading = ref(false)
const selectedFilter = ref('all') // Default to "all records"

// Filter options
const filterOptions = [
    { value: 'all', label: 'Collections' },
    { value: 'book', label: 'Books' },
    { value: 'digital_resource', label: 'Multimedia Collection' },
    { value: 'periodical', label: 'Periodicals/Magazines' },
    { value: 'thesis', label: 'Thesis/Dissertations' }
]

// Function to group search results by title (same as Welcome.vue)
const groupRecordsByTitle = (records: CollectionRecord[]): GroupedCollectionRecord[] => {
    const titleGroups = new Map<string, CollectionRecord[]>();

    // Group records by title
    records.forEach(record => {
        const title = record.title || 'Untitled';
        if (!titleGroups.has(title)) {
            titleGroups.set(title, []);
        }
        titleGroups.get(title)!.push(record);
    });

    // Convert groups to grouped records
    const groupedRecords: GroupedCollectionRecord[] = [];
    titleGroups.forEach((copies) => {
        // Use the first copy as the representative record
        const representative = copies[0];

        const groupedRecord: GroupedCollectionRecord = {
            id: representative.id,
            title: representative.title,
            accession_number: representative.accession_number,
            status: representative.status,
            book: representative.book,
            digital_resource: representative.digital_resource,
            periodical: representative.periodical,
            thesis: representative.thesis,
            copy_count: copies.length,
            copies: copies
        };

        groupedRecords.push(groupedRecord);
    });

    // Debug logging for search results grouping
    if (import.meta.env.DEV) {
        console.log('Search Results Grouping:', {
            originalRecords: records.length,
            uniqueTitles: titleGroups.size,
            groupedRecords: groupedRecords.length,
            copyCounts: groupedRecords.map(g => ({ title: g.title.substring(0, 50), copies: g.copy_count }))
        });
    }

    return groupedRecords;
};

// Debounced search function
const debouncedSearch = debounce(async (query: string) => {
    if (!query || query.length < 2) {
        searchResults.value = []
        isLoading.value = false
        return
    }

    isLoading.value = true

    try {
        // Build query parameters
        const params = new URLSearchParams({
            q: query
        })

        // Add filter parameter if not "all"
        if (selectedFilter.value !== 'all') {
            params.append('type', selectedFilter.value)
        }

        const response = await fetch(`/api/welcome/records/search?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            }
        })

        if (response.ok) {
            const data = await response.json()
            rawSearchResults.value = data.records || data || []
            // Group search results by title
            const groupedResults = groupRecordsByTitle(rawSearchResults.value)
            // Limit to 10 grouped results to prevent UI overwhelming, but each group contains all copies
            searchResults.value = groupedResults.slice(0, 10)
        } else {
            console.error('Record search failed:', response.statusText)
            rawSearchResults.value = []
            searchResults.value = []
        }
    } catch (error) {
        console.error('Record search error:', error)
        rawSearchResults.value = []
        searchResults.value = []
    } finally {
        isLoading.value = false
    }
}, 300)

// Watch for search query changes
watch(searchQuery, (newQuery) => {
    debouncedSearch(newQuery)
})

// Watch for filter changes and re-trigger search if there's a query
watch(selectedFilter, () => {
    if (searchQuery.value && searchQuery.value.length >= 2) {
        debouncedSearch(searchQuery.value)
    }
})

// Clear search - only clear search query and results, preserve filter
const clearSearch = () => {
    searchQuery.value = ''
    rawSearchResults.value = []
    searchResults.value = []
    // Remove this line to preserve the filter: selectedFilter.value = 'all'
}

</script>

<template>
    <div class="grid space-y-4">
        <div class="flex gap-1">
            <!-- Filter Select Box -->
            <Select v-model="selectedFilter">
                <SelectTrigger class="w-48">
                    <SelectValue placeholder="Filter by type" />
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

            <!-- Search Input with Results -->
            <div class="relative flex-1">
                <div class="relative">
                    <Input
                        v-model="searchQuery"
                        class="pr-10"
                        placeholder="Search by title, or accession number..."
                    />

                    <!-- Clear button -->
                    <Button
                        v-if="searchQuery"
                        variant="ghost"
                        class="absolute top-0 right-0 h-full px-2"
                        @click="clearSearch"
                    >
                        <X class="h-4 w-4" />
                    </Button>

                    <!-- Loading spinner -->
                    <div
                        v-if="isLoading"
                        class="absolute top-0 right-8 h-full px-2 flex items-center justify-center pointer-events-none"
                    >
                        <div class="h-4 w-4 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent" />
                    </div>
                </div>

                <!-- Search Results Dropdown -->
                <div
                    v-if="searchQuery"
                    class="absolute top-full left-0 right-0 z-50 mt-1 bg-muted border rounded-md shadow-lg max-h-96 overflow-y-auto"
                >
                    <!-- Empty state -->
                    <div
                        v-if="!searchResults.length && !isLoading"
                        class="flex flex-col items-center p-4 text-center"
                    >
                        <Book class="size-8 text-muted-foreground mb-2" />
                        <p class="text-sm text-muted-foreground">
                            {{ searchQuery.length < 2 ? 'Type at least 2 characters to search' : 'No records found' }}
                        </p>
                    </div>

                    <!-- Search Results -->
                    <div v-if="searchResults.length > 0" class="p-1">
                        <div
                            v-for="record in searchResults"
                            :key="record.id"
                            class="flex my-1 bg-white flex-col items-start hover:bg-accent rounded-sm cursor-pointer"
                        >
                            <div class="flex w-full items-center justify-between">
                                <WelcomeSearchDialog :record="record"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
