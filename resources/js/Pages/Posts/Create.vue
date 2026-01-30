<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PostEditor from '@/Components/Editor/PostEditor.vue';
import AIAssistantPanel from '@/Components/Editor/AIAssistantPanel.vue';
import UpgradeModal from '@/Components/UpgradeModal.vue';
import ImpersonationBanner from '@/Components/ImpersonationBanner.vue';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { marked } from 'marked';
import { useSubscription } from '@/Composables/useSubscription';

const { canCreatePost, canSendNewsletter, getRequiredPlan } = useSubscription();

defineProps({
    brand: Object,
});

const showUpgradeModal = ref(false);

const STORAGE_KEY = 'new_post_draft';

// Try to restore draft from localStorage
const getStoredDraft = () => {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const draft = JSON.parse(stored);
            // Only use if less than 24 hours old
            if (Date.now() - draft.timestamp < 86400000) {
                return draft.data;
            }
            localStorage.removeItem(STORAGE_KEY);
        }
    } catch (e) {
        console.warn('Failed to read draft from localStorage:', e);
    }
    return null;
};

const storedDraft = getStoredDraft();

const form = useForm({
    title: storedDraft?.title || '',
    content: storedDraft?.content || { type: 'doc', content: [] },
    content_html: storedDraft?.content_html || '',
    excerpt: storedDraft?.excerpt || '',
    publish_to_blog: true,
    send_as_newsletter: canSendNewsletter.value,
    generate_social: true,
});

const hasRestoredDraft = ref(!!storedDraft);
const isSaving = ref(false);
const editorRef = ref(null);
const hasUnsavedChanges = ref(false);
const savedSelection = ref(null);

// Save cursor position when user interacts with AI panel
const saveEditorSelection = () => {
    if (editorRef.value?.editor) {
        savedSelection.value = {
            from: editorRef.value.editor.state.selection.from,
            to: editorRef.value.editor.state.selection.to,
        };
    }
};

// Save draft to localStorage
const saveDraftToLocalStorage = () => {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            data: {
                title: form.title,
                content: form.content,
                content_html: form.content_html,
                excerpt: form.excerpt,
            },
            timestamp: Date.now(),
        }));
    } catch (e) {
        console.warn('Failed to save draft to localStorage:', e);
    }
};

// Clear localStorage draft
const clearDraft = () => {
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch (e) {
        console.warn('Failed to clear draft from localStorage:', e);
    }
};

// Watch for changes and save to localStorage
watch(
    () => [form.title, form.content, form.content_html, form.excerpt],
    () => {
        hasUnsavedChanges.value = true;
        saveDraftToLocalStorage();
    },
    { deep: true }
);

// Dismiss restored draft notification
const dismissRestoredNotice = () => {
    hasRestoredDraft.value = false;
};

// Clear all draft content
const discardDraft = () => {
    form.title = '';
    form.content = { type: 'doc', content: [] };
    form.content_html = '';
    form.excerpt = '';
    clearDraft();
    hasRestoredDraft.value = false;
    hasUnsavedChanges.value = false;
};

// Warn before leaving with unsaved changes
const handleBeforeUnload = (e) => {
    if (hasUnsavedChanges.value && form.title) {
        e.preventDefault();
        e.returnValue = '';
    }
};

// Keyboard shortcut for saving (Cmd+S / Ctrl+S)
const handleKeydown = (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key === 's') {
        e.preventDefault();
        saveDraft();
    }
};

onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    document.removeEventListener('keydown', handleKeydown);
});

const updateContent = (json) => {
    form.content = json;
};

const updateHtml = (html) => {
    form.content_html = html;
};

const saveDraft = () => {
    // Check subscription limit first
    if (!canCreatePost.value) {
        showUpgradeModal.value = true;
        return;
    }

    isSaving.value = true;
    form.post('/posts', {
        preserveScroll: true,
        onSuccess: () => {
            clearDraft();
            hasUnsavedChanges.value = false;
        },
        onError: (errors) => {
            console.error('Form validation errors:', errors);
        },
        onFinish: () => {
            isSaving.value = false;
        },
    });
};

const handleApplySuggestion = (suggestion) => {
    // Convert markdown to HTML and set in editor
    const html = marked.parse(suggestion);
    if (editorRef.value?.editor) {
        editorRef.value.editor.commands.setContent(html);
        form.content = editorRef.value.editor.getJSON();
        form.content_html = editorRef.value.editor.getHTML();
    }
};

const handleInsertContent = (content) => {
    // Convert markdown to HTML and insert at saved cursor position
    const html = marked.parse(content);
    if (editorRef.value?.editor) {
        const editor = editorRef.value.editor;

        if (savedSelection.value) {
            // Restore cursor position and insert content there
            editor.chain()
                .focus()
                .setTextSelection(savedSelection.value.from)
                .insertContent(html)
                .run();
        } else {
            // Insert at end if no saved position
            editor.chain()
                .focus('end')
                .insertContent(html)
                .run();
        }

        form.content = editor.getJSON();
        form.content_html = editor.getHTML();
        savedSelection.value = null;
    }
};
</script>

