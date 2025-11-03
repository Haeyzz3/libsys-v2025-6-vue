<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { EyeOff } from 'lucide-vue-next';
import { useUserStatistics } from '@/composables/useUserStatistics';

const sidebarNavItems: NavItem[] = [
    { title: 'All Patrons', href: '/users/all' },
    { title: 'Undergraduate', href: '/users/undergraduate' },
    { title: 'Graduate', href: '/users/graduate' },
    { title: 'Faculty', href: '/users/faculties' },
    { title: 'Staff', href: '/users/staff' },
];

const rightNavItems: NavItem[] = [{ title: 'Options', href: '/users/options' }];

const page = usePage();
if (page.props.auth.permissions.can_view_any_users) {
    sidebarNavItems.push({ title: 'Library Staff', href: '/users/admins' });
    // rightNavItems.unshift({ title: 'Import', href: '/users/import' });
}

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';

// Carousel functionality
const showCarousel = ref(true)

// Use the composable to get real user statistics
const { statistics, loading } = useUserStatistics()

const carouselCards = computed(() => [
    {
        label: 'Total Patrons',
        value: statistics.value.total_patrons,
        color: 'border-[#800000]',
        bgColor: 'bg-[#800000]/10',
        textColor: 'text-[#800000]',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2a2 2 0 012-2h4a2 2 0 012 2z',
    },
    {
        label: 'Undergraduate Students',
        value: statistics.value.undergraduate_students,
        color: 'border-[#FFD700]',
        bgColor: 'bg-[#FFD700]/20',
        textColor: 'text-[#B8860B]',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2a2 2 0 012-2h4a2 2 0 012 2z',
    },
    {
        label: 'Graduate Students',
        value: statistics.value.graduate_students,
        color: 'border-[#800000]',
        bgColor: 'bg-[#800000]/10',
        textColor: 'text-[#800000]',
        icon: 'M12 14l9-5-9-5-9 5 9 5zm0 0v6m0 0H6m6 0h6',
    },
    {
        label: 'All Faculty',
        value: statistics.value.all_faculty,
        color: 'border-[#FFD700]',
        bgColor: 'bg-[#FFD700]/20',
        textColor: 'text-[#B8860B]',
        icon: 'M9 6V4a2 2 0 012-2h2a2 2 0 012 2v2m-8 0h12a2 2 0 012 2v2H3V8a2 2 0 012-2zm16 4v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6m16 0H3',
    },
    {
        label: 'All Staff',
        value: statistics.value.all_staff,
        color: 'border-[#800000]',
        bgColor: 'bg-[#800000]/10',
        textColor: 'text-[#800000]',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2a2 2 0 012-2h4a2 2 0 012 2z',
    },
    {
        label: 'All Library Staff',
        value: statistics.value.all_library_staff,
        color: 'border-[#FFD700]',
        bgColor: 'bg-[#FFD700]/20',
        textColor: 'text-[#B8860B]',
        icon: 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0zm6 4v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2a2 2 0 012-2h4a2 2 0 012 2z',
    }
]);

// Carousel dynamic width logic
const cardSetRef = ref<HTMLElement | null>(null)
const loopCardsRef = ref<HTMLElement | null>(null)
const animationDistance = ref(0)

function updateAnimationDistance() {
    nextTick(() => {
        if (cardSetRef.value && loopCardsRef.value) {
            const width = cardSetRef.value.offsetWidth
            animationDistance.value = width
            loopCardsRef.value.style.setProperty('--cards-loop-distance', width + 'px')
        }
    })
}

onMounted(() => {
    updateAnimationDistance()
})

watch(carouselCards, () => {
    updateAnimationDistance()
}, { deep: true })

</script>

<template>
    <div class="p-4 pt-2">
        <header class="flex flex-wrap items-center justify-between gap-2">
            <nav class="flex flex-wrap gap-1">
                <Button v-for="item in sidebarNavItems" :key="item.href" variant="ghost" size="sm" :class="{ 'bg-primary text-background': currentPath.startsWith(item.href) }" as-child>
                    <Link :href="item.href">{{ item.title }}</Link>
                </Button>
            </nav>
            <nav class="flex flex-wrap gap-1">
                <Button v-for="item in rightNavItems" :key="item.href" variant="ghost" size="sm" :class="{ 'bg-primary text-background': currentPath.startsWith(item.href) }" as-child>
                    <Link :href="item.href">{{ item.title }}</Link>
                </Button>
            </nav>
        </header>

        <div class="flex justify-end mb-2">
            <button
                @click="showCarousel = !showCarousel"
                class="px-3 py-1 rounded-lg text-sm font-medium flex items-center gap-1"
                :class="showCarousel ? 'bg-[#800000] text-white' : 'bg-[#FFD700] text-[#800000]'"
            >
                <EyeOff v-if="!showCarousel" class="h-4 w-4" />
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                {{ showCarousel ? 'Hide Cards' : 'Show Cards' }}
            </button>
        </div>

        <div v-if="showCarousel" class="relative w-full overflow-x-hidden flex items-center mb-6" style="min-height: 100px;">
            <div ref="loopCardsRef" class="loop-cards flex flex-row flex-nowrap gap-4 animate-cards-loop items-center" style="will-change: transform;">
                <div ref="cardSetRef" class="flex flex-row flex-nowrap gap-4 items-center">
                    <div v-for="(card, index) in carouselCards" :key="index" class="flex-shrink-0 min-w-[220px] bg-white rounded-2xl shadow-lg p-3 border-2 flex items-center gap-3 hover:shadow-xl transition-shadow relative" :class="card.color">
                        <div class="flex items-center justify-center h-8 w-8 rounded-full" :class="card.bgColor">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" :class="card.textColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-semibold mb-1 tracking-wide whitespace-nowrap" :class="card.textColor">{{ card.label }}</div>
                            <div class="text-2xl font-extrabold" :class="card.textColor">{{ card.value }}</div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-row flex-nowrap gap-4 items-center">
                    <div v-for="(card, index) in carouselCards" :key="'dup-' + index" class="flex-shrink-0 min-w-[220px] bg-white rounded-2xl shadow-lg p-3 border-2 flex items-center gap-3 hover:shadow-xl transition-shadow relative" :class="card.color">
                        <div class="flex items-center justify-center h-8 w-8 rounded-full" :class="card.bgColor">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" :class="card.textColor" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="card.icon" />
                            </svg>
                        </div>
                        <div>
                            <div class="text-xs font-semibold mb-1 tracking-wide whitespace-nowrap" :class="card.textColor">{{ card.label }}</div>
                            <div class="text-2xl font-extrabold" :class="card.textColor">{{ card.value }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <main class="min-h-0"><slot /></main>
    </div>
</template>

<style scoped>
.loop-cards {
    animation: cards-loop 40s linear infinite;
}

@keyframes cards-loop {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(calc(-1 * var(--cards-loop-distance, 800px)));
    }
}

.animate-cards-loop {
    animation: cards-loop 40s linear infinite;
}

/* Pause animation on hover */
.loop-cards:hover {
    animation-play-state: paused;
}
</style>
