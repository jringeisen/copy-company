<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import Image from '@tiptap/extension-image';
import { ref, watch, onBeforeUnmount, computed } from 'vue';
import { marked } from 'marked';
import { Plugin, PluginKey } from 'prosemirror-state';
import { Decoration, DecorationSet } from 'prosemirror-view';
import MediaPickerModal from '@/Components/Media/MediaPickerModal.vue';
import AIBubbleMenu from '@/Components/Editor/AIBubbleMenu.vue';
import InlineSuggestionToolbar from '@/Components/Editor/InlineSuggestionToolbar.vue';

// Actions that produce structured content (markdown) vs inline text
const structuredActions = ['list', 'examples', 'longer'];

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ type: 'doc', content: [] }),
    },
    placeholder: {
        type: String,
        default: 'Start writing your post...',
    },
});

const emit = defineEmits(['update:modelValue', 'update:html']);

// Suggestion state for inline preview
const suggestionState = ref(null);
const hasSuggestion = computed(() => suggestionState.value !== null);

// Plugin key for suggestion highlight decorations
const suggestionHighlightKey = new PluginKey('suggestionHighlight');

// Create the suggestion highlight plugin
const createSuggestionHighlightPlugin = () => {
    return new Plugin({
        key: suggestionHighlightKey,
        state: {
            init: () => DecorationSet.empty,
            apply: (tr, set) => {
                // Check if there's a new highlight range in the meta
                const highlightMeta = tr.getMeta(suggestionHighlightKey);
                if (highlightMeta !== undefined) {
                    if (highlightMeta === null) {
                        // Clear decorations
                        return DecorationSet.empty;
                    }
                    // Create new decoration
                    const { from, to } = highlightMeta;
                    const decoration = Decoration.inline(from, to, {
                        class: 'ai-suggestion-highlight',
                    });
                    return DecorationSet.create(tr.doc, [decoration]);
                }
                // Map existing decorations through document changes
                return set.map(tr.mapping, tr.doc);
            },
        },
        props: {
            decorations(state) {
                return this.getState(state);
            },
        },
    });
};

const editor = useEditor({
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [1, 2, 3],
            },
            link: {
                openOnClick: false,
                HTMLAttributes: {
                    class: 'text-primary-600 underline',
                },
            },
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
        Image.configure({
            HTMLAttributes: {
                class: 'rounded-lg max-w-full',
            },
        }),
    ],
    // Add the suggestion highlight plugin after editor is created
    onCreate: ({ editor }) => {
        editor.view.updateState(
            editor.view.state.reconfigure({
                plugins: [...editor.view.state.plugins, createSuggestionHighlightPlugin()],
            })
        );
    },
    content: props.modelValue,
    editorProps: {
        attributes: {
            class: 'prose prose-lg max-w-none focus:outline-none min-h-[400px]',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getJSON());
        emit('update:html', editor.getHTML());
    },
});

// Watch for external changes
watch(() => props.modelValue, (newContent) => {
    if (editor.value && JSON.stringify(editor.value.getJSON()) !== JSON.stringify(newContent)) {
        editor.value.commands.setContent(newContent, false);
    }
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});

// Extract block structure from selection range
const getBlockStructure = (from, to) => {
    const doc = editor.value.state.doc;
    const blocks = [];
    const seenPositions = new Set();

    doc.nodesBetween(from, to, (node, pos) => {
        // Skip the doc node itself
        if (node.type.name === 'doc') {
            return true;
        }

        // Only process top-level block nodes (direct children of doc)
        // Check if this node's parent is the doc
        const $pos = doc.resolve(pos);
        const depth = $pos.depth;

        // We want nodes at depth 1 (direct children of doc)
        // But we also need to handle the case where selection starts mid-node
        if (node.isBlock && !seenPositions.has(pos)) {
            // Check if this is a content block (heading, paragraph, etc.)
            // not a wrapper block (bulletList, orderedList, blockquote wrapper)
            const isContentBlock = ['heading', 'paragraph', 'codeBlock'].includes(node.type.name);
            const isListItem = node.type.name === 'listItem';

            if (isContentBlock) {
                seenPositions.add(pos);
                blocks.push({
                    type: node.type.name,
                    attrs: { ...node.attrs },
                    textContent: node.textContent,
                });
                return false; // Don't descend into children
            } else if (isListItem) {
                // For list items, we'll treat them as paragraphs
                seenPositions.add(pos);
                blocks.push({
                    type: 'paragraph',
                    attrs: {},
                    textContent: node.textContent,
                });
                return false;
            }
        }

        return true;
    });

    return blocks;
};

