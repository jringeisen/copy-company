<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Upgrade Required',
    },
    message: {
        type: String,
        default: 'This feature requires a higher plan.',
    },
    requiredPlan: {
        type: String,
        default: null,
    },
    feature: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};

const getFeatureDescription = (feature) => {
    const descriptions = {
        custom_domain: 'Use your own domain for your blog',
        custom_email_domain: 'Send newsletters from your own email domain',
        remove_branding: 'Remove Copy Company branding from your blog',
        analytics: 'Access detailed analytics and insights',
        posts: 'Create more posts per month',
        content_sprints: 'Generate more AI content ideas',
        social_accounts: 'Connect more social media accounts',
    };
    return descriptions[feature] || 'Access this premium feature';
};

const getPlanLabel = (plan) => {
    const labels = {
        starter: 'Starter',
        creator: 'Creator',
        pro: 'Pro',
    };
    return labels[plan] || plan;
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div
                        class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                        @click="close"
                    ></div>

                    <Transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="show"
                            class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6"
                        >
                            <!-- Close button -->
                            <button
                                @click="close"
                                class="absolute top-4 right-4 text-[#0b1215]/40 hover:text-[#0b1215] transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <!-- Icon -->
                            <div class="flex justify-center mb-4">
                                <div class="w-14 h-14 bg-[#a1854f]/10 rounded-full flex items-center justify-center">
                                    <svg class="w-7 h-7 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="text-center mb-6">
                                <h3 class="text-lg font-semibold text-[#0b1215] mb-2">
                                    {{ title }}
                                </h3>
                                <p class="text-[#0b1215]/60">
                                    {{ message }}
                                </p>
                                <p v-if="feature" class="text-sm text-[#0b1215]/50 mt-2">
                                    {{ getFeatureDescription(feature) }}
                                </p>
                            </div>

                            <!-- Required plan badge -->
                            <div v-if="requiredPlan" class="flex justify-center mb-6">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-[#0b1215] text-white">
                                    Requires {{ getPlanLabel(requiredPlan) }} plan or higher
                                </span>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col gap-3">
                                <Link
                                    href="/billing/subscribe"
                                    class="w-full py-3 px-4 text-center bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                                >
                                    View Upgrade Options
                                </Link>
                                <button
                                    @click="close"
                                    class="w-full py-3 px-4 text-center text-[#0b1215]/60 font-medium hover:text-[#0b1215] transition"
                                >
                                    Maybe Later
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
