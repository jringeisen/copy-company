<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import UpgradeModal from '@/Components/UpgradeModal.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useSubscription } from '@/Composables/useSubscription';

const { canCreateSprints } = usePermissions();
const { canCreateSprint, getRequiredPlan } = useSubscription();

const props = defineProps({
    sprints: Array,
    brand: Object,
});

const showUpgradeModal = ref(false);

const handleNewSprint = () => {
    if (!canCreateSprint.value) {
        showUpgradeModal.value = true;
        return;
    }
    // Navigation happens via Link component
};

const statusColors = {
    pending: 'bg-[#0b1215]/10 text-[#0b1215]/70',
    generating: 'bg-[#a1854f]/20 text-[#a1854f]',
    completed: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
};
</script>

<template>
    <Head title="Content Sprints" />

    <AppLayout current-page="sprints">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-[#0b1215]">Content Sprints</h1>
                    <p class="text-[#0b1215]/60">Generate blog post ideas with AI</p>
                </div>
                <Link
                    v-if="canCreateSprints && canCreateSprint"
                    href="/content-sprints/create"
                    class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                >
                    New Sprint
                </Link>
                <button
                    v-else-if="canCreateSprints && !canCreateSprint"
                    @click="showUpgradeModal = true"
                    class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                >
                    New Sprint
                </button>
            </div>

            <!-- Sprints List -->
            <div v-if="sprints.length > 0" class="space-y-4">
                <Link
                    v-for="sprint in sprints"
                    :key="sprint.id"
                    :href="`/content-sprints/${sprint.id}`"
                    class="block bg-white rounded-2xl border border-[#0b1215]/10 p-6 hover:border-[#0b1215]/30 transition"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-[#0b1215]">{{ sprint.title }}</h3>
                            <div class="mt-1 flex items-center space-x-4 text-sm text-[#0b1215]/50">
                                <span>{{ sprint.created_at }}</span>
                                <span v-if="sprint.ideas_count">{{ sprint.ideas_count }} ideas</span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="topic in sprint.topics.slice(0, 5)"
                                    :key="topic"
                                    class="px-2 py-1 bg-[#0b1215]/5 text-[#0b1215]/60 text-xs rounded-lg"
                                >
                                    {{ topic }}
                                </span>
                            </div>
                        </div>
                        <span
                            :class="statusColors[sprint.status]"
                            class="px-3 py-1 text-sm font-medium rounded-full capitalize"
                        >
                            {{ sprint.status }}
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12 bg-white rounded-2xl border border-[#0b1215]/10">
                <svg class="mx-auto h-12 w-12 text-[#0b1215]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-[#0b1215]">No content sprints yet</h3>
                <p class="mt-2 text-sm text-[#0b1215]/50">
                    {{ canCreateSprints ? 'Start a sprint to generate a month of blog post ideas.' : 'No content sprints have been created yet.' }}
                </p>
                <Link
                    v-if="canCreateSprints && canCreateSprint"
                    href="/content-sprints/create"
                    class="mt-4 inline-block px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                >
                    Start Your First Sprint
                </Link>
                <button
                    v-else-if="canCreateSprints && !canCreateSprint"
                    @click="showUpgradeModal = true"
                    class="mt-4 inline-block px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                >
                    Start Your First Sprint
                </button>
            </div>
        </div>

        <UpgradeModal
            :show="showUpgradeModal"
            title="Content Sprint Limit Reached"
            message="You've used all your content sprints for this month. Upgrade to generate more AI content ideas."
            feature="content_sprints"
            :required-plan="getRequiredPlan('content_sprints')"
            @close="showUpgradeModal = false"
        />
    </AppLayout>
</template>
