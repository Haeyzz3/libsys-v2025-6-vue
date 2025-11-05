<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getStatusStyle } from '../utils/statusColors';
import BookDetails from './BookDetails.vue';
import DigitalResourceDetails from './DigitalResourceDetails.vue';
import PeriodicalDetails from './PeriodicalDetails.vue';
import ThesisDetails from './ThesisDetails.vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@radix-icons/vue';
import { ref, computed, watch } from 'vue';

const props = defineProps<{
    record: {
        title: string
        accession_number: string
        status: string
        book?: any
        digital_resource?: any
        periodical?: any
        thesis?: any
        copy_count?: number
        copies?: any[]
    }
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
    if (record.book && record.book.cover_image) {
        return `/records/covers/${record.book.cover_image}`;
    }
    return '/storage/placeholders/sample3.svg';
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
        <DialogTrigger as-child>
            <Card class="overflow-hidden p-0 hover:shadow-md transition">
                <div class="flex">
                    <!-- Main content -->
                    <div class="flex-1 p-6">
                        <CardHeader class="p-0 pb-4">
                            <div class="flex items-start justify-between gap-2">
                                <CardTitle class="line-clamp-2 text-xl font-semibold flex-1">
                                    {{ props.record.title }}
                                </CardTitle>
                                <Badge v-if="hasMultipleCopies" variant="secondary" class="shrink-0">
                                    {{ totalCopies }} copies
                                </Badge>
                            </div>
                        </CardHeader>

                        <CardContent class="p-0">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div class="space-y-1">
                                    <span class="text-muted-foreground">Accession Number</span>
                                    <div class="font-medium">{{ props.record.accession_number }}</div>
                                </div>
                                <div class="space-y-1">
                                    <span class="text-muted-foreground">Status</span>
                                    <div>
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="[
                                                getStatusStyle(props.record.status || '').text,
                                                getStatusStyle(props.record.status || '').bg
                                            ]"
                                        >
                                            {{ props.record.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </div>
                    <!-- Cover image -->
                    <div class="flex w-32 items-center justify-center bg-gray-50 p-4">
                        <img
                            class="h-auto max-h-40 w-full rounded-lg object-cover shadow-sm"
                            :src="getCoverUrl(props.record)"
                            :alt="`Cover of ${props.record.title}`"
                            @error="(e) => e?.target && (e.target.style.display = 'none')"
                        />
                    </div>
                </div>
            </Card>
        </DialogTrigger>

        <DialogContent class="max-w-7xl w-[95vw] h-[90vh] overflow-hidden flex flex-col p-0">
            <!-- Copy Navigation Header (only show if multiple copies) -->
            <div v-if="hasMultipleCopies" class="flex items-center justify-between p-4 border-b border-border bg-background/95 backdrop-blur-sm">
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
            <div class="flex-1 overflow-hidden p-6">
                <component
                    v-if="recordTypeComponent()"
                    :is="recordTypeComponent()"
                    :record="currentCopy"
                />
                <div v-else class="flex items-center justify-center h-full text-center">
                    <div>
                        <h2 class="text-2xl font-bold mb-4">{{ currentCopy.title }}</h2>
                        <p class="text-muted-foreground">
                            This collection item type is not supported for detailed viewing yet.
                        </p>
                    </div>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
