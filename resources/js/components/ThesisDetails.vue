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
    <div class="flex flex-col h-full">
        <!-- Details section - scrollable and responsive -->
        <div class="flex-1 min-w-0 overflow-y-auto max-h-full">
            <div class="space-y-6 pr-2">
                <!-- Title and basic info -->
                <div class="space-y-2">
                    <h2 class="text-2xl lg:text-3xl font-bold leading-tight break-words">{{ record?.title }}</h2>
                    <div class="flex flex-wrap gap-2 text-sm text-muted-foreground">
                        <span class="bg-muted/50 px-2 py-1 rounded">{{ record?.accession_number }}</span>
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

                <!-- Details grid - responsive -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-4">
                    <div class="space-y-1" v-if="safeArrayJoin(record?.thesis.researchers)">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Researchers</span>
                        <p class="break-words">{{ safeArrayJoin(record?.thesis.researchers) }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.thesis.adviser">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Adviser</span>
                        <p class="break-words">{{ record?.thesis.adviser }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.thesis.year">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Year</span>
                        <p>{{ record?.thesis.year }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.thesis.month">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Month</span>
                        <p>{{ record?.thesis.month }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.thesis.institution">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Institution</span>
                        <p class="break-words">{{ record?.thesis.institution }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.thesis.college">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">College</span>
                        <p class="break-words">{{ record?.thesis.college }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.thesis.degree_program">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Degree Program</span>
                        <p class="break-words">{{ record?.thesis.degree_program }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.thesis.degree_level">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Degree Level</span>
                        <p>{{ record?.thesis.degree_level }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.thesis.call_number">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Call Number</span>
                        <p class="font-mono text-sm">{{ record?.thesis.call_number }}</p>
                    </div>
                </div>

                <!-- Abstract - full width -->
                <div class="space-y-2" v-if="record?.thesis.abstract">
                    <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Abstract</span>
                    <div class="bg-muted/30 rounded-lg p-4">
                        <p class="whitespace-pre-wrap break-words leading-relaxed text-sm">{{ record?.thesis.abstract }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
