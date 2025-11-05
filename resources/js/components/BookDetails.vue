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
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 h-full">
        <!-- Fixed left column for cover image -->
        <div class="flex items-start justify-center top-0">
            <img
                class="max-w-xs rounded-lg shadow-md sticky top-6"
                loading="lazy"
                :src="getCoverUrl(record?.book.cover_image)"
                alt="Book Cover"
            />
        </div>

        <!-- Scrollable right column for details -->
        <div class="space-y-6 overflow-y-auto h-[80vh] pr-4">
            <div>
                <h2 class="text-2xl font-bold">{{ record?.title }}</h2>
                <div class="flex my-4 gap-2">
                    <p class="text-muted-foreground">{{ record?.accession_number }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="space-y-1">
                    <span class="font-semibold">Authors:</span>
                    <p>{{ record?.book.authors }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Editors:</span>
                    <p>{{ record?.book.editors }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">ISBN:</span>
                    <p>{{ record?.book.isbn }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Publication Year:</span>
                    <p>{{ record?.book.publication_year }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Call Number:</span>
                    <p>{{ record?.book.call_number }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Publisher:</span>
                    <p>{{ record?.book.publisher }}</p>
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
                <div class="space-y-1">
                    <span class="font-semibold">Volume:</span>
                    <p>{{ record?.book.volume }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Publication Place:</span>
                    <p>{{ record?.book.publication_place }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">DDC Class ID:</span>
                    <p>{{ record?.book.ddc_class_id }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Physical Location ID:</span>
                    <p>{{ record?.book.physical_location_id }}</p>
                </div>
            </div>

            <div class="space-y-1">
                <span class="font-semibold">Subject Headings:</span>
                <p>{{ record?.subject_headings }}</p>
            </div>

            <div class="space-y-1">
                <span class="font-semibold">Table of Contents:</span>
                <p class="whitespace-pre-wrap">{{ record?.book.table_of_contents }}</p>
            </div>
        </div>
    </div>
</template>