// Convert block type to HTML tag
const blockToHtml = (blockType, attrs, content) => {
    // Escape HTML entities in content
    const escapedContent = content
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');

    switch (blockType) {
        case 'heading': {
            const level = attrs.level || 1;
            return `<h${level}>${escapedContent}</h${level}>`;
        }
        case 'paragraph':
            return `<p>${escapedContent}</p>`;
        case 'codeBlock':
            return `<pre><code>${escapedContent}</code></pre>`;
        default:
            return `<p>${escapedContent}</p>`;
    }
};

// Find sentence boundaries in text
const findSentenceBoundaries = (text) => {
    const boundaries = [];
    // Match sentence endings: . ! ? followed by space or end of string
    const regex = /[.!?]+[\s]+|[.!?]+$/g;
    let match;
    while ((match = regex.exec(text)) !== null) {
        boundaries.push(match.index + match[0].length);
    }
    return boundaries;
};

// Find the best split point near a target position
const findBestSplitPoint = (text, targetPos, boundaries) => {
    if (boundaries.length === 0) {
        return targetPos; // No sentence boundaries, just split at target
    }

    // Find the closest sentence boundary to the target position
    let bestBoundary = boundaries[0];
    let minDistance = Math.abs(targetPos - bestBoundary);

    for (const boundary of boundaries) {
        const distance = Math.abs(targetPos - boundary);
        if (distance < minDistance) {
            minDistance = distance;
            bestBoundary = boundary;
        }
    }

    // Only use the sentence boundary if it's reasonably close (within 30% of target)
    const tolerance = text.length * 0.15;
    if (minDistance <= tolerance) {
        return bestBoundary;
    }

    return targetPos;
};

// Reconstruct content preserving original block structure
const reconstructWithStructure = (aiContent, originalBlocks) => {
    // Normalize line endings
    const normalizedContent = aiContent.replace(/\r\n/g, '\n').trim();

    // If only one block, just wrap and return
    if (originalBlocks.length === 1) {
        return blockToHtml(originalBlocks[0].type, originalBlocks[0].attrs, normalizedContent);
    }

    // First, try to split by newlines (double or single)
    let parts = normalizedContent
        .split(/\n\n+/)
        .map(p => p.trim())
        .filter(p => p.length > 0);

    // If double newlines didn't give enough parts, try single newlines
    if (parts.length < originalBlocks.length) {
        const singleNewlineParts = normalizedContent
            .split(/\n/)
            .map(p => p.trim())
            .filter(p => p.length > 0);

        if (singleNewlineParts.length >= originalBlocks.length) {
            parts = singleNewlineParts;
        }
    }

    // If we have the right number of parts, use them directly
    if (parts.length >= originalBlocks.length) {
        let html = '';
        for (let i = 0; i < originalBlocks.length; i++) {
            const block = originalBlocks[i];
            // If we have more parts than blocks, join remaining parts into the last block
            const content = i === originalBlocks.length - 1
                ? parts.slice(i).join(' ')
                : parts[i];
            html += blockToHtml(block.type, block.attrs, content);
        }
        return html;
    }

    // If newline splitting didn't work (AI returned continuous text),
    // split proportionally based on original block lengths at sentence boundaries
    const totalOriginalLength = originalBlocks.reduce((sum, b) => sum + b.textContent.length, 0);
    const sentenceBoundaries = findSentenceBoundaries(normalizedContent);

    const splitParts = [];
    let currentPos = 0;

    for (let i = 0; i < originalBlocks.length; i++) {
        const block = originalBlocks[i];

        if (i === originalBlocks.length - 1) {
            // Last block gets the rest
            splitParts.push(normalizedContent.slice(currentPos).trim());
        } else {
            // Calculate proportional position
            const blockRatio = block.textContent.length / totalOriginalLength;
            const targetLength = Math.round(normalizedContent.length * blockRatio);
            const targetPos = currentPos + targetLength;

            // Find best split point (at sentence boundary if possible)
            const splitPos = findBestSplitPoint(normalizedContent, targetPos, sentenceBoundaries);

            splitParts.push(normalizedContent.slice(currentPos, splitPos).trim());
            currentPos = splitPos;
        }
    }

    // Build HTML
    let html = '';
    for (let i = 0; i < originalBlocks.length; i++) {
        const block = originalBlocks[i];
        const content = splitParts[i] || '';
        if (content) {
            html += blockToHtml(block.type, block.attrs, content);
        }
    }

    return html;
};

