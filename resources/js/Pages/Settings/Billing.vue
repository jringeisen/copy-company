<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from '@/Components/Button.vue';

const props = defineProps({
    usage: Object,
    subscription: Object,
    invoices: Array,
    has_payment_method: Boolean,
});

const cancelSubscription = () => {
    if (!confirm('Are you sure you want to cancel your subscription? You will continue to have access until the end of your billing period.')) {
        return;
    }
    router.post('/billing/cancel');
};

const resumeSubscription = () => {
    router.post('/billing/resume');
};

const formatLimit = (limit) => {
    return limit === null ? 'Unlimited' : limit;
};

const getUsagePercentage = (used, limit) => {
    if (limit === null) return 0;
    return Math.min(100, (used / limit) * 100);
};

const getUsageColor = (used, limit) => {
    if (limit === null) return 'bg-[#a1854f]';
    const percentage = (used / limit) * 100;
    if (percentage >= 90) return 'bg-red-500';
    if (percentage >= 70) return 'bg-yellow-500';
    return 'bg-[#a1854f]';
};
</script>

<template>
    <Head title="Billing Settings" />

    <AppLayout current-page="billing">
        <div class="max-w-3xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-[#0b1215]">Billing & Subscription</h1>
                <p class="text-[#0b1215]/60 mt-1">Manage your subscription and view usage</p>
            </div>

            <!-- Current Plan -->
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6 mb-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-[#0b1215]">Current Plan</h2>
                        <div class="flex items-center gap-3 mt-2">
                            <span class="text-2xl font-bold text-[#0b1215]">{{ usage.plan_label }}</span>
                            <span
                                v-if="usage.on_trial"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#a1854f]/10 text-[#a1854f]"
                            >
                                Trial ends {{ usage.trial_ends_at ? new Date(usage.trial_ends_at).toLocaleDateString() : 'soon' }}
                            </span>
                            <span
                                v-else-if="subscription?.canceled"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700"
                            >
                                Cancels {{ subscription.ends_at }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            v-if="!subscription || subscription?.canceled"
                            href="/billing/subscribe"
                        >
                            {{ subscription?.canceled ? 'Choose New Plan' : 'Subscribe' }}
                        </Button>
                        <a
                            v-else
                            href="/billing/portal"
                            class="px-4 py-2 border border-[#0b1215]/20 text-[#0b1215] font-medium rounded-xl hover:bg-[#0b1215]/5 transition"
                        >
                            Manage Subscription
                        </a>
                    </div>
                </div>

                <!-- Resume/Cancel buttons -->
                <div v-if="subscription?.on_grace_period" class="mt-4 pt-4 border-t border-[#0b1215]/10">
                    <p class="text-sm text-[#0b1215]/60 mb-3">
                        Your subscription has been canceled. You can resume it before {{ subscription.ends_at }}.
                    </p>
                    <button
                        @click="resumeSubscription"
                        class="px-4 py-2.5 bg-[#a1854f] text-white font-medium rounded-full hover:bg-[#a1854f]/90 transition text-sm"
                    >
                        Resume Subscription
                    </button>
                </div>

                <div v-else-if="subscription && !subscription.canceled" class="mt-4 pt-4 border-t border-[#0b1215]/10">
                    <button
                        @click="cancelSubscription"
                        class="text-sm text-red-600 hover:text-red-700"
                    >
                        Cancel subscription
                    </button>
                </div>
            </div>

            <!-- Usage Overview -->
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6 mb-6">
                <h2 class="text-lg font-semibold text-[#0b1215] mb-6">Usage This Month</h2>

                <div class="space-y-6">
                    <!-- Posts Usage -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-[#0b1215]">Posts</span>
                            <span class="text-sm text-[#0b1215]/60">
                                {{ usage.posts.used }} / {{ formatLimit(usage.posts.limit) }}
                            </span>
                        </div>
                        <div class="h-2 bg-[#0b1215]/10 rounded-full overflow-hidden">
                            <div
                                :class="getUsageColor(usage.posts.used, usage.posts.limit)"
                                class="h-full rounded-full transition-all"
                                :style="{ width: `${usage.posts.limit === null ? 30 : getUsagePercentage(usage.posts.used, usage.posts.limit)}%` }"
                            ></div>
                        </div>
                        <p v-if="usage.posts.limit !== null && usage.posts.remaining <= 1" class="text-xs text-orange-600 mt-1">
                            {{ usage.posts.remaining === 0 ? 'Post limit reached' : `Only ${usage.posts.remaining} post remaining` }}
                        </p>
                    </div>

                    <!-- Content Sprints Usage -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-[#0b1215]">AI Content Sprints</span>
                            <span class="text-sm text-[#0b1215]/60">
                                {{ usage.content_sprints.used }} / {{ formatLimit(usage.content_sprints.limit) }}
                            </span>
                        </div>
                        <div class="h-2 bg-[#0b1215]/10 rounded-full overflow-hidden">
                            <div
                                :class="getUsageColor(usage.content_sprints.used, usage.content_sprints.limit)"
                                class="h-full rounded-full transition-all"
                                :style="{ width: `${usage.content_sprints.limit === null ? 30 : getUsagePercentage(usage.content_sprints.used, usage.content_sprints.limit)}%` }"
                            ></div>
                        </div>
                        <p v-if="usage.content_sprints.limit !== null && usage.content_sprints.remaining <= 1" class="text-xs text-orange-600 mt-1">
                            {{ usage.content_sprints.remaining === 0 ? 'Sprint limit reached' : `Only ${usage.content_sprints.remaining} sprint remaining` }}
                        </p>
                    </div>

                    <!-- Social Accounts Usage -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-[#0b1215]">Social Accounts</span>
                            <span class="text-sm text-[#0b1215]/60">
                                {{ usage.social_accounts.used }} / {{ usage.social_accounts.limit }}
                            </span>
                        </div>
                        <div class="h-2 bg-[#0b1215]/10 rounded-full overflow-hidden">
                            <div
                                :class="getUsageColor(usage.social_accounts.used, usage.social_accounts.limit)"
                                class="h-full rounded-full transition-all"
                                :style="{ width: `${getUsagePercentage(usage.social_accounts.used, usage.social_accounts.limit)}%` }"
                            ></div>
                        </div>
                    </div>

                    <!-- Email Usage -->
                    <div v-if="usage.emails" class="pt-4 border-t border-[#0b1215]/10">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-[#0b1215]">Newsletter Emails Sent</span>
                            <span class="text-sm text-[#0b1215]/60">
                                {{ usage.emails.sent.toLocaleString() }} emails
                            </span>
                        </div>
                        <div class="bg-[#0b1215]/5 rounded-xl p-4 mt-2">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-[#0b1215]/60">Estimated cost this period</p>
                                    <p class="text-xl font-semibold text-[#0b1215]">${{ usage.emails.estimated_cost.toFixed(2) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-[#0b1215]/40">Rate</p>
                                    <p class="text-sm text-[#0b1215]/60">${{ usage.emails.cost_per_thousand.toFixed(2) }} / 1,000 emails</p>
                                </div>
                            </div>
                            <p v-if="usage.emails.sent > 0" class="text-xs text-[#0b1215]/40 mt-3">
                                This amount will be added to your next invoice based on actual usage.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="mt-8 pt-6 border-t border-[#0b1215]/10">
                    <h3 class="text-sm font-medium text-[#0b1215] mb-4">Plan Features</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center gap-2">
                            <svg
                                v-if="usage.features.custom_domain"
                                class="w-5 h-5 text-green-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-5 h-5 text-[#0b1215]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="text-sm" :class="usage.features.custom_domain ? 'text-[#0b1215]' : 'text-[#0b1215]/50'">
                                Custom Domain
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg
                                v-if="usage.features.custom_email_domain"
                                class="w-5 h-5 text-green-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-5 h-5 text-[#0b1215]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="text-sm" :class="usage.features.custom_email_domain ? 'text-[#0b1215]' : 'text-[#0b1215]/50'">
                                Custom Email Domain
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg
                                v-if="usage.features.analytics"
                                class="w-5 h-5 text-green-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-5 h-5 text-[#0b1215]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="text-sm" :class="usage.features.analytics ? 'text-[#0b1215]' : 'text-[#0b1215]/50'">
                                Analytics
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg
                                v-if="usage.features.remove_branding"
                                class="w-5 h-5 text-green-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-5 h-5 text-[#0b1215]/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <span class="text-sm" :class="usage.features.remove_branding ? 'text-[#0b1215]' : 'text-[#0b1215]/50'">
                                Remove Branding
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Upgrade CTA -->
                <div v-if="usage.plan !== 'pro'" class="mt-6 pt-6 border-t border-[#0b1215]/10">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-[#0b1215]/60">
                            Upgrade your plan to unlock more features
                        </p>
                        <Link
                            href="/billing/subscribe"
                            class="text-sm font-medium text-[#a1854f] hover:text-[#a1854f]/80"
                        >
                            View Plans
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Invoice History -->
            <div v-if="invoices.length > 0" class="bg-white rounded-2xl border border-[#0b1215]/10">
                <div class="px-6 py-4 border-b border-[#0b1215]/10">
                    <h2 class="text-lg font-semibold text-[#0b1215]">Invoice History</h2>
                </div>
                <ul class="divide-y divide-[#0b1215]/10">
                    <li
                        v-for="invoice in invoices"
                        :key="invoice.id"
                        class="px-6 py-4 flex items-center justify-between"
                    >
                        <div>
                            <p class="text-sm font-medium text-[#0b1215]">{{ invoice.date }}</p>
                            <p class="text-sm text-[#0b1215]/50">{{ invoice.total }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                :class="{
                                    'bg-green-100 text-green-700': invoice.status === 'paid',
                                    'bg-yellow-100 text-yellow-700': invoice.status === 'open',
                                    'bg-red-100 text-red-700': invoice.status === 'uncollectible',
                                }"
                            >
                                {{ invoice.status }}
                            </span>
                            <a
                                :href="invoice.download_url"
                                class="text-sm font-medium text-[#a1854f] hover:text-[#a1854f]/80"
                            >
                                Download
                            </a>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- No invoices -->
            <div v-else class="bg-white rounded-2xl border border-[#0b1215]/10 p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-[#0b1215]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-[#0b1215]">No invoices yet</h3>
                <p class="mt-1 text-sm text-[#0b1215]/50">Invoices will appear here after your first payment.</p>
            </div>
        </div>
    </AppLayout>
</template>
