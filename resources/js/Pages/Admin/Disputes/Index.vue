<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    disputes: Array,
    pagination: Object,
    stats: Object,
    filters: Object,
});

const getStatusBadgeClass = (color) => {
    return {
        red: 'bg-red-100 text-red-700',
        yellow: 'bg-yellow-100 text-yellow-700',
        green: 'bg-green-100 text-green-700',
        gray: 'bg-gray-100 text-gray-700',
    }[color] || 'bg-gray-100 text-gray-700';
};

const formatCurrency = (cents) => {
    return '$' + (cents / 100).toLocaleString('en-US', { minimumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Payment Disputes - Admin" />

    <AdminLayout current-page="disputes">
        <div class="max-w-6xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-[#0b1215]">Payment Disputes</h1>
                <p class="text-[#0b1215]/60 mt-1">Monitor and manage all payment disputes across the platform</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-8">
                <div class="bg-white rounded-xl border border-[#0b1215]/10 p-4">
                    <p class="text-sm text-[#0b1215]/60">Total</p>
                    <p class="text-2xl font-bold text-[#0b1215]">{{ stats.total }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#0b1215]/10 p-4">
                    <p class="text-sm text-[#0b1215]/60">Open</p>
                    <p class="text-2xl font-bold text-[#0b1215]">{{ stats.open }}</p>
                </div>
                <div class="bg-white rounded-xl border border-red-200 p-4 bg-red-50">
                    <p class="text-sm text-red-600">Action Required</p>
                    <p class="text-2xl font-bold text-red-600">{{ stats.requiring_action }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#0b1215]/10 p-4">
                    <p class="text-sm text-[#0b1215]/60">Won</p>
                    <p class="text-2xl font-bold text-green-600">{{ stats.won }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#0b1215]/10 p-4">
                    <p class="text-sm text-[#0b1215]/60">Lost</p>
                    <p class="text-2xl font-bold text-gray-600">{{ stats.lost }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#0b1215]/10 p-4">
                    <p class="text-sm text-[#0b1215]/60">Total Disputed</p>
                    <p class="text-xl font-bold text-[#0b1215]">{{ formatCurrency(stats.total_amount_disputed) }}</p>
                </div>
                <div class="bg-white rounded-xl border border-[#0b1215]/10 p-4">
                    <p class="text-sm text-[#0b1215]/60">Total Lost</p>
                    <p class="text-xl font-bold text-red-600">{{ formatCurrency(stats.total_amount_lost) }}</p>
                </div>
            </div>

            <!-- Disputes List -->
            <div v-if="disputes.length > 0" class="bg-white rounded-2xl border border-[#0b1215]/10">
                <div class="px-6 py-4 border-b border-[#0b1215]/10">
                    <h2 class="text-lg font-semibold text-[#0b1215]">All Disputes</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#0b1215]/10">
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">Account</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">Reason</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">Evidence</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-[#0b1215]/60 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#0b1215]/10">
                            <tr v-for="dispute in disputes" :key="dispute.id" class="hover:bg-[#0b1215]/5">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-[#0b1215]">{{ dispute.account_name || 'Unknown' }}</p>
                                    <p class="text-xs text-[#0b1215]/50">{{ dispute.stripe_dispute_id }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-medium text-[#0b1215]">{{ dispute.amount_formatted }}</p>
                                    <p class="text-xs text-[#0b1215]/50">{{ dispute.currency }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-[#0b1215]">{{ dispute.reason_label }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="getStatusBadgeClass(dispute.status_color)"
                                    >
                                        {{ dispute.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="dispute.evidence_submitted" class="text-xs text-green-600">Submitted</span>
                                    <span v-else-if="dispute.is_evidence_overdue" class="text-xs text-red-600">Overdue</span>
                                    <span v-else-if="dispute.evidence_due_at_relative" class="text-xs text-orange-600">Due {{ dispute.evidence_due_at_relative }}</span>
                                    <span v-else class="text-xs text-[#0b1215]/40">-</span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-[#0b1215]">{{ dispute.disputed_at }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="`/admin/disputes/${dispute.id}`"
                                        class="text-sm font-medium text-[#a1854f] hover:text-[#a1854f]/80"
                                    >
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.last_page > 1" class="px-6 py-4 border-t border-[#0b1215]/10 flex items-center justify-between">
                    <p class="text-sm text-[#0b1215]/60">
                        Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} total)
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="pagination.current_page > 1"
                            :href="`/admin/disputes?page=${pagination.current_page - 1}`"
                            class="px-3 py-1 text-sm border border-[#0b1215]/20 rounded-lg hover:bg-[#0b1215]/5"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="pagination.current_page < pagination.last_page"
                            :href="`/admin/disputes?page=${pagination.current_page + 1}`"
                            class="px-3 py-1 text-sm border border-[#0b1215]/20 rounded-lg hover:bg-[#0b1215]/5"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>

            <!-- No Disputes -->
            <div v-else class="bg-white rounded-2xl border border-[#0b1215]/10 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-[#0b1215]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-[#0b1215]">No disputes</h3>
                <p class="mt-2 text-sm text-[#0b1215]/60">
                    No payment disputes have been received yet.
                </p>
            </div>
        </div>
    </AdminLayout>
</template>
