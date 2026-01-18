<script setup>
import { Head, Link, InfiniteScroll } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePageLoading } from '@/Composables/usePageLoading';

const props = defineProps({
    newsletters: Object,
});

const { isLoading } = usePageLoading();

const statusColors = {
    draft: 'bg-[#0b1215]/10 text-[#0b1215]/70',
    scheduled: 'bg-[#a1854f]/20 text-[#a1854f]',
    sending: 'bg-[#a1854f]/20 text-[#a1854f]',
    sent: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    return num.toLocaleString();
};
</script>

<template>
    <Head title="Newsletters" />

    <AppLayout current-page="newsletters">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-[#0b1215]">Newsletters</h1>
                <p class="text-[#0b1215]/60 mt-1">View performance of sent newsletters</p>
            </div>

            <!-- Newsletter List -->
            <div v-if="isLoading" class="space-y-4">
                <!-- Skeleton cards -->
                <div v-for="i in 4" :key="i" class="bg-white rounded-2xl border border-[#0b1215]/10 p-6 animate-pulse">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="h-5 bg-[#0b1215]/10 rounded w-64"></div>
                                <div class="h-6 w-16 bg-[#0b1215]/10 rounded-full"></div>
                            </div>
                            <div class="h-4 bg-[#0b1215]/10 rounded w-48 mb-3"></div>
                            <div class="flex flex-wrap items-center gap-4">
                                <div class="h-4 bg-[#0b1215]/10 rounded w-24"></div>
                                <div class="h-4 bg-[#0b1215]/10 rounded w-20"></div>
                                <div class="h-4 bg-[#0b1215]/10 rounded w-20"></div>
                            </div>
                        </div>
                        <div class="h-4 bg-[#0b1215]/10 rounded w-32 ml-4"></div>
                    </div>
                </div>
            </div>
            <InfiniteScroll v-else-if="newsletters.data.length > 0" data="newsletters" only-next class="space-y-4">
                <Link
                    v-for="newsletter in newsletters.data"
                    :key="newsletter.id"
                    :href="`/newsletters/${newsletter.id}`"
                    class="block bg-white rounded-2xl border border-[#0b1215]/10 p-6 hover:border-[#0b1215]/30 transition"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="font-semibold text-[#0b1215] truncate">
                                    {{ newsletter.subject_line }}
                                </h3>
                                <span
                                    :class="statusColors[newsletter.status]"
                                    class="px-2 py-0.5 text-xs font-medium rounded-full capitalize shrink-0"
                                >
                                    {{ newsletter.status }}
                                </span>
                            </div>
                            <p v-if="newsletter.post" class="text-sm text-[#0b1215]/50 mb-3">
                                From: {{ newsletter.post.title }}
                            </p>

                            <!-- Stats Row -->
                            <div class="flex flex-wrap items-center gap-4 text-sm">
                                <span class="text-[#0b1215]/60">
                                    {{ formatNumber(newsletter.recipients_count || newsletter.total_recipients) }} recipients
                                </span>
                                <template v-if="newsletter.status === 'sent'">
                                    <span class="text-[#0b1215]/30">|</span>
                                    <span class="text-[#0b1215]/60">
                                        <span class="font-medium text-green-600">{{ newsletter.open_rate }}%</span> opened
                                    </span>
                                    <span class="text-[#0b1215]/30">|</span>
                                    <span class="text-[#0b1215]/60">
                                        <span class="font-medium text-[#a1854f]">{{ newsletter.click_rate }}%</span> clicked
                                    </span>
                                </template>
                                <template v-else-if="newsletter.status === 'sending'">
                                    <span class="text-[#0b1215]/30">|</span>
                                    <span class="text-[#a1854f]">
                                        {{ formatNumber(newsletter.sent_count) }} / {{ formatNumber(newsletter.recipients_count || newsletter.total_recipients) }} sent
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="text-sm text-[#0b1215]/50 shrink-0 ml-4 text-right">
                            <template v-if="newsletter.sent_at">
                                Sent {{ newsletter.sent_at }}
                            </template>
                            <template v-else-if="newsletter.scheduled_at">
                                Scheduled {{ newsletter.scheduled_at }}
                            </template>
                            <template v-else>
                                {{ newsletter.created_at }}
                            </template>
                        </div>
                    </div>
                </Link>
            </InfiniteScroll>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white rounded-2xl border border-[#0b1215]/10">
                <svg class="mx-auto h-12 w-12 text-[#0b1215]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-[#0b1215]">No newsletters sent yet</h3>
                <p class="mt-2 text-sm text-[#0b1215]/50">
                    Newsletters will appear here after you send your first one.
                </p>
                <Link
                    href="/posts"
                    class="mt-4 inline-block px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                >
                    Create a Post
                </Link>
            </div>

        </div>
    </AppLayout>
</template>
