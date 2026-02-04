<script setup>
import { Head, Link, router, useForm, InfiniteScroll } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import UpgradeModal from '@/Components/UpgradeModal.vue';
import HelpLink from '@/Components/HelpLink.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { canManageSprints } = usePermissions();

const props = defineProps({
    sprint: Object,
    ideas: Object,
    brand: Object,
    postLimits: Object,
});

const selectedIdeas = ref([]);
const pollInterval = ref(null);
const showDeleteModal = ref(false);
const showUpgradeModal = ref(false);
const isDeleting = ref(false);

// Post limits
const hasPostLimit = computed(() => !props.postLimits?.unlimited);
const remainingPosts = computed(() => props.postLimits?.remaining ?? null);
const canCreateAnyPosts = computed(() => props.postLimits?.unlimited || (props.postLimits?.remaining > 0));
const isAtPostLimit = computed(() => hasPostLimit.value && remainingPosts.value === 0);
const selectionExceedsLimit = computed(() => {
    if (!hasPostLimit.value) return false;
    return selectedIdeas.value.length > remainingPosts.value;
});

const isGenerating = computed(() => ['pending', 'generating'].includes(props.sprint.status));
const isCompleted = computed(() => props.sprint.status === 'completed');
const isFailed = computed(() => props.sprint.status === 'failed');

const isConverted = (index) => {
    return (props.sprint.converted_indices || []).includes(index);
};

const unconvertedIdeas = computed(() => {
    return (props.ideas?.data || []).filter(idea => !isConverted(idea.original_index));
});

const allUnconvertedSelected = computed(() => {
    return unconvertedIdeas.value.length > 0 &&
        unconvertedIdeas.value.every(idea => selectedIdeas.value.includes(idea.original_index));
});

const acceptForm = useForm({
    idea_indices: [],
});

// Poll for updates if generating
onMounted(() => {
    if (isGenerating.value) {
        pollInterval.value = setInterval(() => {
            router.reload({ only: ['sprint', 'ideas'] });
        }, 3000);
    }
});

onUnmounted(() => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
    }
});

// Stop polling when completed
const stopPolling = () => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
        pollInterval.value = null;
    }
};

// Watch for status changes to stop polling
watch(() => props.sprint.status, (newStatus) => {
    if (newStatus === 'completed' || newStatus === 'failed') {
        stopPolling();
    }
});

const toggleIdea = (index) => {
    // Don't allow toggling converted ideas
    if (isConverted(index)) return;

    const idx = selectedIdeas.value.indexOf(index);
    if (idx > -1) {
        // Always allow deselecting
        selectedIdeas.value.splice(idx, 1);
    } else {
        // Check if selecting would exceed the limit
        if (hasPostLimit.value && selectedIdeas.value.length >= remainingPosts.value) {
            showUpgradeModal.value = true;
            return;
        }
        selectedIdeas.value.push(index);
    }
};

const selectAll = () => {
    // Only select/deselect unconverted ideas from currently loaded items
    if (allUnconvertedSelected.value) {
        selectedIdeas.value = [];
    } else {
        // Limit selection to remaining posts if there's a limit
        const ideasToSelect = unconvertedIdeas.value.map(idea => idea.original_index);
        if (hasPostLimit.value && ideasToSelect.length > remainingPosts.value) {
            selectedIdeas.value = ideasToSelect.slice(0, remainingPosts.value);
        } else {
            selectedIdeas.value = ideasToSelect;
        }
    }
};

const createPosts = () => {
    // Check if user has no posts remaining
    if (isAtPostLimit.value) {
        showUpgradeModal.value = true;
        return;
    }

    // Check if selection exceeds limit (shouldn't happen with UI guards, but backend will catch it too)
    if (selectionExceedsLimit.value) {
        showUpgradeModal.value = true;
        return;
    }

    acceptForm.idea_indices = selectedIdeas.value;
    acceptForm.post(`/content-sprints/${props.sprint.id}/accept`);
};

const retry = () => {
    router.post(`/content-sprints/${props.sprint.id}/retry`);
};

const deleteSprint = () => {
    isDeleting.value = true;
    router.delete(`/content-sprints/${props.sprint.id}`, {
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        },
    });
};
</script>

