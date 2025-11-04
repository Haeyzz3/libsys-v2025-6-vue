<script setup lang="ts">
import { getStatusStyle } from '../utils/statusColors';
import { Badge } from '@/components/ui/badge';

defineProps({
    record: Object,
});

// Helper function to safely handle arrays or JSON strings
const safeArrayJoin = (data: any, separator = ', ') => {
    if (!data) return '';
    if (Array.isArray(data)) return data.join(separator);
    if (typeof data === 'string') {
        try {
            const parsed = JSON.parse(data);
            return Array.isArray(parsed) ? parsed.join(separator) : data;
        } catch {
            return data;
        }
    }
    return String(data);
};
</script>

<template>
    <div class="grid gap-6 h-full max-h-9/10 sm:max-w-6xl justify-between">
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
                    <span class="font-semibold">Researchers:</span>
                    <p>{{ safeArrayJoin(record?.thesis.researchers) }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Adviser:</span>
                    <p>{{ record?.thesis.adviser }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Year:</span>
                    <p>{{ record?.thesis.year }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Month:</span>
                    <p>{{ record?.thesis.month }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Institution:</span>
                    <p>{{ record?.thesis.institution }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">College:</span>
                    <p>{{ record?.thesis.college }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Degree Program:</span>
                    <p>{{ record?.thesis.degree_program }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Degree Level:</span>
                    <p>{{ record?.thesis.degree_level }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Call Number:</span>
                    <p>{{ record?.thesis.call_number }}</p>
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

            <div class="space-y-1">
                <span class="font-semibold">Abstract:</span>
                <p class="whitespace-pre-wrap">{{ record?.thesis.abstract }}</p>
            </div>
        </div>
    </div>
</template>
