<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    plans: Array,
    current_plan: String,
    on_trial: Boolean,
    trial_ends_at: String,
});

const billingInterval = ref('monthly');
const checkoutForm = ref(null);
const selectedPlanValue = ref('');
const selectedInterval = ref('');

const selectPlan = (plan) => {
    selectedPlanValue.value = plan.value;
    selectedInterval.value = billingInterval.value;

    // Use nextTick to ensure the form values are updated before submitting
    setTimeout(() => {
        checkoutForm.value.submit();
    }, 0);
};

const formatPrice = (plan) => {
    return billingInterval.value === 'annual'
        ? plan.annual_price_per_month
        : plan.monthly_price;
};

const formatLimit = (limit) => {
    return limit === null ? 'Unlimited' : limit;
};
</script>

<template>
    <Head title="Choose Your Plan" />

    <!-- Hidden form for Stripe Checkout (traditional form submission to handle external redirect) -->
    <form
        ref="checkoutForm"
        method="POST"
        action="/billing/checkout"
        class="hidden"
    >
        <input type="hidden" name="_token" :value="usePage().props.csrf_token" />
        <input type="hidden" name="plan" :value="selectedPlanValue" />
        <input type="hidden" name="interval" :value="selectedInterval" />
    </form>

    <AppLayout current-page="billing">
        <div class="max-w-5xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#0b1215]">Choose Your Plan</h1>
                <p class="text-[#0b1215]/60 mt-2">
                    <span v-if="on_trial">
                        Your trial ends {{ trial_ends_at }}. Choose a plan to continue using Copy Company.
                    </span>
                    <span v-else-if="current_plan">
                        You're currently on the {{ current_plan.charAt(0).toUpperCase() + current_plan.slice(1) }} plan.
                    </span>
                    <span v-else>
                        Start with a 14-day free trial. No credit card required.
                    </span>
                </p>
            </div>

            <!-- Billing Toggle -->
            <div class="flex justify-center items-center gap-4 mb-12">
                <span
                    :class="billingInterval === 'monthly' ? 'text-[#0b1215] font-medium' : 'text-[#0b1215]/50'"
                    class="text-sm cursor-pointer"
                    @click="billingInterval = 'monthly'"
                >
                    Monthly
                </span>
                <button
                    @click="billingInterval = billingInterval === 'monthly' ? 'annual' : 'monthly'"
                    class="relative w-14 h-7 rounded-full transition-colors"
                    :class="billingInterval === 'annual' ? 'bg-[#0b1215]' : 'bg-[#0b1215]/20'"
                >
                    <span
                        class="absolute top-0.5 left-0.5 w-6 h-6 bg-white rounded-full shadow transition-transform"
                        :class="billingInterval === 'annual' ? 'translate-x-7' : 'translate-x-0'"
                    ></span>
                </button>
                <span
                    :class="billingInterval === 'annual' ? 'text-[#0b1215] font-medium' : 'text-[#0b1215]/50'"
                    class="text-sm cursor-pointer"
                    @click="billingInterval = 'annual'"
                >
                    Annual
                    <span class="ml-2 text-xs bg-[#a1854f] text-white px-2 py-1 rounded-full font-medium">Save 25%</span>
                </span>
            </div>

            <!-- Plans Grid -->
            <div class="grid md:grid-cols-3 gap-6">
                <div
                    v-for="plan in plans"
                    :key="plan.value"
                    class="rounded-3xl p-8 relative"
                    :class="{
                        'bg-[#0b1215] text-white': plan.value === 'creator',
                        'bg-white border border-[#0b1215]/10': plan.value !== 'creator',
                        'ring-2 ring-[#a1854f]': plan.is_current,
                    }"
                >
                    <!-- Current Plan Badge -->
                    <div
                        v-if="plan.is_current"
                        class="absolute -top-3 left-1/2 -translate-x-1/2"
                    >
                        <span class="bg-[#a1854f] text-white text-xs font-medium px-3 py-1 rounded-full">
                            Current Plan
                        </span>
                    </div>

                    <!-- Most Popular Badge (for Creator) -->
                    <div
                        v-else-if="plan.value === 'creator' && !current_plan"
                        class="absolute -top-3 left-1/2 -translate-x-1/2"
                    >
                        <span class="bg-[#a1854f] text-white text-xs font-medium px-3 py-1 rounded-full">
                            Most Popular
                        </span>
                    </div>

                    <div class="text-center mb-6">
                        <h3
                            class="text-lg font-medium mb-4"
                            :class="plan.value === 'creator' ? 'text-white' : 'text-[#0b1215]'"
                        >
                            {{ plan.label }}
                        </h3>
                        <div class="mb-2">
                            <span
                                class="text-4xl font-light"
                                :class="plan.value === 'creator' ? 'text-white' : 'text-[#0b1215]'"
                            >
                                ${{ formatPrice(plan) }}
                            </span>
                            <span :class="plan.value === 'creator' ? 'text-white/50' : 'text-[#0b1215]/50'">/month</span>
                        </div>
                        <p
                            v-if="billingInterval === 'annual'"
                            class="text-xs"
                            :class="plan.value === 'creator' ? 'text-[#a1854f]' : 'text-[#a1854f]'"
                        >
                            Billed annually (${{ plan.annual_price_per_month * 12 }}/year)
                        </p>
                    </div>

                    <ul class="space-y-4 mb-8">
                        <li class="flex items-start gap-3">
                            <div
                                class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                :class="plan.value === 'creator' ? 'bg-[#a1854f]' : 'bg-[#0b1215]'"
                            >
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span :class="plan.value === 'creator' ? 'text-white/70' : 'text-[#0b1215]/70'">
                                <strong :class="plan.value === 'creator' ? 'text-white' : 'text-[#0b1215]'">{{ formatLimit(plan.post_limit) }}</strong>
                                posts/month
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div
                                class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                :class="plan.value === 'creator' ? 'bg-[#a1854f]' : 'bg-[#0b1215]'"
                            >
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span :class="plan.value === 'creator' ? 'text-white/70' : 'text-[#0b1215]/70'">
                                {{ plan.social_account_limit }} social accounts
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <div
                                class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                :class="plan.value === 'creator' ? 'bg-[#a1854f]' : 'bg-[#0b1215]'"
                            >
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span :class="plan.value === 'creator' ? 'text-white/70' : 'text-[#0b1215]/70'">
                                <strong :class="plan.value === 'creator' ? 'text-white' : 'text-[#0b1215]'">{{ formatLimit(plan.content_sprint_limit) }}</strong>
                                AI sprints/month
                            </span>
                        </li>
                        <li v-if="plan.features.custom_domain" class="flex items-start gap-3">
                            <div
                                class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                :class="plan.value === 'creator' ? 'bg-[#a1854f]' : 'bg-[#0b1215]'"
                            >
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span :class="plan.value === 'creator' ? 'text-white/70' : 'text-[#0b1215]/70'">
                                Custom domain
                            </span>
                        </li>
                        <li v-if="plan.features.analytics" class="flex items-start gap-3">
                            <div
                                class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                :class="plan.value === 'creator' ? 'bg-[#a1854f]' : 'bg-[#0b1215]'"
                            >
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span :class="plan.value === 'creator' ? 'text-white/70' : 'text-[#0b1215]/70'">
                                Analytics
                            </span>
                        </li>
                        <li v-if="plan.features.custom_email_domain" class="flex items-start gap-3">
                            <div
                                class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                :class="plan.value === 'creator' ? 'bg-[#a1854f]' : 'bg-[#0b1215]'"
                            >
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span :class="plan.value === 'creator' ? 'text-white/70' : 'text-[#0b1215]/70'">
                                Custom email domain
                            </span>
                        </li>
                        <li v-if="plan.features.remove_branding" class="flex items-start gap-3">
                            <div
                                class="w-5 h-5 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                :class="plan.value === 'creator' ? 'bg-[#a1854f]' : 'bg-[#0b1215]'"
                            >
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span :class="plan.value === 'creator' ? 'text-white/70' : 'text-[#0b1215]/70'">
                                Remove branding
                            </span>
                        </li>
                    </ul>

                    <button
                        @click="selectPlan(plan)"
                        :disabled="plan.is_current"
                        class="block w-full py-3 px-4 text-center font-medium rounded-full transition"
                        :class="{
                            'bg-white text-[#0b1215] hover:bg-white/90': plan.value === 'creator',
                            'border border-[#0b1215]/20 text-[#0b1215] hover:bg-[#0b1215]/5': plan.value !== 'creator' && !plan.is_current,
                            'bg-[#0b1215]/10 text-[#0b1215]/50 cursor-not-allowed': plan.is_current,
                        }"
                    >
                        <span v-if="plan.is_current">Current Plan</span>
                        <span v-else-if="plan.is_higher">Upgrade to {{ plan.label }}</span>
                        <span v-else-if="current_plan">Switch to {{ plan.label }}</span>
                        <span v-else>Start Free Trial</span>
                    </button>
                </div>
            </div>

            <!-- Email pricing note -->
            <div class="mt-12 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-[#f7f7f7] rounded-full">
                    <svg class="w-4 h-4 text-[#0b1215]/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm text-[#0b1215]/60">
                        Newsletter emails are billed at $0.40 per 1,000 emails sent
                    </span>
                </div>
            </div>

            <p class="text-center text-sm text-[#0b1215]/50 mt-6">
                All plans include a 14-day free trial. No credit card required to start.
            </p>

            <div class="mt-8 text-center">
                <Link
                    href="/settings/billing"
                    class="text-sm text-[#0b1215]/60 hover:text-[#0b1215]"
                >
                    &larr; Back to Billing Settings
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
