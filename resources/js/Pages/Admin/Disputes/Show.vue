<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    dispute: Object,
    account: Object,
    events: Array,
});

const getStatusBadgeClass = (color) => {
    return {
        red: 'bg-red-100 text-red-700',
        yellow: 'bg-yellow-100 text-yellow-700',
        green: 'bg-green-100 text-green-700',
        gray: 'bg-gray-100 text-gray-700',
    }[color] || 'bg-gray-100 text-gray-700';
};

const getEventIcon = (eventType) => {
    const icons = {
        'dispute.created': 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
        'dispute.updated': 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        'dispute.closed': 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'dispute.funds_withdrawn': 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'dispute.funds_reinstated': 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'evidence.gathered': 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'evidence.submitted': 'M5 13l4 4L19 7',
    };
    return icons[eventType] || 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
};

const getEventLabel = (eventType) => {
    const labels = {
        'dispute.created': 'Dispute Created',
        'dispute.updated': 'Status Updated',
        'dispute.closed': 'Dispute Closed',
        'dispute.funds_withdrawn': 'Funds Withdrawn',
        'dispute.funds_reinstated': 'Funds Reinstated',
        'evidence.gathered': 'Evidence Gathered',
        'evidence.submitted': 'Evidence Submitted',
    };
    return labels[eventType] || eventType;
};
</script>

<template>
    <Head title="Dispute Details - Admin" />

    <AdminLayout current-page="disputes">
        <div class="max-w-4xl mx-auto">
            <div class="mb-8">
                <div class="flex items-center gap-2 text-sm text-[#0b1215]/60 mb-2">
                    <Link href="/admin/disputes" class="hover:text-[#0b1215]">Disputes</Link>
                    <span>/</span>
                    <span>Details</span>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-[#0b1215]">Dispute Details</h1>
                        <p class="text-[#0b1215]/60 mt-1">{{ dispute.stripe_dispute_id }}</p>
                    </div>
                    <span
                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                        :class="getStatusBadgeClass(dispute.status_color)"
                    >
                        {{ dispute.status_label }}
                    </span>
                </div>
            </div>

            <!-- Alert for action required -->
            <div v-if="dispute.requires_action && !dispute.is_evidence_overdue" class="mb-6 bg-orange-50 border border-orange-200 rounded-xl p-4">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-orange-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-orange-800">Response Required</p>
                        <p class="text-sm text-orange-700 mt-1">
                            Evidence must be submitted by {{ dispute.evidence_due_at }}.
                            Auto-evidence gathering should have been triggered.
                        </p>
                    </div>
                </div>
            </div>

            <div v-else-if="dispute.is_evidence_overdue" class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-red-800">Evidence Deadline Passed</p>
                        <p class="text-sm text-red-700 mt-1">
                            The deadline to submit evidence has passed.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Account Information -->
                <div v-if="account" class="bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                    <h2 class="text-lg font-semibold text-[#0b1215] mb-4">Account Information</h2>
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-sm text-[#0b1215]/60">Account Name</dt>
                            <dd class="text-sm font-medium text-[#0b1215]">{{ account.name }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-[#0b1215]/60">Admin Email</dt>
                            <dd class="text-sm font-medium text-[#0b1215]">{{ account.email || 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-[#0b1215]/60">Account Created</dt>
                            <dd class="text-sm font-medium text-[#0b1215]">{{ account.created_at }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Dispute Information -->
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                    <h2 class="text-lg font-semibold text-[#0b1215] mb-4">Dispute Information</h2>
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-sm text-[#0b1215]/60">Amount</dt>
                            <dd class="text-sm font-medium text-[#0b1215]">{{ dispute.amount_formatted }} {{ dispute.currency }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-[#0b1215]/60">Reason</dt>
                            <dd class="text-sm font-medium text-[#0b1215]">{{ dispute.reason_label }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-[#0b1215]/60">Disputed Date</dt>
                            <dd class="text-sm font-medium text-[#0b1215]">{{ dispute.disputed_at }}</dd>
                        </div>
                        <div v-if="dispute.evidence_due_at" class="flex justify-between">
                            <dt class="text-sm text-[#0b1215]/60">Evidence Due</dt>
                            <dd class="text-sm font-medium" :class="dispute.is_evidence_overdue ? 'text-red-600' : 'text-[#0b1215]'">
                                {{ dispute.evidence_due_at }}
                            </dd>
                        </div>
                        <div v-if="dispute.resolved_at" class="flex justify-between">
                            <dt class="text-sm text-[#0b1215]/60">Resolved Date</dt>
                            <dd class="text-sm font-medium text-[#0b1215]">{{ dispute.resolved_at }}</dd>
                        </div>
                        <div v-if="dispute.resolution" class="flex justify-between">
                            <dt class="text-sm text-[#0b1215]/60">Resolution</dt>
                            <dd class="text-sm font-medium capitalize" :class="dispute.resolution === 'won' ? 'text-green-600' : 'text-gray-600'">
                                {{ dispute.resolution }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Status Information -->
                <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6 lg:col-span-2">
                    <h2 class="text-lg font-semibold text-[#0b1215] mb-4">Status</h2>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="flex justify-between items-center p-3 bg-[#0b1215]/5 rounded-lg">
                            <span class="text-sm text-[#0b1215]/60">Evidence Submitted</span>
                            <span
                                v-if="dispute.evidence_submitted"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700"
                            >
                                Yes
                            </span>
                            <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                Pending
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-[#0b1215]/5 rounded-lg">
                            <span class="text-sm text-[#0b1215]/60">Funds Withdrawn</span>
                            <span
                                v-if="dispute.funds_withdrawn"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700"
                            >
                                Yes
                            </span>
                            <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                No
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-[#0b1215]/5 rounded-lg">
                            <span class="text-sm text-[#0b1215]/60">Funds Reinstated</span>
                            <span
                                v-if="dispute.funds_reinstated"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700"
                            >
                                Yes
                            </span>
                            <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                No
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Timeline -->
            <div class="mt-6 bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                <h2 class="text-lg font-semibold text-[#0b1215] mb-4">Activity Timeline</h2>
                <div v-if="events.length > 0" class="relative">
                    <div class="absolute left-4 top-0 bottom-0 w-px bg-[#0b1215]/10"></div>
                    <ul class="space-y-4">
                        <li v-for="event in events" :key="event.id" class="relative pl-10">
                            <div class="absolute left-0 w-8 h-8 rounded-full bg-[#0b1215]/5 flex items-center justify-center">
                                <svg class="w-4 h-4 text-[#0b1215]/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getEventIcon(event.event_type)" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-[#0b1215]">{{ getEventLabel(event.event_type) }}</p>
                                <p class="text-sm text-[#0b1215]/60">{{ event.event_at }} ({{ event.event_at_relative }})</p>
                            </div>
                        </li>
                    </ul>
                </div>
                <p v-else class="text-sm text-[#0b1215]/60">No events recorded yet.</p>
            </div>

            <!-- Back Link -->
            <div class="mt-6">
                <Link
                    href="/admin/disputes"
                    class="text-sm font-medium text-[#a1854f] hover:text-[#a1854f]/80"
                >
                    &larr; Back to all disputes
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
