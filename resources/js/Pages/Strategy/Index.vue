<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    strategies: Array,
});

const pollInterval = ref(null);

const currentWeekStrategy = computed(() => {
    return props.strategies?.find(s => ['pending', 'generating'].includes(s.status) || s.status === 'completed') || null;
});

const hasGenerating = computed(() => {
    return props.strategies?.some(s => ['pending', 'generating'].includes(s.status)) || false;
});

const statusBadgeClass = (status) => {
    return {
        pending: 'bg-gray-100 text-gray-700',
        generating: 'bg-yellow-100 text-yellow-700',
        completed: 'bg-green-100 text-green-700',
        failed: 'bg-red-100 text-red-700',
    }[status] || 'bg-gray-100 text-gray-700';
};

onMounted(() => {
    if (hasGenerating.value) {
        pollInterval.value = setInterval(() => {
            router.reload({ only: ['strategies'] });
        }, 3000);
    }
});

onUnmounted(() => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
    }
});
</script>

<template>
    <Head title="Marketing Strategy" />

    <AppLayout current-page="strategy">
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-[#0b1215]">Marketing Strategy</h1>
                <p class="mt-1 text-sm text-[#0b1215]/60">
                    AI-generated weekly content plans tailored to your brand.
                </p>
            </div>

            <!-- Strategies List -->
            <div v-if="strategies?.length > 0" class="space-y-4">
                <Link
                    v-for="strategy in strategies"
                    :key="strategy.id"
                    :href="`/strategies/${strategy.id}`"
                    class="block bg-white rounded-2xl border border-[#0b1215]/10 p-6 hover:border-[#0b1215]/20 transition"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-[#0b1215] truncate">
                                    {{ strategy.theme_title || `Week of ${strategy.week_start}` }}
                                </h3>
                                <span
                                    :class="statusBadgeClass(strategy.status)"
                                    class="px-3 py-1 text-xs font-medium rounded-full capitalize shrink-0"
                                >
                                    {{ strategy.status }}
                                </span>
                            </div>
                            <p class="text-sm text-[#0b1215]/50">
                                {{ strategy.week_start }} - {{ strategy.week_end }}
                            </p>
                            <div v-if="strategy.status === 'completed'" class="mt-3 flex items-center gap-4 text-sm text-[#0b1215]/60">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    {{ strategy.blog_posts_count }} blog posts
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                    </svg>
                                    {{ strategy.social_posts_count }} social posts
                                </span>
                                <span v-if="strategy.loops_count > 0" class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ strategy.loops_count }} loops
                                </span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-[#0b1215]/30 shrink-0 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12 bg-white rounded-2xl border border-[#0b1215]/10">
                <svg class="mx-auto h-12 w-12 text-[#0b1215]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-[#0b1215]">No strategies yet</h3>
                <p class="mt-2 text-sm text-[#0b1215]/50 max-w-sm mx-auto">
                    Weekly marketing strategies are generated automatically every Sunday. Check back soon for your first personalized strategy.
                </p>
            </div>
        </div>
    </AppLayout>
</template>
