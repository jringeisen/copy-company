<script setup>
import { ref, computed } from 'vue';
import { useAI } from '@/Composables/useAI';
import ConfirmModal from '@/Components/ConfirmModal.vue';

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
const showReplaceConfirm = ref(false);

const clearSuggestion = () => {
    suggestion.value = null;
};

const confirmReplace = () => {
    showReplaceConfirm.value = true;
};

const applySuggestion = () => {
    if (suggestion.value) {
        emit('apply-suggestion', suggestion.value);
        suggestion.value = null;
        showReplaceConfirm.value = false;
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
    <div class="border-l border-[#0b1215]/10 bg-white flex flex-col" :class="isOpen ? 'w-80' : 'w-12'">
        <!-- Toggle Button -->
        <button
            @click="isOpen = !isOpen"
            class="p-3 hover:bg-[#0b1215]/5 border-b border-[#0b1215]/10 flex items-center justify-center transition-colors"
        >
            <svg v-if="isOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#0b1215]/50" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#0b1215]/50" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
        </button>

        <template v-if="isOpen">
            <!-- Header -->
            <div class="p-4 border-b border-[#0b1215]/10">
                <h3 class="font-semibold text-[#0b1215] flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#a1854f]" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z" />
                    </svg>
                    AI Assistant
                </h3>
            </div>

            <!-- Tabs -->
            <div class="flex border-b border-[#0b1215]/10">
                <button
                    @click="activeTab = 'assist'"
                    class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors"
                    :class="activeTab === 'assist' ? 'text-[#a1854f] border-b-2 border-[#a1854f]' : 'text-[#0b1215]/50 hover:text-[#0b1215]'"
                >
                    Assist
                </button>
                <button
                    @click="activeTab = 'generate'"
                    class="flex-1 px-4 py-2.5 text-sm font-medium transition-colors"
                    :class="activeTab === 'generate' ? 'text-[#a1854f] border-b-2 border-[#a1854f]' : 'text-[#0b1215]/50 hover:text-[#0b1215]'"
                >
                    Generate
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <!-- Loading State -->
                <div v-if="isLoading" class="flex items-center justify-center py-8">
                    <svg class="animate-spin h-6 w-6 text-[#a1854f]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="ml-2 text-sm text-[#0b1215]/60">Thinking...</span>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-xl p-3">
                    <p class="text-sm text-red-600">{{ error }}</p>
                    <button @click="error = null" class="text-xs text-red-500 underline mt-1">Dismiss</button>
                </div>

                <!-- Suggestion Display -->
                <div v-else-if="suggestion" class="space-y-3">
                    <div class="bg-[#fcfbf8] border border-[#0b1215]/10 rounded-xl p-3 max-h-64 overflow-y-auto">
                        <pre class="prose prose-sm max-w-none whitespace-pre-wrap font-sans text-[#0b1215]">{{ suggestion }}</pre>
                    </div>
                    <div class="space-y-2">
                        <button
                            @click="confirmReplace"
                            class="w-full px-3 py-2.5 bg-[#0b1215] text-white text-sm rounded-xl hover:bg-[#0b1215]/90 flex items-center justify-between transition-colors"
                        >
                            <span class="font-medium">Replace All</span>
                            <span class="text-xs opacity-75">Overwrites content</span>
                        </button>
                        <button
                            @click="insertSuggestion"
                            class="w-full px-3 py-2.5 bg-white border border-[#0b1215]/20 text-[#0b1215] text-sm rounded-xl hover:bg-[#0b1215]/5 flex items-center justify-between transition-colors"
                        >
                            <span class="font-medium">Insert at Cursor</span>
                            <span class="text-xs text-[#0b1215]/50">Adds at position</span>
                        </button>
                        <button
                            @click="clearSuggestion"
                            class="w-full px-3 py-2 text-[#0b1215]/50 text-sm hover:text-[#0b1215] hover:bg-[#0b1215]/5 rounded-xl transition-colors"
                        >
                            Dismiss
                        </button>
                    </div>
                </div>

                <!-- Assist Tab -->
                <template v-else-if="activeTab === 'assist'">
                    <div class="space-y-3">
                        <p class="text-xs text-[#0b1215]/50">Work with your existing content</p>

                        <button
                            @click="handleContinue"
                            :disabled="!hasContent || isLoading"
                            class="w-full flex items-center gap-2 px-3 py-2.5 bg-white border border-[#0b1215]/10 rounded-xl hover:bg-[#0b1215]/5 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#0b1215]/50" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-[#0b1215]">Continue writing</span>
                        </button>

                        <button
                            @click="handlePolish"
                            :disabled="!hasContent || isLoading"
                            class="w-full flex items-center gap-2 px-3 py-2.5 bg-white border border-[#0b1215]/10 rounded-xl hover:bg-[#0b1215]/5 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#0b1215]/50" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                            </svg>
                            <span class="text-sm text-[#0b1215]">Polish writing</span>
                        </button>

                        <button
                            @click="handleShorter"
                            :disabled="!hasContent || isLoading"
                            class="w-full flex items-center gap-2 px-3 py-2.5 bg-white border border-[#0b1215]/10 rounded-xl hover:bg-[#0b1215]/5 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#0b1215]/50" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-[#0b1215]">Make it shorter</span>
                        </button>

                        <button
                            @click="handleLonger"
                            :disabled="!hasContent || isLoading"
                            class="w-full flex items-center gap-2 px-3 py-2.5 bg-white border border-[#0b1215]/10 rounded-xl hover:bg-[#0b1215]/5 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#0b1215]/50" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-sm text-[#0b1215]">Make it longer</span>
                        </button>

                        <!-- Tone Change -->
                        <div class="pt-3 border-t border-[#0b1215]/10">
                            <label class="block text-xs text-[#0b1215]/50 mb-2">Change tone</label>
                            <div class="flex gap-2">
                                <select
                                    v-model="selectedTone"
                                    class="flex-1 text-sm border border-[#0b1215]/10 rounded-xl px-3 py-2 bg-white focus:ring-[#0b1215]/20 focus:border-[#0b1215]/30"
                                >
                                    <option v-for="tone in toneOptions" :key="tone.value" :value="tone.value">
                                        {{ tone.label }}
                                    </option>
                                </select>
                                <button
                                    @click="handleChangeTone"
                                    :disabled="!hasContent || isLoading"
                                    class="px-4 py-2 bg-[#0b1215] text-white text-sm rounded-xl hover:bg-[#0b1215]/90 disabled:opacity-50 transition-colors"
                                >
                                    Apply
                                </button>
                            </div>
                        </div>

                        <!-- Freeform Question -->
                        <div class="pt-3 border-t border-[#0b1215]/10">
                            <label class="block text-xs text-[#0b1215]/50 mb-2">Ask anything</label>
                            <div class="space-y-2">
                                <textarea
                                    v-model="freeformQuestion"
                                    placeholder="How can I improve the introduction?"
                                    rows="2"
                                    class="w-full text-sm border border-[#0b1215]/10 rounded-xl px-3 py-2 resize-none focus:ring-[#0b1215]/20 focus:border-[#0b1215]/30 placeholder-[#0b1215]/30"
                                ></textarea>
                                <button
                                    @click="handleAskQuestion"
                                    :disabled="!freeformQuestion.trim() || isLoading"
                                    class="w-full px-3 py-2.5 bg-[#0b1215] text-white text-sm font-medium rounded-xl hover:bg-[#0b1215]/90 disabled:opacity-50 transition-colors"
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
                            <label class="flex items-center gap-1.5 text-xs text-[#0b1215]/50">
                                Generate draft from title
                                <span class="group relative">
                                    <svg class="h-3.5 w-3.5 text-[#0b1215]/30 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="invisible group-hover:visible absolute left-0 top-5 z-10 w-48 p-2 bg-[#0b1215] text-white text-xs rounded-xl shadow-lg">
                                        Creates a complete, conversational blog post with paragraphs, examples, and insights.
                                    </span>
                                </span>
                            </label>
                            <textarea
                                v-model="draftBullets"
                                placeholder="Key points to cover (optional)..."
                                rows="3"
                                class="w-full text-sm border border-[#0b1215]/10 rounded-xl px-3 py-2 resize-none focus:ring-[#0b1215]/20 focus:border-[#0b1215]/30 placeholder-[#0b1215]/30"
                            ></textarea>
                            <button
                                @click="handleGenerateDraft"
                                :disabled="!hasTitle || isLoading"
                                class="w-full px-3 py-2.5 bg-[#0b1215] text-white text-sm font-medium rounded-xl hover:bg-[#0b1215]/90 disabled:opacity-50 transition-colors"
                            >
                                Generate Draft
                            </button>
                            <p v-if="!hasTitle" class="text-xs text-[#a1854f]">Enter a title first</p>
                        </div>

                        <!-- Generate Outline -->
                        <div class="pt-4 border-t border-[#0b1215]/10 space-y-2">
                            <label class="flex items-center gap-1.5 text-xs text-[#0b1215]/50">
                                Generate outline from title
                                <span class="group relative">
                                    <svg class="h-3.5 w-3.5 text-[#0b1215]/30 cursor-help" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="invisible group-hover:visible absolute left-0 top-5 z-10 w-48 p-2 bg-[#0b1215] text-white text-xs rounded-xl shadow-lg">
                                        Creates a structured framework with main sections, subsections, and brief descriptions to guide your writing.
                                    </span>
                                </span>
                            </label>
                            <textarea
                                v-model="outlineNotes"
                                placeholder="Additional notes or ideas (optional)..."
                                rows="3"
                                class="w-full text-sm border border-[#0b1215]/10 rounded-xl px-3 py-2 resize-none focus:ring-[#0b1215]/20 focus:border-[#0b1215]/30 placeholder-[#0b1215]/30"
                            ></textarea>
                            <button
                                @click="handleOutline"
                                :disabled="!hasTitle || isLoading"
                                class="w-full px-3 py-2.5 bg-white border border-[#0b1215]/20 text-[#0b1215] text-sm font-medium rounded-xl hover:bg-[#0b1215]/5 disabled:opacity-50 transition-colors"
                            >
                                Generate Outline
                            </button>
                            <p v-if="!hasTitle" class="text-xs text-[#a1854f]">Enter a title first</p>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- Collapsed State Icons -->
        <template v-else>
            <div class="flex flex-col items-center py-4 space-y-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#a1854f]" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z" />
                </svg>
            </div>
        </template>

        <ConfirmModal
            :show="showReplaceConfirm"
            title="Replace All Content?"
            message="This will replace all existing content in the editor with the AI suggestion. This cannot be undone."
            confirm-text="Replace All"
            variant="warning"
            @confirm="applySuggestion"
            @cancel="showReplaceConfirm = false"
        />
    </div>
</template>
