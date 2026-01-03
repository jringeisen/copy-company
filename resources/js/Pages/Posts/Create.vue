<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PostEditor from '@/Components/Editor/PostEditor.vue';
import AIAssistantPanel from '@/Components/Editor/AIAssistantPanel.vue';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { marked } from 'marked';

defineProps({
    brand: Object,
});

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
    send_as_newsletter: true,
    generate_social: true,
});

const hasRestoredDraft = ref(!!storedDraft);
const isSaving = ref(false);
const editorRef = ref(null);
const hasUnsavedChanges = ref(false);

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

onMounted(() => {
    window.addEventListener('beforeunload', handleBeforeUnload);
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
});

const updateContent = (json) => {
    form.content = json;
};

const updateHtml = (html) => {
    form.content_html = html;
};

const saveDraft = () => {
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
    // Convert markdown to HTML and append to editor
    const html = marked.parse(content);
    if (editorRef.value?.editor) {
        editorRef.value.editor.commands.insertContent(html);
        form.content = editorRef.value.editor.getJSON();
        form.content_html = editorRef.value.editor.getHTML();
    }
};
</script>

<template>
    <Head title="New Post" />

    <div class="min-h-screen bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <Link href="/posts" :preserve-state="false" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <span class="text-gray-400">|</span>
                        <span class="text-gray-600">New Post</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span v-if="isSaving" class="text-sm text-gray-500">Saving...</span>
                        <span v-else-if="hasUnsavedChanges && form.title" class="text-sm text-amber-500">Draft saved locally</span>
                        <button
                            @click="saveDraft"
                            :disabled="form.processing || !form.title"
                            class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition disabled:opacity-50"
                        >
                            Save Draft
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
                    <div v-if="hasRestoredDraft" class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-sm text-blue-700">Your previous draft has been restored.</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="discardDraft" class="text-sm text-blue-600 hover:text-blue-800">Discard</button>
                            <button @click="dismissRestoredNotice" class="text-sm text-gray-500 hover:text-gray-700">Dismiss</button>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-8">
                        <!-- Title -->
                        <div class="mb-6">
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Post title..."
                                class="w-full text-4xl font-bold text-gray-900 border-0 border-b-2 border-transparent focus:border-primary-500 focus:ring-0 pb-2 placeholder-gray-300"
                            />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <!-- Excerpt -->
                        <div class="mb-6">
                            <textarea
                                v-model="form.excerpt"
                                placeholder="Write a brief excerpt or summary (optional)..."
                                rows="2"
                                class="w-full text-gray-600 border-0 focus:ring-0 resize-none placeholder-gray-400"
                                maxlength="500"
                            ></textarea>
                            <div class="flex justify-between items-center mt-1">
                                <p v-if="form.errors.excerpt" class="text-sm text-red-600">{{ form.errors.excerpt }}</p>
                                <span class="text-xs text-gray-400">{{ form.excerpt?.length || 0 }}/500</span>
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
                        <div class="border-t border-gray-200 pt-6 mt-6">
                            <h3 class="text-sm font-medium text-gray-900 mb-4">Distribution Options</h3>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input
                                        v-model="form.publish_to_blog"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Publish to blog</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.send_as_newsletter"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Send as newsletter</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.generate_social"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Generate social posts</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- AI Assistant Panel -->
            <AIAssistantPanel
                :content="form.content_html"
                :title="form.title"
                @apply-suggestion="handleApplySuggestion"
                @insert-content="handleInsertContent"
            />
        </div>
    </div>
</template>
