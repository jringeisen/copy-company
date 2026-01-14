<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import { ref, watch, onBeforeUnmount } from 'vue';
import MediaPickerModal from '@/Components/Media/MediaPickerModal.vue';
import AIBubbleMenu from '@/Components/Editor/AIBubbleMenu.vue';

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

const editor = useEditor({
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [1, 2, 3],
            },
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-primary-600 underline',
            },
        }),
        Image.configure({
            HTMLAttributes: {
                class: 'rounded-lg max-w-full',
            },
        }),
    ],
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
            <AIBubbleMenu v-if="editor" :editor="editor" />
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
</style>