// Reconstruct content with markdown parsing while preserving original block structure
const reconstructWithMarkdown = (aiContent, originalBlocks) => {
    // Normalize line endings
    const normalizedContent = aiContent.replace(/\r\n/g, '\n').trim();

    // If only one block, parse inline markdown and wrap
    if (originalBlocks.length === 1) {
        const parsedContent = marked.parseInline(normalizedContent);
        return blockToHtmlRaw(originalBlocks[0].type, originalBlocks[0].attrs, parsedContent);
    }

    // First, try to split by newlines (double or single)
    let parts = normalizedContent
        .split(/\n\n+/)
        .map(p => p.trim())
        .filter(p => p.length > 0);

    // If double newlines didn't give enough parts, try single newlines
    if (parts.length < originalBlocks.length) {
        const singleNewlineParts = normalizedContent
            .split(/\n/)
            .map(p => p.trim())
            .filter(p => p.length > 0);

        if (singleNewlineParts.length >= originalBlocks.length) {
            parts = singleNewlineParts;
        }
    }

    // If we have the right number of parts, use them directly
    if (parts.length >= originalBlocks.length) {
        let html = '';
        for (let i = 0; i < originalBlocks.length; i++) {
            const block = originalBlocks[i];
            // If we have more parts than blocks, join remaining parts into the last block
            const content = i === originalBlocks.length - 1
                ? parts.slice(i).join(' ')
                : parts[i];
            // Parse inline markdown within the content
            const parsedContent = marked.parseInline(content);
            html += blockToHtmlRaw(block.type, block.attrs, parsedContent);
        }
        return html;
    }

    // Fallback: split proportionally and parse inline markdown
    const totalOriginalLength = originalBlocks.reduce((sum, b) => sum + b.textContent.length, 0);
    const sentenceBoundaries = findSentenceBoundaries(normalizedContent);

    const splitParts = [];
    let currentPos = 0;

    for (let i = 0; i < originalBlocks.length; i++) {
        const block = originalBlocks[i];

        if (i === originalBlocks.length - 1) {
            splitParts.push(normalizedContent.slice(currentPos).trim());
        } else {
            const blockRatio = block.textContent.length / totalOriginalLength;
            const targetLength = Math.round(normalizedContent.length * blockRatio);
            const targetPos = currentPos + targetLength;
            const splitPos = findBestSplitPoint(normalizedContent, targetPos, sentenceBoundaries);

            splitParts.push(normalizedContent.slice(currentPos, splitPos).trim());
            currentPos = splitPos;
        }
    }

    // Build HTML with parsed markdown
    let html = '';
    for (let i = 0; i < originalBlocks.length; i++) {
        const block = originalBlocks[i];
        const content = splitParts[i] || '';
        if (content) {
            const parsedContent = marked.parseInline(content);
            html += blockToHtmlRaw(block.type, block.attrs, parsedContent);
        }
    }

    return html;
};

// Convert block type to HTML tag (content is already HTML, no escaping)
const blockToHtmlRaw = (blockType, attrs, htmlContent) => {
    switch (blockType) {
        case 'heading': {
            const level = attrs.level || 1;
            return `<h${level}>${htmlContent}</h${level}>`;
        }
        case 'paragraph':
            return `<p>${htmlContent}</p>`;
        case 'codeBlock':
            return `<pre><code>${htmlContent}</code></pre>`;
        default:
            return `<p>${htmlContent}</p>`;
    }
};

