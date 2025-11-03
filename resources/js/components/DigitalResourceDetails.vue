<script setup lang="ts">
import { getStatusStyle } from '../utils/statusColors';

defineProps({
    record: Object,
});

const getCoverUrl = (path: string) => {
    return path ? `/storage/uploads/resource-covers/${path}` : '/storage/placeholders/sample3.svg';
};
</script>

<template>
    <div class="grid gap-6 h-full max-h-9/10 sm:grid-cols-2 sm:max-w-6xl justify-between">
        <div class="flex items-center justify-center">
            <img
                class="max-w-xs rounded-lg shadow-md"
                loading="lazy"
                :src="getCoverUrl(record?.digital_resource.cover_image)"
                alt="Digital Resource Cover"
            />
        </div>

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
                    <p>{{ record?.digital_resource.authors?.join(', ') }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Editors:</span>
                    <p>{{ record?.digital_resource.editors?.join(', ') }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Publication Year:</span>
                    <p>{{ record?.digital_resource.publication_year }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Copyright Year:</span>
                    <p>{{ record?.digital_resource.copyright_year }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Producer:</span>
                    <p>{{ record?.digital_resource.producer }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Language:</span>
                    <p>{{ record?.digital_resource.language }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Collection Type:</span>
                    <p>{{ record?.digital_resource.collection_type }}</p>
                </div>
                <div class="space-y-1">
                    <span class="font-semibold">Duration:</span>
                    <p>{{ record?.digital_resource.duration }}</p>
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
                <span class="font-semibold">Overview:</span>
                <p class="whitespace-pre-wrap">{{ record?.digital_resource.overview }}</p>
            </div>
        </div>
    </div>
</template>
