<script setup lang="ts">
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getStatusStyle } from '../utils/statusColors';
import BookDetails from './BookDetails.vue';
import DigitalResourceDetails from './DigitalResourceDetails.vue';
import PeriodicalDetails from './PeriodicalDetails.vue';
import ThesisDetails from './ThesisDetails.vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@radix-icons/vue';
import { ref, computed } from 'vue';

const props = defineProps<{
    record: {
        id: number;
        title: string;
        accession_number: string;
        status: string;
        book?: any;
        digital_resource?: any;
        periodical?: any;
        thesis?: any;
        copy_count?: number;
        copies?: any[];
    };
}>();

// Copy navigation state
const currentCopyIndex = ref(0);
const isOpen = ref(false);

// Computed properties for current copy
const hasMultipleCopies = computed(() => {
    return props.record.copies && props.record.copies.length > 1;
});

const currentCopy = computed(() => {
    if (hasMultipleCopies.value && props.record.copies) {
        return props.record.copies[currentCopyIndex.value];
    }
    return props.record;
});

const totalCopies = computed(() => {
    return props.record.copy_count || 1;
});

// Copy navigation functions
const goToPreviousCopy = () => {
    if (hasMultipleCopies.value && currentCopyIndex.value > 0) {
        currentCopyIndex.value--;
    }
};

const goToNextCopy = () => {
    if (hasMultipleCopies.value && props.record.copies && currentCopyIndex.value < props.record.copies.length - 1) {
        currentCopyIndex.value++;
    }
};

// Reset copy index when dialog opens
const handleOpenChange = (open: boolean) => {
    isOpen.value = open;
    if (open) {
        currentCopyIndex.value = 0;
    }
};

const getCoverUrl = (record: any) => {
    if (record.book?.cover_image) {
        return `/records/covers/${record.book.cover_image}`;
    }
    if (record.digital_resource?.cover_image) {
        return `/records/covers/${record.digital_resource.cover_image}`;
    }
    if (record.periodical?.cover_image) {
        return `/records/covers/${record.periodical.cover_image}`;
    }
    return '/storage/placeholders/sample3.svg';
};

const getRecordType = () => {
    const record = currentCopy.value;
    if (record.book) return 'book';
    if (record.thesis) return 'thesis/dissertation';
    if (record.digital_resource) return 'multimedia';
    if (record.periodical) return 'periodical/magazine';
    return 'unknown';
};

const recordTypeComponent = () => {
    const record = currentCopy.value;
    if (record.book) return BookDetails;
    if (record.digital_resource) return DigitalResourceDetails;
    if (record.periodical) return PeriodicalDetails;
    if (record.thesis) return ThesisDetails;
    return null;
};
</script>

<template>
    <Dialog :open="isOpen" @update:open="handleOpenChange">
        <DialogTrigger as-child class="p-2 cursor-pointer hover:bg-accent rounded-md transition-colors">
            <div class="w-full grid gap-2">
                <div class="flex items-start justify-between gap-2">
                    <div class="text-md font-semibold leading-tight truncate flex-1">
                        {{ record.title }}
                    </div>
                    <Badge v-if="hasMultipleCopies" variant="outline" class="text-xs shrink-0">
                        {{ totalCopies }} copies
                    </Badge>
                </div>
                <div class="flex w-full justify-between items-center">
                    <div class="leading-tight text-sm text-muted-foreground">
                        {{ record.accession_number }}
                    </div>
                    <Badge variant="secondary" class="text-xs">
                        {{ getRecordType() }}
                    </Badge>
                </div>
            </div>
        </DialogTrigger>

        <DialogContent class="max-w-4xl h-[90vh] overflow-hidden">
            <!-- Copy Navigation Header (only show if multiple copies) -->
            <div v-if="hasMultipleCopies" class="flex items-center justify-between p-4 border-b border-border">
                <div class="flex items-center gap-4">
                    <Badge variant="outline">
                        Copy {{ currentCopyIndex + 1 }} of {{ totalCopies }}
                    </Badge>
                    <div class="text-sm text-muted-foreground">
                        Accession: {{ currentCopy.accession_number }}
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="currentCopyIndex === 0"
                        @click="goToPreviousCopy"
                    >
                        <ChevronLeftIcon class="h-4 w-4 mr-1" />
                        Previous
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="!props.record.copies || currentCopyIndex === props.record.copies.length - 1"
                        @click="goToNextCopy"
                    >
                        Next
                        <ChevronRightIcon class="h-4 w-4 ml-1" />
                    </Button>
                </div>
            </div>

            <!-- Current Copy Details -->
            <div class="flex-1 overflow-hidden">
                <component
                    v-if="recordTypeComponent()"
                    :is="recordTypeComponent()"
                    :record="currentCopy"
                />
                <div v-else class="p-6 text-center">
                    <h2 class="text-2xl font-bold mb-4">{{ currentCopy.title }}</h2>
                    <p class="text-muted-foreground">
                        This collection item type is not supported for detailed viewing yet.
                    </p>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