<template>
    <Head title="New Post" />

    <ImpersonationBanner />

    <div class="min-h-screen bg-[#fcfbf8]">
        <!-- Navigation -->
        <nav class="bg-white border-b border-[#0b1215]/10 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <Link href="/posts" :preserve-state="false" class="text-[#0b1215]/50 hover:text-[#0b1215] transition-colors" aria-label="Back to posts">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <span class="text-[#0b1215]/20">|</span>
                        <span class="text-[#0b1215]/70">New Post</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <div v-if="isSaving || hasUnsavedChanges" class="flex items-center gap-2">
                            <!-- Saving spinner -->
                            <svg v-if="isSaving" class="animate-spin h-4 w-4 text-[#0b1215]/50" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <!-- Local save icon -->
                            <svg v-else-if="hasUnsavedChanges && form.title" class="h-4 w-4 text-[#a1854f]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                            <span v-if="isSaving" class="text-sm text-[#0b1215]/50">Saving...</span>
                            <span v-else-if="hasUnsavedChanges && form.title" class="text-sm text-[#a1854f]">Draft saved locally</span>
                        </div>
                        <button
                            @click="saveDraft"
                            :disabled="form.processing || !form.title"
                            class="px-4 py-2 border border-[#0b1215]/20 text-[#0b1215] font-medium rounded-full hover:bg-[#0b1215]/5 transition disabled:opacity-50 text-sm flex items-center gap-1.5"
                            title="Save Draft (⌘S)"
                        >
                            Save Draft
                            <kbd class="hidden sm:inline text-xs font-normal text-[#0b1215]/40 bg-[#0b1215]/5 px-1.5 py-0.5 rounded">⌘S</kbd>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <div class="flex h-[calc(100vh-4rem)]">
            <!-- Main Editor Area -->
            <main class="flex-1 overflow-y-auto py-8 px-4">
                <div class="max-w-4xl mx-auto">
                    <!-- Restored Draft Notice -->
                    <div v-if="hasRestoredDraft" class="mb-4 bg-[#a1854f]/10 border border-[#a1854f]/20 rounded-xl p-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-[#a1854f] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm text-[#a1854f]">Your previous draft has been restored.</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="discardDraft" class="text-sm text-[#a1854f] hover:text-[#0b1215] transition-colors">Discard</button>
                            <button @click="dismissRestoredNotice" class="text-sm text-[#0b1215]/50 hover:text-[#0b1215] transition-colors">Dismiss</button>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-8">
                        <!-- Title -->
                        <div class="mb-6">
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Post title..."
                                class="w-full text-4xl font-bold text-[#0b1215] border-0 border-b-2 border-transparent focus:border-[#a1854f] focus:ring-0 focus:outline-none pb-2 placeholder-[#0b1215]/30"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <!-- Excerpt -->
                        <div class="mb-6">
                            <textarea
                                v-model="form.excerpt"
                                placeholder="Write a brief excerpt or summary (optional)..."
                                rows="2"
                                class="w-full text-[#0b1215]/70 border-0 border-b-2 border-transparent focus:border-[#a1854f] focus:ring-0 focus:outline-none resize-none placeholder-[#0b1215]/30"
                                maxlength="500"
                            ></textarea>
                            <div class="flex justify-between items-center mt-1">
                                <p v-if="form.errors.excerpt" class="text-sm text-red-600">{{ form.errors.excerpt }}</p>
                                <span class="text-xs text-[#0b1215]/40">{{ form.excerpt?.length || 0 }}/500</span>
                            </div>
                        </div>

                        <!-- Editor -->
                        <div class="mb-6">
                            <PostEditor
                                ref="editorRef"
                                :model-value="form.content"
                                @update:model-value="updateContent"
                                @update:html="updateHtml"
                                placeholder="Start writing your post..."
                            />
                            <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
                        </div>

                        <!-- Distribution Options -->
                        <div class="border-t border-[#0b1215]/10 pt-6 mt-6">
                            <h3 class="text-sm font-medium text-[#0b1215] mb-4">Distribution Options</h3>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input
                                        v-model="form.publish_to_blog"
                                        type="checkbox"
                                        class="rounded border-[#0b1215]/20 text-[#a1854f] focus:ring-[#a1854f]/30"
                                    />
                                    <span class="ml-2 text-sm text-[#0b1215]/70">Publish to blog</span>
                                </label>
                                <div class="flex items-center justify-between">
                                    <label class="flex items-center">
                                        <input
                                            v-model="form.send_as_newsletter"
                                            type="checkbox"
                                            :disabled="!canSendNewsletter"
                                            :class="[
                                                'rounded border-[#0b1215]/20 focus:ring-[#a1854f]/30',
                                                canSendNewsletter ? 'text-[#a1854f]' : 'text-[#0b1215]/30 cursor-not-allowed'
                                            ]"
                                        />
                                        <span :class="['ml-2 text-sm', canSendNewsletter ? 'text-[#0b1215]/70' : 'text-[#0b1215]/40']">
                                            Send as newsletter
                                        </span>
                                    </label>
                                    <Link
                                        v-if="!canSendNewsletter"
                                        href="/billing/subscribe"
                                        class="text-xs text-[#a1854f] hover:text-[#a1854f]/80 font-medium"
                                    >
                                        Upgrade to unlock
                                    </Link>
                                </div>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.generate_social"
                                        type="checkbox"
                                        class="rounded border-[#0b1215]/20 text-[#a1854f] focus:ring-[#a1854f]/30"
                                    />
                                    <span class="ml-2 text-sm text-[#0b1215]/70">Generate social posts</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- AI Assistant Panel -->
            <div @mousedown="saveEditorSelection" class="flex">
                <AIAssistantPanel
                    :title="form.title"
                    @apply-suggestion="handleApplySuggestion"
                    @insert-content="handleInsertContent"
                />
            </div>
        </div>

        <UpgradeModal
            :show="showUpgradeModal"
            title="Post Limit Reached"
            message="You've used all your posts for this month. Upgrade to create unlimited posts."
            feature="posts"
            :required-plan="getRequiredPlan('posts')"
            @close="showUpgradeModal = false"
        />
    </div>
</template>
