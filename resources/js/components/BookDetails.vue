<script setup lang="ts">
import { getStatusStyle } from '../utils/statusColors';

defineProps({
    record: Object,
});

const getCoverUrl = (path: string) => {
    return path ? `/records/covers/${path}` : '/storage/placeholders/sample3.svg';
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
                    :src="getCoverUrl(record?.book.cover_image)"
                    alt="Book Cover"
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
                    <div class="space-y-1" v-if="record?.book.authors">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Authors</span>
                        <p class="break-words">{{ record?.book.authors }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.book.editors">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Editors</span>
                        <p class="break-words">{{ record?.book.editors }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.book.isbn">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">ISBN</span>
                        <p class="font-mono text-sm">{{ record?.book.isbn }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.book.publication_year">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Publication Year</span>
                        <p>{{ record?.book.publication_year }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.book.call_number">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Call Number</span>
                        <p class="font-mono text-sm">{{ record?.book.call_number }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.book.publisher">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Publisher</span>
                        <p class="break-words">{{ record?.book.publisher }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.book.volume">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Volume</span>
                        <p>{{ record?.book.volume }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.book.publication_place">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Publication Place</span>
                        <p class="break-words">{{ record?.book.publication_place }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.book.ddc_class_id">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">DDC Class ID</span>
                        <p>{{ record?.book.ddc_class_id }}</p>
                    </div>
                    <div class="space-y-1" v-if="record?.book.physical_location_id">
                        <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Physical Location ID</span>
                        <p>{{ record?.book.physical_location_id }}</p>
                    </div>
                </div>

                <!-- Subject headings - full width -->
                <div class="space-y-2" v-if="record?.subject_headings">
                    <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Subject Headings</span>
                    <p class="break-words leading-relaxed">{{ record?.subject_headings }}</p>
                </div>

                <!-- Table of contents - full width -->
                <div class="space-y-2" v-if="record?.book.table_of_contents">
                    <span class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Table of Contents</span>
                    <div class="bg-muted/30 rounded-lg p-4">
                        <p class="whitespace-pre-wrap break-words leading-relaxed text-sm">{{ record?.book.table_of_contents }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
