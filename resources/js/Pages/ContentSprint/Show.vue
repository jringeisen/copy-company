<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import AppNavigation from '@/Components/AppNavigation.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { canManageSprints } = usePermissions();

const props = defineProps({
    sprint: Object,
    brand: Object,
});

const selectedIdeas = ref([]);
const pollInterval = ref(null);
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const isGenerating = computed(() => ['pending', 'generating'].includes(props.sprint.status));
const isCompleted = computed(() => props.sprint.status === 'completed');
const isFailed = computed(() => props.sprint.status === 'failed');

const isConverted = (index) => {
    return (props.sprint.converted_indices || []).includes(index);
};

const unconvertedIndices = computed(() => {
    return (props.sprint.generated_content || [])
        .map((_, i) => i)
        .filter(i => !isConverted(i));
});

const allUnconvertedSelected = computed(() => {
    return unconvertedIndices.value.length > 0 &&
        unconvertedIndices.value.every(i => selectedIdeas.value.includes(i));
});

const acceptForm = useForm({
    idea_indices: [],
});

// Poll for updates if generating
onMounted(() => {
    if (isGenerating.value) {
        pollInterval.value = setInterval(() => {
            router.reload({ only: ['sprint'] });
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

// Watch for status changes
if (isCompleted.value || isFailed.value) {
    stopPolling();
}

const toggleIdea = (index) => {
    // Don't allow toggling converted ideas
    if (isConverted(index)) return;

    const idx = selectedIdeas.value.indexOf(index);
    if (idx > -1) {
        selectedIdeas.value.splice(idx, 1);
    } else {
        selectedIdeas.value.push(index);
    }
};

const selectAll = () => {
    // Only select/deselect unconverted ideas
    if (allUnconvertedSelected.value) {
        selectedIdeas.value = [];
    } else {
        selectedIdeas.value = [...unconvertedIndices.value];
    }
};

const createPosts = () => {
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

    <div class="min-h-screen bg-gray-50">
        <AppNavigation current-page="sprints" />

        <!-- Page Header -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link href="/content-sprints" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <span class="text-gray-400">|</span>
                        <h1 class="text-lg font-semibold text-gray-900">{{ sprint.title }}</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            v-if="canManageSprints && isCompleted && selectedIdeas.length > 0"
                            @click="createPosts"
                            :disabled="acceptForm.processing"
                            class="px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                        >
                            Create {{ selectedIdeas.length }} Posts
                        </button>
                        <button
                            v-if="canManageSprints"
                            @click="showDeleteModal = true"
                            class="p-2 text-gray-400 hover:text-red-600 transition"
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

        <main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Generating State -->
            <div v-if="isGenerating" class="text-center py-16">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-6">
                    <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Generating Your Content Ideas</h2>
                <p class="text-gray-500 mb-4">
                    Our AI is brainstorming {{ sprint.inputs?.content_count || 20 }} blog post ideas for you...
                </p>
                <div class="flex flex-wrap justify-center gap-2">
                    <span
                        v-for="topic in sprint.inputs?.topics"
                        :key="topic"
                        class="px-3 py-1 bg-indigo-100 text-indigo-700 text-sm rounded-full"
                    >
                        {{ topic }}
                    </span>
                </div>
                <p class="mt-6 text-sm text-gray-400">
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
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Generation Failed</h2>
                <p class="text-gray-500 mb-6">
                    Something went wrong while generating your content ideas. Please try again.
                </p>
                <button
                    v-if="canManageSprints"
                    @click="retry"
                    class="px-6 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                >
                    Try Again
                </button>
            </div>

            <!-- Completed State - Show Ideas -->
            <div v-else-if="isCompleted">
                <!-- Header -->
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ sprint.ideas_count }} Ideas Generated
                            <span v-if="sprint.converted_indices?.length > 0" class="text-gray-500 font-normal">
                                ({{ sprint.unconverted_ideas_count }} remaining)
                            </span>
                        </h2>
                        <p class="text-sm text-gray-500">
                            <template v-if="unconvertedIndices.length > 0">
                                Select the ideas you want to turn into draft posts
                            </template>
                            <template v-else>
                                All ideas have been converted to posts
                            </template>
                        </p>
                    </div>
                    <button
                        v-if="canManageSprints && unconvertedIndices.length > 0"
                        @click="selectAll"
                        class="text-sm text-primary-600 hover:text-primary-700 font-medium"
                    >
                        {{ allUnconvertedSelected ? 'Deselect All' : 'Select All' }}
                    </button>
                </div>

                <!-- Ideas Grid -->
                <div class="space-y-4">
                    <div
                        v-for="(idea, index) in sprint.generated_content"
                        :key="index"
                        @click="toggleIdea(index)"
                        :class="[
                            'bg-white rounded-lg border-2 p-5 transition relative',
                            isConverted(index)
                                ? 'border-green-200 bg-green-50/50 cursor-default opacity-75'
                                : selectedIdeas.includes(index)
                                    ? 'border-primary-500 bg-primary-50 cursor-pointer'
                                    : 'border-gray-200 hover:border-gray-300 cursor-pointer'
                        ]"
                    >
                        <!-- Created Badge -->
                        <div
                            v-if="isConverted(index)"
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
                                v-if="!isConverted(index)"
                                :class="[
                                    'w-6 h-6 rounded-full border-2 flex items-center justify-center flex-shrink-0 mt-1',
                                    selectedIdeas.includes(index)
                                        ? 'border-primary-500 bg-primary-500'
                                        : 'border-gray-300'
                                ]"
                            >
                                <svg
                                    v-if="selectedIdeas.includes(index)"
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
                                <h3 :class="['font-semibold text-lg', isConverted(index) ? 'text-gray-500' : 'text-gray-900']">
                                    {{ idea.title }}
                                </h3>
                                <p :class="['mt-1', isConverted(index) ? 'text-gray-400' : 'text-gray-600']">
                                    {{ idea.description }}
                                </p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="point in idea.key_points"
                                        :key="point"
                                        :class="[
                                            'px-2 py-1 text-xs rounded',
                                            isConverted(index) ? 'bg-gray-100 text-gray-400' : 'bg-gray-100 text-gray-600'
                                        ]"
                                    >
                                        {{ point }}
                                    </span>
                                </div>
                                <div :class="['mt-3 text-sm', isConverted(index) ? 'text-gray-400' : 'text-gray-500']">
                                    ~{{ idea.estimated_words }} words
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fixed Bottom Bar -->
                <div v-if="canManageSprints && selectedIdeas.length > 0" class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-lg">
                    <div class="max-w-4xl mx-auto flex items-center justify-between">
                        <span class="text-gray-600">
                            {{ selectedIdeas.length }} of {{ unconvertedIndices.length }} available ideas selected
                        </span>
                        <button
                            @click="createPosts"
                            :disabled="acceptForm.processing"
                            class="px-6 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                        >
                            {{ acceptForm.processing ? 'Creating...' : `Create ${selectedIdeas.length} Draft Posts` }}
                        </button>
                    </div>
                </div>
            </div>
        </main>

        <ConfirmModal
            :show="showDeleteModal"
            title="Delete Content Sprint"
            message="Are you sure you want to delete this content sprint? This action cannot be undone."
            confirm-text="Delete"
            :processing="isDeleting"
            @confirm="deleteSprint"
            @cancel="showDeleteModal = false"
        />
    </div>
</template>
