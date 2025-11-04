<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { getStatusStyle } from '../utils/statusColors';
import BookDetails from './BookDetails.vue';
import DigitalResourceDetails from './DigitalResourceDetails.vue';
import PeriodicalDetails from './PeriodicalDetails.vue';
import ThesisDetails from './ThesisDetails.vue';

const props = defineProps<{
    record: {
        title: string
        accession_number: string
        status: string
        book?: any
        digital_resource?: any
        periodical?: any
        thesis?: any
    }
}>();

const getCoverUrl = (record: typeof props.record) => {
    if (record.book && record.book.cover_image) {
        return `/records/covers/${record.book.cover_image}`;
    }
    return '/storage/placeholders/sample3.svg';
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
        <DialogTrigger as-child>
            <Card class="overflow-hidden p-0 hover:shadow-md transition">
                <div class="flex">
                    <!-- Main content -->
                    <div class="flex-1 p-6">
                        <CardHeader class="p-0 pb-4">
                            <CardTitle class="line-clamp-2 text-xl font-semibold">
                                {{ props.record.title }}
                            </CardTitle>
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

        <DialogContent class="max-w-4xl">
            <component :is="recordTypeComponent()" :record="props.record" />
        </DialogContent>
    </Dialog>
</template>