// Handle AI suggestion from AIBubbleMenu
const handleSuggestion = ({ content, range, action, originalText }) => {
    if (!editor.value) return;

    // Determine if we need to parse as markdown (for structured content like lists)
    const isStructuredAction = structuredActions.includes(action);

    let insertContent;
    if (isStructuredAction) {
        // For structured actions, parse as markdown
        insertContent = marked.parse(content);
    } else {
        // Check for block-level markdown (headings, code blocks, blockquotes)
        // Headings: lines starting with # (with 1-6 hashes)
        const hasBlockMarkdown = /^#{1,6}\s/m.test(content) ||
            /^```/m.test(content) ||
            /^>/m.test(content);

        // Check for inline markdown formatting
        // Matches: **bold**, __bold__, _italic_, *italic*, ~~strike~~, `code`, [links](url)
        const hasInlineMarkdown = /(\*\*|__|_(?=[^\s])|(?<=[^\s])_|\*(?=[^\s])|(?<=[^\s])\*|~~|`|\[.*?\]\(.*?\))/.test(content);

        if (hasBlockMarkdown) {
            // Parse full markdown to HTML (handles headings, paragraphs, etc.)
            insertContent = marked.parse(content);
        } else if (hasInlineMarkdown) {
            // Parse only inline markdown, preserve original block structure
            const originalBlocks = getBlockStructure(range.from, range.to);

            if (originalBlocks.length > 0) {
                // Reconstruct with original structure, parsing inline markdown for each block
                insertContent = reconstructWithMarkdown(content, originalBlocks);
            } else {
                // Fallback to inline parsing
                insertContent = marked.parseInline(content);
            }
        } else {
            // No formatting, preserve block structure
            const originalBlocks = getBlockStructure(range.from, range.to);

            if (originalBlocks.length > 0) {
                // Reconstruct with original structure
                insertContent = reconstructWithStructure(content, originalBlocks);
            } else {
                // Fallback to plain text if no blocks found
                insertContent = content;
            }
        }
    }

    // Replace the selected content with the suggestion
    editor.value
        .chain()
        .focus()
        .deleteRange(range)
        .insertContentAt(range.from, insertContent)
        .run();

    // Get the new selection position after insertion
    const newEndPos = editor.value.state.selection.to;

    // Store suggestion state for accept/reject
    suggestionState.value = {
        originalText,
        originalRange: range,
        action,
        newRange: { from: range.from, to: newEndPos },
    };

    // Apply highlight decoration
    applyHighlight({ from: range.from, to: newEndPos });
};

// Apply highlight decoration to a range
const applyHighlight = (range) => {
    if (!editor.value) return;

    const tr = editor.value.view.state.tr;
    tr.setMeta(suggestionHighlightKey, range);
    editor.value.view.dispatch(tr);
};

// Remove highlight decoration
const removeHighlight = () => {
    if (!editor.value) return;

    const tr = editor.value.view.state.tr;
    tr.setMeta(suggestionHighlightKey, null);
    editor.value.view.dispatch(tr);
};

// Accept the suggestion
const acceptSuggestion = () => {
    removeHighlight();
    suggestionState.value = null;
    editor.value?.commands.focus();
};

// Reject the suggestion and restore original
const rejectSuggestion = () => {
    if (!editor.value || !suggestionState.value) return;

    // Undo the change to restore original content
    editor.value.commands.undo();

    removeHighlight();
    suggestionState.value = null;
    editor.value.commands.focus();
};

const setLink = () => {
    const url = window.prompt('Enter URL');
    if (url) {
        editor.value.chain().focus().setLink({ href: url }).run();
    }
};

const showMediaPicker = ref(false);

const addImage = () => {
    showMediaPicker.value = true;
};

const handleImageSelect = (media) => {
    // Use permanent URL instead of signed URL - it redirects to a fresh signed URL on access
    editor.value.chain().focus().setImage({
        src: media.permanent_url,
        alt: media.alt_text || ''
    }).run();
    showMediaPicker.value = false;
};

defineExpose({ editor });
</script>

