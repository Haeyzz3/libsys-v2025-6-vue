<script setup lang="ts">
import { getStatusStyle } from '../utils/statusColors';
import { Badge } from '@/components/ui/badge';

defineProps({
    record: Object,
});

const getCoverUrl = (path: string) => {
    return path ? `/records/covers/${path}` : '/storage/placeholders/sample3.svg';
};

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
    <div class="flex flex-col lg:flex-row gap-6 h-full">
        <!-- Cover image section - responsive -->
        <div class="flex-shrink-0 flex justify-center lg:justify-start">
            <div class="w-full max-w-sm lg:max-w-xs xl:max-w-sm">
                <img
                    class="w-full h-auto max-h-[400px] lg:max-h-[500px] object-contain rounded-lg shadow-lg border"
                    loading="lazy"
                    :src="getCoverUrl(record?.periodical.cover_image)"
                    alt="Periodical Cover"
                    @error="(e) => {
                        if (e?.target) {
                            e.target.src = '/storage/placeholders/sample3.svg';
                            e.target.style.objectFit = 'contain';
                        }
                    }"
                />
            </div>
        </div>

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
                    <div class="space-y-1" v-if="safeArrayJoin(record?.periodical.authors)">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Authors</span>
                        <p class="break-words">{{ safeArrayJoin(record?.periodical.authors) }}</p>
                    </div>
                    <div class="space-y-1" v-if="safeArrayJoin(record?.periodical.editors)">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Editors</span>
                        <p class="break-words">{{ safeArrayJoin(record?.periodical.editors) }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.periodical.publication_year">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Publication Year</span>
                        <p>{{ record?.periodical.publication_year }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.periodical.publication_month">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Publication Month</span>
                        <p>{{ record?.periodical.publication_month }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.periodical.publisher">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Publisher</span>
                        <p class="break-words">{{ record?.periodical.publisher }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.periodical.volume_number">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Volume Number</span>
                        <p>{{ record?.periodical.volume_number }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.periodical.issue_number">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Issue Number</span>
                        <p>{{ record?.periodical.issue_number }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.periodical.issn">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">ISSN</span>
                        <p class="font-mono text-sm">{{ record?.periodical.issn }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.periodical.series_title">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Series Title</span>
                        <p class="break-words">{{ record?.periodical.series_title }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.periodical.call_number">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Call Number</span>
                        <p class="font-mono text-sm">{{ record?.periodical.call_number }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
