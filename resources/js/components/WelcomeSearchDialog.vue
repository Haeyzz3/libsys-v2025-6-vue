<script setup lang="ts">
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { getStatusStyle } from '../utils/statusColors';
import BookDetails from './BookDetails.vue';
import DigitalResourceDetails from './DigitalResourceDetails.vue';
import PeriodicalDetails from './PeriodicalDetails.vue';
import ThesisDetails from './ThesisDetails.vue';

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
    };
}>();

const getCoverUrl = (record: typeof props.record) => {
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
    if (props.record.book) return 'book';
    if (props.record.thesis) return 'thesis/dissertation';
    if (props.record.digital_resource) return 'multimedia';
    if (props.record.periodical) return 'periodical/magazine';
    return 'unknown';
};

const recordTypeComponent = () => {
    if (props.record.book) return BookDetails;
    if (props.record.digital_resource) return DigitalResourceDetails;
    if (props.record.periodical) return PeriodicalDetails;
    if (props.record.thesis) return ThesisDetails;
    return null;
};
</script>

<template>
    <Dialog>
        <DialogTrigger as-child class="p-2 cursor-pointer hover:bg-accent rounded-md transition-colors">
            <div class="w-full grid gap-2">
                <div class="text-md font-semibold leading-tight truncate w-full">
                    {{ record.title }}
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
            <component
                v-if="recordTypeComponent()"
                :is="recordTypeComponent()"
                :record="record"
            />
            <div v-else class="p-6 text-center">
                <h2 class="text-2xl font-bold mb-4">{{ record.title }}</h2>
                <p class="text-muted-foreground">
                    This collection item type is not supported for detailed viewing yet.
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