<template>
    <div class="border border-gray-200 rounded-lg overflow-hidden bg-white">
        <!-- Toolbar -->
        <div v-if="editor" class="border-b border-gray-200 bg-gray-50 px-3 py-2 flex flex-wrap gap-1">
            <!-- Text formatting -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBold().run()"
                :class="{ 'bg-gray-200': editor.isActive('bold') }"
                class="p-2 rounded hover:bg-gray-200 transition"
                title="Bold"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 4h8a4 4 0 014 4 4 4 0 01-4 4H6z M6 12h9a4 4 0 014 4 4 4 0 01-4 4H6z" />
                </svg>
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleItalic().run()"
                :class="{ 'bg-gray-200': editor.isActive('italic') }"
                class="p-2 rounded hover:bg-gray-200 transition"
                title="Italic"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 4h4m-2 0v16m-4 0h8" transform="skewX(-10)" />
                </svg>
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleStrike().run()"
                :class="{ 'bg-gray-200': editor.isActive('strike') }"
                class="p-2 rounded hover:bg-gray-200 transition"
                title="Strikethrough"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3V6m0 12v-3M5 12h14" />
                </svg>
            </button>

            <div class="w-px h-6 bg-gray-300 mx-1 self-center"></div>

            <!-- Headings -->
            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                :class="{ 'bg-gray-200': editor.isActive('heading', { level: 1 }) }"
                class="p-2 rounded hover:bg-gray-200 transition text-sm font-bold"
                title="Heading 1"
            >
                H1
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                :class="{ 'bg-gray-200': editor.isActive('heading', { level: 2 }) }"
                class="p-2 rounded hover:bg-gray-200 transition text-sm font-bold"
                title="Heading 2"
            >
                H2
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                :class="{ 'bg-gray-200': editor.isActive('heading', { level: 3 }) }"
                class="p-2 rounded hover:bg-gray-200 transition text-sm font-bold"
                title="Heading 3"
            >
                H3
            </button>

            <div class="w-px h-6 bg-gray-300 mx-1 self-center"></div>

            <!-- Lists -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBulletList().run()"
                :class="{ 'bg-gray-200': editor.isActive('bulletList') }"
                class="p-2 rounded hover:bg-gray-200 transition"
                title="Bullet List"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleOrderedList().run()"
                :class="{ 'bg-gray-200': editor.isActive('orderedList') }"
                class="p-2 rounded hover:bg-gray-200 transition"
                title="Numbered List"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 6h13M7 12h13M7 18h13M3 6h.01M3 12h.01M3 18h.01" />
                </svg>
            </button>

            <div class="w-px h-6 bg-gray-300 mx-1 self-center"></div>

            <!-- Quote & Code -->
            <button
                type="button"
                @click="editor.chain().focus().toggleBlockquote().run()"
                :class="{ 'bg-gray-200': editor.isActive('blockquote') }"
                class="p-2 rounded hover:bg-gray-200 transition"
                title="Quote"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </button>

            <button
                type="button"
                @click="editor.chain().focus().toggleCodeBlock().run()"
                :class="{ 'bg-gray-200': editor.isActive('codeBlock') }"
                class="p-2 rounded hover:bg-gray-200 transition"
                title="Code Block"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
            </button>

            <div class="w-px h-6 bg-gray-300 mx-1 self-center"></div>

            <!-- Link & Image -->
            <button
                type="button"
                @click="setLink"
                :class="{ 'bg-gray-200': editor.isActive('link') }"
                class="p-2 rounded hover:bg-gray-200 transition"
                title="Add Link"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
            </button>

            <button
                type="button"
                @click="addImage"
                class="p-2 rounded hover:bg-gray-200 transition"
                title="Add Image"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </button>

            <div class="w-px h-6 bg-gray-300 mx-1 self-center"></div>

            <!-- Undo/Redo -->
            <button
                type="button"
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().undo()"
                class="p-2 rounded hover:bg-gray-200 transition disabled:opacity-50"
                title="Undo"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                </svg>
            </button>

            <button
                type="button"
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().redo()"
                class="p-2 rounded hover:bg-gray-200 transition disabled:opacity-50"
                title="Redo"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6" />
                </svg>
            </button>
        </div>

        <!-- Editor Content -->
        <div class="p-6">
            <EditorContent :editor="editor" />
            <!-- AI Bubble Menu -->
            <AIBubbleMenu
                v-if="editor && !hasSuggestion"
                :editor="editor"
                @suggestion="handleSuggestion"
            />
            <!-- Inline Suggestion Toolbar -->
            <InlineSuggestionToolbar
                v-if="editor && hasSuggestion"
                :editor="editor"
                :suggestion-range="suggestionState.newRange"
                :original-text="suggestionState.originalText"
                @accept="acceptSuggestion"
                @reject="rejectSuggestion"
            />
        </div>

        <!-- Media Picker Modal -->
        <MediaPickerModal
            :show="showMediaPicker"
            @close="showMediaPicker = false"
            @select="handleImageSelect"
        />
    </div>
</template>

<style>
.ProseMirror p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    color: #9ca3af;
    pointer-events: none;
    height: 0;
}

.ProseMirror:focus {
    outline: none;
}

/* AI Suggestion Highlight */
.ProseMirror .ai-suggestion-highlight {
    background: rgb(220, 252, 231); /* green-100 */
    border-bottom: 2px dashed rgb(34, 197, 94); /* green-500 */
    border-radius: 2px;
    padding: 1px 0;
}
</style>
