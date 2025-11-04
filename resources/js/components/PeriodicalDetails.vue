<script setup lang="ts">
import { getStatusStyle } from '../utils/statusColors';
import { Badge } from '@/components/ui/badge';

defineProps({
    record: Object,
});
</script>

<template>
    <div class="space-y-6 overflow-y-auto">
        <div>
            <h2 class="text-2xl font-bold">{{ record?.title }}</h2>
            <div class="flex my-4 gap-2">
                <p class="text-muted-foreground">{{ record?.accession_number }}</p>
                <Badge v-if="record?.copy_count > 1">{{ record?.copy_count }} copies</Badge>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="space-y-1">
                <span class="font-semibold">Authors:</span>
                <p>{{ record?.periodical.authors?.join(', ') }}</p>
            </div>
            <div class="space-y-1">
                <span class="font-semibold">Editors:</span>
                <p>{{ record?.periodical.editors?.join(', ') }}</p>
            </div>
            <div class="space-y-1">
                <span class="font-semibold">Publication Year:</span>
                <p>{{ record?.periodical.publication_year }}</p>
            </div>
            <div class="space-y-1">
                <span class="font-semibold">Publication Month:</span>
                <p>{{ record?.periodical.publication_month }}</p>
            </div>
            <div class="space-y-1">
                <span class="font-semibold">Publisher:</span>
                <p>{{ record?.periodical.publisher }}</p>
            </div>
            <div class="space-y-1">
                <span class="font-semibold">Volume Number:</span>
                <p>{{ record?.periodical.volume_number }}</p>
            </div>
            <div class="space-y-1">
                <span class="font-semibold">Issue Number:</span>
                <p>{{ record?.periodical.issue_number }}</p>
            </div>
            <div class="space-y-1">
                <span class="font-semibold">ISSN:</span>
                <p>{{ record?.periodical.issn }}</p>
            </div>
            <div class="space-y-1">
                <span class="font-semibold">Series Title:</span>
                <p>{{ record?.periodical.series_title }}</p>
            </div>
            <div class="space-y-1">
                <span class="font-semibold">Call Number:</span>
                <p>{{ record?.periodical.call_number }}</p>
            </div>
            <div class="space-y-1">
                <span class="font-semibold">Status:</span>
                <span
                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="[
                        getStatusStyle(record?.status || '').text,
                        getStatusStyle(record?.status || '').bg
                    ]"
                >
                    {{ record?.status }}
                </span>
            </div>
        </div>
    </div>
</template>