<template>
    <Head :title="sprint.title" />

    <AppLayout current-page="sprints">
        <!-- Page Header -->
        <div class="bg-white border-b border-[#0b1215]/10 -mt-8 -mx-4 sm:-mx-6 lg:-mx-8 mb-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link href="/content-sprints" class="text-[#0b1215]/50 hover:text-[#0b1215]/70">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <span class="text-[#0b1215]/30">|</span>
                        <h1 class="text-lg font-semibold text-[#0b1215]">{{ sprint.title }}</h1>
                        <HelpLink category-slug="content-sprints" article-slug="turning-ideas-into-draft-posts" />
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            v-if="canManageSprints && isCompleted && selectedIdeas.length > 0"
                            @click="createPosts"
                            :disabled="acceptForm.processing"
                            class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                        >
                            Create {{ selectedIdeas.length }} Posts
                        </button>
                        <button
                            v-if="canManageSprints"
                            @click="showDeleteModal = true"
                            class="p-2 text-[#0b1215]/40 hover:text-red-600 transition"
                            title="Delete sprint"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto">
            <!-- Generating State -->
            <div v-if="isGenerating" class="text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-[#a1854f]/20 rounded-full mb-6">
                    <svg class="animate-spin h-8 w-8 text-[#a1854f]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-[#0b1215] mb-2">Generating Your Content Ideas</h2>
                <p class="text-[#0b1215]/60 mb-4">
                    Our AI is brainstorming {{ sprint.inputs?.content_count || 20 }} blog post ideas for you...
                </p>
                <div class="flex flex-wrap justify-center gap-2">
                    <span
                        v-for="topic in sprint.inputs?.topics"
                        :key="topic"
                        class="px-3 py-1 bg-[#a1854f]/10 text-[#a1854f] text-sm rounded-full"
                    >
                        {{ topic }}
                    </span>
                </div>
                <p class="mt-6 text-sm text-[#0b1215]/40">
                    This usually takes 30-60 seconds...
                </p>
            </div>

            <!-- Failed State -->
            <div v-else-if="isFailed" class="text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-6">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-[#0b1215] mb-2">Generation Failed</h2>
                <p class="text-[#0b1215]/60 mb-6">
                    Something went wrong while generating your content ideas. Please try again.
                </p>
                <button
                    v-if="canManageSprints"
                    @click="retry"
                    class="px-6 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition"
                >
                    Try Again
                </button>
            </div>

            <!-- Completed State - Show Ideas -->
            <div v-else-if="isCompleted">
                <!-- Post Limit Warning -->
                <div v-if="hasPostLimit && remainingPosts <= 3 && remainingPosts > 0" class="mb-4 bg-[#a1854f]/10 border border-[#a1854f]/20 rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-[#a1854f] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm text-[#a1854f]">
                            You can create {{ remainingPosts }} more post{{ remainingPosts !== 1 ? 's' : '' }} this month.
                        </span>
                    </div>
                    <Link href="/billing/subscribe" class="text-sm text-[#a1854f] hover:text-[#0b1215] font-medium">
                        Upgrade
                    </Link>
                </div>

                <!-- At Limit Warning -->
                <div v-else-if="isAtPostLimit" class="mb-4 bg-red-50 border border-red-200 rounded-xl p-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span class="text-sm text-red-700">
                            You've reached your post limit for this month.
                        </span>
                    </div>
                    <Link href="/billing/subscribe" class="text-sm text-red-700 hover:text-red-900 font-medium">
                        Upgrade to Continue
                    </Link>
                </div>

                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-[#0b1215]">
                            {{ sprint.ideas_count }} Ideas Generated
                            <span v-if="sprint.converted_indices?.length > 0" class="text-[#0b1215]/50 font-normal">
                                ({{ sprint.unconverted_ideas_count }} remaining)
                            </span>
                        </h2>
                        <p class="text-sm text-[#0b1215]/60">
                            <template v-if="isAtPostLimit">
                                Upgrade your plan to create posts from these ideas
                            </template>
                            <template v-else-if="sprint.unconverted_ideas_count > 0">
                                Select the ideas you want to turn into draft posts
                                <span v-if="hasPostLimit" class="text-[#a1854f]">({{ remainingPosts }} remaining this month)</span>
                            </template>
                            <template v-else>
                                All ideas have been converted to posts
                            </template>
                        </p>
                    </div>
                    <button
                        v-if="canManageSprints && unconvertedIdeas.length > 0 && canCreateAnyPosts"
                        @click="selectAll"
                        class="text-sm text-[#a1854f] hover:text-[#a1854f]/80 font-medium"
                    >
                        <template v-if="allUnconvertedSelected">Deselect All</template>
                        <template v-else-if="hasPostLimit && unconvertedIdeas.length > remainingPosts">Select {{ remainingPosts }}</template>
                        <template v-else>Select All</template>
                    </button>
                </div>

                <!-- Ideas Grid with Infinite Scroll -->
                <InfiniteScroll data="ideas" only-next class="space-y-4">
                    <div
                        v-for="idea in ideas.data"
                        :key="idea.original_index"
                        @click="toggleIdea(idea.original_index)"
                        :class="[
                            'bg-white rounded-2xl border-2 p-5 transition relative',
                            isConverted(idea.original_index)
                                ? 'border-green-200 bg-green-50/50 cursor-default opacity-75'
                                : selectedIdeas.includes(idea.original_index)
                                    ? 'border-[#a1854f] bg-[#a1854f]/5 cursor-pointer'
                                    : 'border-[#0b1215]/10 hover:border-[#0b1215]/30 cursor-pointer'
                        ]"
                    >
                        <!-- Created Badge -->
                        <div
                            v-if="isConverted(idea.original_index)"
                            class="absolute top-3 right-3 px-2 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full flex items-center gap-1"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Created
                        </div>

                        <div class="flex items-start gap-4">
                            <!-- Checkbox -->
                            <div
                                v-if="!isConverted(idea.original_index)"
                                :class="[
                                    'w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-1',
                                    selectedIdeas.includes(idea.original_index)
                                        ? 'border-[#a1854f] bg-[#a1854f]'
                                        : 'border-[#0b1215]/30'
                                ]"
                            >
                                <svg
                                    v-if="selectedIdeas.includes(idea.original_index)"
                                    class="w-4 h-4 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <!-- Converted checkmark placeholder -->
                            <div v-else class="w-6 h-6 flex-shrink-0 mt-1"></div>

                            <!-- Content -->
                            <div class="flex-1">
                                <h3 :class="['font-semibold text-lg', isConverted(idea.original_index) ? 'text-[#0b1215]/50' : 'text-[#0b1215]']">
                                    {{ idea.title }}
                                </h3>
                                <p :class="['mt-1', isConverted(idea.original_index) ? 'text-[#0b1215]/40' : 'text-[#0b1215]/60']">
                                    {{ idea.description }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="point in idea.key_points"
                                        :key="point"
                                        :class="[
                                            'px-2 py-1 text-xs rounded-lg',
                                            isConverted(idea.original_index) ? 'bg-[#0b1215]/5 text-[#0b1215]/40' : 'bg-[#0b1215]/5 text-[#0b1215]/60'
                                        ]"
                                    >
                                        {{ point }}
                                    </span>
                                </div>
                                <div :class="['mt-3 text-sm', isConverted(idea.original_index) ? 'text-[#0b1215]/40' : 'text-[#0b1215]/50']">
                                    ~{{ idea.estimated_words }} words
                                </div>
                            </div>
                        </div>
                    </div>
                </InfiniteScroll>

                <!-- Fixed Bottom Bar -->
                <div v-if="canManageSprints && selectedIdeas.length > 0" class="fixed bottom-0 left-0 right-0 bg-white border-t border-[#0b1215]/10 p-4 shadow-lg">
                    <div class="max-w-4xl mx-auto flex items-center justify-between">
                        <span class="text-[#0b1215]/60">
                            {{ selectedIdeas.length }} idea{{ selectedIdeas.length !== 1 ? 's' : '' }} selected
                        </span>
                        <button
                            @click="createPosts"
                            :disabled="acceptForm.processing"
                            class="px-6 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                        >
                            {{ acceptForm.processing ? 'Creating...' : `Create ${selectedIdeas.length} Draft Posts` }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Content Sprint"
            message="Are you sure you want to delete this content sprint? This action cannot be undone."
            confirm-text="Delete"
            :processing="isDeleting"
            @confirm="deleteSprint"
            @cancel="showDeleteModal = false"
        />

        <UpgradeModal
            :show="showUpgradeModal"
            title="Post Limit Reached"
            message="You've used all your posts for this month. Upgrade to create unlimited posts from your content sprints."
            feature="posts"
            required-plan="creator"
            @close="showUpgradeModal = false"
        />
    </AppLayout>
</template>
