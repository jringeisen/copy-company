<script setup>
import { ref, computed } from 'vue';
import { useAI } from '@/Composables/useAI';

const props = defineProps({
    content: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['apply-suggestion', 'insert-content']);

const {
    isLoading,
    error,
    generateDraft,
    polishWriting,
    continueWriting,
    suggestOutline,
    changeTone,
    makeItShorter,
    makeItLonger,
    askQuestion,
} = useAI();

const isOpen = ref(true);
const activeTab = ref('assist');
const suggestion = ref(null);
const freeformQuestion = ref('');
const draftBullets = ref('');
const outlineNotes = ref('');
const selectedTone = ref('professional');

const toneOptions = [
    { value: 'formal', label: 'Formal' },
    { value: 'casual', label: 'Casual' },
    { value: 'professional', label: 'Professional' },
    { value: 'friendly', label: 'Friendly' },
    { value: 'persuasive', label: 'Persuasive' },
];

const hasContent = computed(() => props.content && props.content.trim().length > 0);
const hasTitle = computed(() => props.title && props.title.trim().length > 0);

const clearSuggestion = () => {
    suggestion.value = null;
};

const applySuggestion = () => {
    if (suggestion.value) {
        emit('apply-suggestion', suggestion.value);
        suggestion.value = null;
    }
};

const insertSuggestion = () => {
    if (suggestion.value) {
        emit('insert-content', suggestion.value);
        suggestion.value = null;
    }
};

const handleGenerateDraft = async () => {
    if (!hasTitle.value) return;
    try {
        const result = await generateDraft(props.title, draftBullets.value || null);
        suggestion.value = result;
    } catch (e) {
        console.error('Failed to generate draft:', e);
    }
};

const handlePolish = async () => {
    if (!hasContent.value) return;
    try {
        const result = await polishWriting(props.content);
        suggestion.value = result;
    } catch (e) {
        console.error('Failed to polish:', e);
    }
};

const handleContinue = async () => {
    if (!hasContent.value) return;
    try {
        const result = await continueWriting(props.content);
        suggestion.value = result;
    } catch (e) {
        console.error('Failed to continue:', e);
    }
};

const handleOutline = async () => {
    if (!hasTitle.value) return;
    try {
        const result = await suggestOutline(props.title, outlineNotes.value || null);
        suggestion.value = result;
    } catch (e) {
        console.error('Failed to generate outline:', e);
    }
};

const handleChangeTone = async () => {
    if (!hasContent.value) return;
    try {
        const result = await changeTone(props.content, selectedTone.value);
        suggestion.value = result;
    } catch (e) {
        console.error('Failed to change tone:', e);
    }
};

const handleShorter = async () => {
    if (!hasContent.value) return;
    try {
        const result = await makeItShorter(props.content);
        suggestion.value = result;
    } catch (e) {
        console.error('Failed to shorten:', e);
    }
};

const handleLonger = async () => {
    if (!hasContent.value) return;
    try {
        const result = await makeItLonger(props.content);
        suggestion.value = result;
    } catch (e) {
        console.error('Failed to expand:', e);
    }
};

const handleAskQuestion = async () => {
    if (!freeformQuestion.value.trim()) return;
    try {
        const result = await askQuestion(props.content, freeformQuestion.value);
        suggestion.value = result;
        freeformQuestion.value = '';
    } catch (e) {
        console.error('Failed to process question:', e);
    }
};
</script>

<template>
    <div class="border-l border-gray-200 bg-gray-50 flex flex-col" :class="isOpen ? 'w-80' : 'w-12'">
        <!-- Toggle Button -->
        <button
            @click="isOpen = !isOpen"
            class="p-3 hover:bg-gray-100 border-b border-gray-200 flex items-center justify-center"
        >
            <svg v-if="isOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
        </button>

        <template v-if="isOpen">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z" />
                    </svg>
                    AI Assistant
                </h3>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-gray-200">
                <button
                    @click="activeTab = 'assist'"
                    class="flex-1 px-4 py-2 text-sm font-medium"
                    :class="activeTab === 'assist' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                >
                    Assist
                </button>
                <button
                    @click="activeTab = 'generate'"
                    class="flex-1 px-4 py-2 text-sm font-medium"
                    :class="activeTab === 'generate' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                >
                    Generate
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center justify-center py-8">
                    <svg class="animate-spin h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-2 text-sm text-gray-600">Thinking...</span>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <p class="text-sm text-red-600">{{ error }}</p>
                    <button @click="error = null" class="text-xs text-red-500 underline mt-1">Dismiss</button>
                </div>

                <!-- Suggestion Display -->
                <div v-else-if="suggestion" class="space-y-3">
                    <div class="bg-white border border-gray-200 rounded-lg p-3 max-h-64 overflow-y-auto">
                        <div class="prose prose-sm max-w-none" v-html="suggestion.replace(/\n/g, '<br>')"></div>
                    </div>
                    <div class="flex gap-2">
                        <button
                            @click="applySuggestion"
                            class="flex-1 px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
                        >
                            Replace
                        </button>
                        <button
                            @click="insertSuggestion"
                            class="flex-1 px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50"
                        >
                            Insert
                        </button>
                        <button
                            @click="clearSuggestion"
                            class="px-3 py-2 text-gray-500 text-sm hover:text-gray-700"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>

                <!-- Assist Tab -->
                <template v-else-if="activeTab === 'assist'">
                    <div class="space-y-3">
                        <p class="text-xs text-gray-500">Work with your existing content</p>

                        <button
                            @click="handleContinue"
                            :disabled="!hasContent || isLoading"
                            class="w-full flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">Continue writing</span>
                        </button>

                        <button
                            @click="handlePolish"
                            :disabled="!hasContent || isLoading"
                            class="w-full flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                            </svg>
                            <span class="text-sm">Polish writing</span>
                        </button>

                        <button
                            @click="handleShorter"
                            :disabled="!hasContent || isLoading"
                            class="w-full flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">Make it shorter</span>
                        </button>

                        <button
                            @click="handleLonger"
                            :disabled="!hasContent || isLoading"
                            class="w-full flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm">Make it longer</span>
                        </button>

                        <!-- Tone Change -->
                        <div class="pt-2 border-t border-gray-200">
                            <label class="block text-xs text-gray-500 mb-2">Change tone</label>
                            <div class="flex gap-2">
                                <select
                                    v-model="selectedTone"
                                    class="flex-1 text-sm border border-gray-200 rounded-lg px-2 py-1.5"
                                >
                                    <option v-for="tone in toneOptions" :key="tone.value" :value="tone.value">
                                        {{ tone.label }}
                                    </option>
                                </select>
                                <button
                                    @click="handleChangeTone"
                                    :disabled="!hasContent || isLoading"
                                    class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Apply
                                </button>
                            </div>
                        </div>

                        <!-- Freeform Question -->
                        <div class="pt-2 border-t border-gray-200">
                            <label class="block text-xs text-gray-500 mb-2">Ask anything</label>
                            <div class="space-y-2">
                                <textarea
                                    v-model="freeformQuestion"
                                    placeholder="How can I improve the introduction?"
                                    rows="2"
                                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none"
                                ></textarea>
                                <button
                                    @click="handleAskQuestion"
                                    :disabled="!freeformQuestion.trim() || isLoading"
                                    class="w-full px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    Ask AI
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Generate Tab -->
                <template v-else-if="activeTab === 'generate'">
                    <div class="space-y-4">
                        <!-- Generate Draft -->
                        <div class="space-y-2">
                            <label class="block text-xs text-gray-500">Generate draft from title</label>
                            <textarea
                                v-model="draftBullets"
                                placeholder="Key points to cover (optional)..."
                                rows="3"
                                class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none"
                            ></textarea>
                            <button
                                @click="handleGenerateDraft"
                                :disabled="!hasTitle || isLoading"
                                class="w-full px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Generate Draft
                            </button>
                            <p v-if="!hasTitle" class="text-xs text-amber-600">Enter a title first</p>
                        </div>

                        <!-- Generate Outline -->
                        <div class="pt-4 border-t border-gray-200 space-y-2">
                            <label class="block text-xs text-gray-500">Generate outline from title</label>
                            <textarea
                                v-model="outlineNotes"
                                placeholder="Additional notes or ideas (optional)..."
                                rows="3"
                                class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 resize-none"
                            ></textarea>
                            <button
                                @click="handleOutline"
                                :disabled="!hasTitle || isLoading"
                                class="w-full px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 disabled:opacity-50"
                            >
                                Generate Outline
                            </button>
                            <p v-if="!hasTitle" class="text-xs text-amber-600">Enter a title first</p>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- Collapsed State Icons -->
        <template v-else>
            <div class="flex flex-col items-center py-4 space-y-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z" />
                </svg>
            </div>
        </template>
    </div>
</template>
