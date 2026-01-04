<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    onboarding: {
        type: Object,
        required: true,
    },
});

const isMinimized = ref(false);

const steps = computed(() => [
    {
        key: 'brand_created',
        title: 'Create Your Brand',
        description: 'Set up your brand identity',
        href: '/settings/brand',
        completed: props.onboarding.steps.brand_created,
    },
    {
        key: 'voice_configured',
        title: 'Configure Brand Voice',
        description: 'Define your writing tone and style for AI assistance',
        href: '/settings/brand',
        completed: props.onboarding.steps.voice_configured,
    },
    {
        key: 'social_connected',
        title: 'Connect Social Platform',
        description: 'Link your social accounts to share content',
        href: '/settings/social',
        completed: props.onboarding.steps.social_connected,
        optional: true,
    },
    {
        key: 'first_post_created',
        title: 'Create First Post',
        description: 'Write your first blog post',
        href: '/posts/create',
        completed: props.onboarding.steps.first_post_created,
    },
    {
        key: 'calendar_viewed',
        title: 'View Content Calendar',
        description: 'Plan and visualize your content schedule',
        href: '/calendar',
        completed: props.onboarding.steps.calendar_viewed,
    },
]);

const dismissChecklist = () => {
    router.post('/onboarding/dismiss');
};
</script>

<template>
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Header -->
        <div
            class="p-4 bg-gradient-to-r from-primary-600 to-indigo-600 text-white cursor-pointer"
            @click="isMinimized = !isMinimized"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Getting Started</h2>
                    <p class="text-primary-100 text-sm mt-0.5">
                        {{ onboarding.completed }} of {{ onboarding.total }} steps complete
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Progress Ring -->
                    <div class="relative w-12 h-12">
                        <svg class="w-12 h-12 transform -rotate-90">
                            <circle
                                cx="24"
                                cy="24"
                                r="20"
                                stroke="currentColor"
                                stroke-width="4"
                                fill="none"
                                class="text-white/30"
                            />
                            <circle
                                cx="24"
                                cy="24"
                                r="20"
                                stroke="currentColor"
                                stroke-width="4"
                                fill="none"
                                class="text-white"
                                :stroke-dasharray="`${onboarding.percentage * 1.256} 125.6`"
                            />
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-sm font-bold">
                            {{ onboarding.percentage }}%
                        </span>
                    </div>
                    <!-- Expand/Collapse -->
                    <svg
                        class="w-5 h-5 transition-transform"
                        :class="{ 'rotate-180': isMinimized }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Steps List -->
        <div v-show="!isMinimized" class="divide-y divide-gray-100">
            <Link
                v-for="(step, index) in steps"
                :key="step.key"
                :href="step.href"
                class="flex items-center gap-4 p-4 hover:bg-gray-50 transition"
            >
                <!-- Completion indicator -->
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                    :class="step.completed
                        ? 'bg-green-100 text-green-600'
                        : 'bg-gray-100 text-gray-400'"
                >
                    <svg v-if="step.completed" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span v-else class="text-sm font-medium">{{ index + 1 }}</span>
                </div>

                <!-- Step info -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span
                            class="font-medium"
                            :class="step.completed ? 'text-gray-500 line-through' : 'text-gray-900'"
                        >
                            {{ step.title }}
                        </span>
                        <span
                            v-if="step.optional"
                            class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 rounded"
                        >
                            Optional
                        </span>
                    </div>
                    <p class="text-sm text-gray-500">{{ step.description }}</p>
                </div>

                <!-- Arrow -->
                <svg v-if="!step.completed" class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </Link>
        </div>

        <!-- Footer -->
        <div v-show="!isMinimized" class="p-4 bg-gray-50 border-t border-gray-100">
            <button
                @click.prevent="dismissChecklist"
                class="text-sm text-gray-500 hover:text-gray-700"
            >
                Dismiss checklist
            </button>
        </div>
    </div>
</template>
