<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    newsletter: Object,
    eventCounts: Object,
});

const statusColors = {
    draft: 'bg-[#0b1215]/10 text-[#0b1215]/70',
    scheduled: 'bg-[#0b1215]/10 text-[#0b1215]/80',
    sending: 'bg-[#a1854f]/20 text-[#a1854f]',
    sent: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
};

const formatNumber = (num) => {
    if (num === null || num === undefined) return '0';
    return num.toLocaleString();
};

const sentPercentage = computed(() => {
    const total = props.newsletter.recipients_count || props.newsletter.total_recipients || 0;
    if (total === 0) return 0;
    return Math.round((props.newsletter.sent_count / total) * 100);
});

// Event breakdown for visualization
const eventBreakdown = computed(() => {
    const counts = props.eventCounts || {};
    const total = props.newsletter.recipients_count || props.newsletter.total_recipients || 1;

    const events = [
        { key: 'delivery', label: 'Delivered', count: counts.delivery || 0, color: 'bg-green-500' },
        { key: 'open', label: 'Opened', count: counts.open || 0, color: 'bg-blue-500' },
        { key: 'click', label: 'Clicked', count: counts.click || 0, color: 'bg-indigo-500' },
        { key: 'bounce', label: 'Bounced', count: counts.bounce || 0, color: 'bg-orange-500' },
        { key: 'complaint', label: 'Complained', count: counts.complaint || 0, color: 'bg-red-500' },
    ];

    // Calculate max for relative bar widths
    const maxCount = Math.max(...events.map(e => e.count), 1);

    return events.map(event => ({
        ...event,
        percentage: Math.round((event.count / total) * 100),
        barWidth: Math.round((event.count / maxCount) * 100),
    }));
});
</script>

<template>
    <Head :title="newsletter.subject_line" />

    <AppLayout current-page="newsletters">
        <div class="max-w-4xl mx-auto">
            <!-- Back Link & Header -->
            <div class="mb-8">
                <Link
                    href="/newsletters"
                    class="inline-flex items-center gap-2 text-sm text-[#0b1215]/50 hover:text-[#0b1215]/70 mb-4"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Newsletters
                </Link>

                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-[#0b1215]">
                            {{ newsletter.subject_line }}
                        </h1>
                        <p v-if="newsletter.post" class="text-[#0b1215]/60 mt-1">
                            From: <Link :href="`/posts/${newsletter.post.id}/edit`" class="text-[#a1854f] hover:underline">{{ newsletter.post.title }}</Link>
                        </p>
                        <p class="text-sm text-[#0b1215]/50 mt-2">
                            <template v-if="newsletter.sent_at">
                                Sent {{ newsletter.sent_at }}
                            </template>
                            <template v-else-if="newsletter.scheduled_at">
                                Scheduled for {{ newsletter.scheduled_at }}
                            </template>
                            <template v-else>
                                Created {{ newsletter.created_at }}
                            </template>
                        </p>
                    </div>
                    <span
                        :class="statusColors[newsletter.status]"
                        class="px-3 py-1 text-sm font-medium rounded-full capitalize"
                    >
                        {{ newsletter.status }}
                    </span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <!-- Recipients -->
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                    <div class="text-2xl font-bold text-[#0b1215]">
                        {{ formatNumber(newsletter.recipients_count || newsletter.total_recipients) }}
                    </div>
                    <div class="text-sm text-[#0b1215]/50">Recipients</div>
                </div>

                <!-- Sent -->
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                    <div class="text-2xl font-bold text-[#0b1215]">
                        {{ formatNumber(newsletter.sent_count) }}
                    </div>
                    <div class="text-sm text-[#0b1215]/50">
                        Sent
                        <span v-if="sentPercentage > 0" class="text-green-600">({{ sentPercentage }}%)</span>
                    </div>
                </div>

                <!-- Opens -->
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                    <div class="text-2xl font-bold text-green-600">
                        {{ formatNumber(newsletter.unique_opens) }}
                    </div>
                    <div class="text-sm text-[#0b1215]/50">
                        Opens
                        <span class="text-green-600">({{ newsletter.open_rate }}%)</span>
                    </div>
                    <div v-if="newsletter.opens !== newsletter.unique_opens" class="text-xs text-[#0b1215]/40 mt-1">
                        {{ formatNumber(newsletter.opens) }} total
                    </div>
                </div>

                <!-- Clicks -->
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                    <div class="text-2xl font-bold text-[#a1854f]">
                        {{ formatNumber(newsletter.unique_clicks) }}
                    </div>
                    <div class="text-sm text-[#0b1215]/50">
                        Clicks
                        <span class="text-[#a1854f]">({{ newsletter.click_rate }}%)</span>
                    </div>
                    <div v-if="newsletter.clicks !== newsletter.unique_clicks" class="text-xs text-[#0b1215]/40 mt-1">
                        {{ formatNumber(newsletter.clicks) }} total
                    </div>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                <!-- Failed -->
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                    <div class="text-2xl font-bold" :class="newsletter.failed_count > 0 ? 'text-red-600' : 'text-[#0b1215]'">
                        {{ formatNumber(newsletter.failed_count) }}
                    </div>
                    <div class="text-sm text-[#0b1215]/50">Failed</div>
                </div>

                <!-- Unsubscribes -->
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                    <div class="text-2xl font-bold" :class="newsletter.unsubscribes > 0 ? 'text-orange-600' : 'text-[#0b1215]'">
                        {{ formatNumber(newsletter.unsubscribes) }}
                    </div>
                    <div class="text-sm text-[#0b1215]/50">Unsubscribes</div>
                </div>

                <!-- Bounces (from event counts) -->
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-4">
                    <div class="text-2xl font-bold" :class="(eventCounts?.bounce || 0) > 0 ? 'text-orange-600' : 'text-[#0b1215]'">
                        {{ formatNumber(eventCounts?.bounce || 0) }}
                    </div>
                    <div class="text-sm text-[#0b1215]/50">Bounces</div>
                </div>
            </div>

            <!-- Event Breakdown -->
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                <h2 class="text-lg font-semibold text-[#0b1215] mb-4">Event Breakdown</h2>

                <div v-if="Object.keys(eventCounts || {}).length > 0" class="space-y-4">
                    <div v-for="event in eventBreakdown" :key="event.key" class="flex items-center gap-4">
                        <div class="w-24 text-sm text-[#0b1215]/60">{{ event.label }}</div>
                        <div class="flex-1 h-6 bg-[#f7f7f7] rounded-full overflow-hidden">
                            <div
                                :class="event.color"
                                class="h-full rounded-full transition-all duration-300"
                                :style="{ width: event.barWidth + '%' }"
                            ></div>
                        </div>
                        <div class="w-20 text-right text-sm font-medium text-[#0b1215]">
                            {{ formatNumber(event.count) }}
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-[#0b1215]/50">
                    No event data available yet
                </div>
            </div>

            <!-- Preview Text -->
            <div v-if="newsletter.preview_text" class="mt-8 bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                <h2 class="text-lg font-semibold text-[#0b1215] mb-2">Preview Text</h2>
                <p class="text-[#0b1215]/60">{{ newsletter.preview_text }}</p>
            </div>
        </div>
    </AppLayout>
</template>
