<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAI } from '@/Composables/useAI';
import { marked } from 'marked';

const props = defineProps({
    editor: {
        type: Object,
        required: true,
    },
});

const {
    isLoading,
    error,
    polishWriting,
    changeTone,
    makeItShorter,
    makeItLonger,
    fixGrammar,
    simplify,
    rephrase,
    toList,
    addExamples,
} = useAI();

// State
const menuRef = ref(null);
const isVisible = ref(false);
const preview = ref(null);
const selectionRange = ref(null);
const showToneMenu = ref(false);
const activeAction = ref(null);
const lastAction = ref(null);
const menuPosition = ref({ top: 0, left: 0 });

// Actions that produce structured content (markdown) vs inline text
const structuredActions = ['list', 'examples', 'longer'];

// Tone options
const toneOptions = [
    { value: 'formal', label: 'Formal' },
    { value: 'casual', label: 'Casual' },
    { value: 'professional', label: 'Professional' },
    { value: 'friendly', label: 'Friendly' },
    { value: 'persuasive', label: 'Persuasive' },
];

// Computed
const hasPreview = computed(() => preview.value !== null);

// Check if selection is valid
const isValidSelection = () => {
    if (!props.editor) return false;

    const { state } = props.editor;
    const { selection } = state;
    const { empty } = selection;

    // Don't show if selection is empty
    if (empty) return false;

    // Don't show if we're in a code block
    if (props.editor.isActive('codeBlock')) return false;

    // Get selected text and check if it's meaningful
    const selectedText = state.doc.textBetween(selection.from, selection.to, ' ');

    // Don't show for whitespace-only selections
    return selectedText.trim().length > 0;
};

// Get selected text
const getSelectedText = () => {
    const { state } = props.editor;
    const { selection } = state;
    return state.doc.textBetween(selection.from, selection.to, ' ');
};

// Save selection range
const saveSelection = () => {
    const { state } = props.editor;
    const { selection } = state;
    selectionRange.value = {
        from: selection.from,
        to: selection.to,
    };
};

// Update menu position
const updateMenuPosition = () => {
    if (!props.editor || !menuRef.value) return;

    const { view, state } = props.editor;
    const { from, to } = state.selection;

    // Get coordinates from ProseMirror
    const start = view.coordsAtPos(from);
    const end = view.coordsAtPos(to);

    // Calculate position relative to viewport
    const left = (start.left + end.left) / 2;
    const top = start.top;

    // Get editor element bounds
    const editorRect = view.dom.getBoundingClientRect();
    const menuRect = menuRef.value.getBoundingClientRect();

    // Position above selection, centered
    let menuLeft = left - menuRect.width / 2;
    let menuTop = top - menuRect.height - 10;

    // Keep within viewport bounds
    const padding = 10;
    if (menuLeft < padding) menuLeft = padding;
    if (menuLeft + menuRect.width > window.innerWidth - padding) {
        menuLeft = window.innerWidth - menuRect.width - padding;
    }

    // If there's not enough space above, show below
    if (menuTop < padding) {
        menuTop = end.bottom + 10;
    }

    menuPosition.value = {
        top: menuTop,
        left: menuLeft,
    };
};

// Handle selection change
const handleSelectionUpdate = () => {
    if (hasPreview.value || isLoading.value) return;

    if (isValidSelection()) {
        isVisible.value = true;
        // Use setTimeout to ensure the menu is rendered before positioning
        setTimeout(updateMenuPosition, 0);
    } else {
        isVisible.value = false;
        showToneMenu.value = false;
    }
};

// Handle AI action
const handleAction = async (action, actionFn, actionParams = null) => {
    if (isLoading.value || hasPreview.value) return;

    activeAction.value = action;
    lastAction.value = action;
    saveSelection();

    const selectedText = getSelectedText();

    try {
        let result;
        if (actionParams) {
            result = await actionFn(selectedText, actionParams);
        } else {
            result = await actionFn(selectedText);
        }
        preview.value = result;
        // Keep menu visible and update position
        setTimeout(updateMenuPosition, 0);
    } catch (e) {
        console.error('AI action failed:', e);
    }

    activeAction.value = null;
    showToneMenu.value = false;
};

// Handle tone change
const handleToneChange = async (tone) => {
    await handleAction('tone', changeTone, tone);
};

// Apply preview (accept)
const applyPreview = () => {
    if (!preview.value || !selectionRange.value) return;

    const { from, to } = selectionRange.value;

    // For structured actions (list, examples, longer), parse as markdown
    // For inline actions (polish, shorter, etc.), insert as plain text
    const isStructuredAction = structuredActions.includes(lastAction.value);
    const content = isStructuredAction ? marked.parse(preview.value) : preview.value;

    props.editor
        .chain()
        .focus()
        .deleteRange({ from, to })
        .insertContentAt(from, content)
        .run();

    clearPreview();
};

// Reject preview
const clearPreview = () => {
    preview.value = null;
    selectionRange.value = null;
    activeAction.value = null;
    lastAction.value = null;
    isVisible.value = false;
};

// Close tone menu when clicking outside
const handleClickOutside = (event) => {
    if (menuRef.value && !menuRef.value.contains(event.target)) {
        showToneMenu.value = false;
        if (!hasPreview.value && !isLoading.value) {
            // Only hide if not in preview or loading state
            if (!isValidSelection()) {
                isVisible.value = false;
            }
        }
    }
};

// Setup listeners
onMounted(() => {
    if (props.editor) {
        props.editor.on('selectionUpdate', handleSelectionUpdate);
        props.editor.on('blur', () => {
            // Delay hide to allow clicking menu buttons
            setTimeout(() => {
                if (!hasPreview.value && !isLoading.value) {
                    isVisible.value = false;
                    showToneMenu.value = false;
                }
            }, 200);
        });
    }
    document.addEventListener('click', handleClickOutside);
    window.addEventListener('resize', updateMenuPosition);
    window.addEventListener('scroll', updateMenuPosition, true);
});

onUnmounted(() => {
    if (props.editor) {
        props.editor.off('selectionUpdate', handleSelectionUpdate);
    }
    document.removeEventListener('click', handleClickOutside);
    window.removeEventListener('resize', updateMenuPosition);
    window.removeEventListener('scroll', updateMenuPosition, true);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-show="isVisible"
            ref="menuRef"
            class="fixed z-50"
            :style="{ top: menuPosition.top + 'px', left: menuPosition.left + 'px' }"
            @mousedown.prevent
        >
            <div
                class="flex items-center gap-1 px-2 py-1.5 bg-white rounded-lg shadow-lg border border-gray-200"
                @click.stop
            >
                <!-- Loading State -->
                <template v-if="isLoading">
                    <div class="flex items-center gap-2 px-3 py-1 text-sm text-gray-600">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Processing...</span>
                    </div>
                </template>

                <!-- Preview State -->
                <template v-else-if="hasPreview">
                    <div class="flex items-center gap-2">
                        <div class="max-w-md px-3 py-1.5 text-sm bg-green-50 border border-green-200 rounded text-green-800 max-h-32 overflow-y-auto">
                            {{ preview }}
                        </div>
                        <button
                            @click="applyPreview"
                            class="p-1.5 text-green-600 hover:bg-green-100 rounded transition"
                            title="Accept"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <button
                            @click="clearPreview"
                            class="p-1.5 text-red-600 hover:bg-red-100 rounded transition"
                            title="Reject"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </template>

                <!-- Normal State: Tool Buttons -->
                <template v-else>
                    <!-- Text Manipulation -->
                    <button
                        @click="handleAction('shorter', makeItShorter)"
                        class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition"
                        title="Make Shorter"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                        </svg>
                    </button>

                    <button
                        @click="handleAction('longer', makeItLonger)"
                        class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition"
                        title="Make Longer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>

                    <button
                        @click="handleAction('polish', polishWriting)"
                        class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition"
                        title="Polish Writing"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </button>

                    <button
                        @click="handleAction('grammar', fixGrammar)"
                        class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition"
                        title="Fix Grammar"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>

                    <button
                        @click="handleAction('simplify', simplify)"
                        class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition"
                        title="Simplify"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </button>

                    <div class="w-px h-5 bg-gray-300 mx-0.5"></div>

                    <!-- Tone Dropdown -->
                    <div class="relative">
                        <button
                            @click.stop="showToneMenu = !showToneMenu"
                            class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition flex items-center gap-1"
                            title="Change Tone"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Tone Menu -->
                        <div
                            v-if="showToneMenu"
                            class="absolute top-full left-0 mt-1 py-1 bg-white rounded-lg shadow-lg border border-gray-200 min-w-[120px] z-50"
                        >
                            <button
                                v-for="tone in toneOptions"
                                :key="tone.value"
                                @click="handleToneChange(tone.value)"
                                class="w-full px-3 py-1.5 text-left text-sm text-gray-700 hover:bg-gray-100 transition"
                            >
                                {{ tone.label }}
                            </button>
                        </div>
                    </div>

                    <div class="w-px h-5 bg-gray-300 mx-0.5"></div>

                    <!-- Transformations -->
                    <button
                        @click="handleAction('rephrase', rephrase)"
                        class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition"
                        title="Rephrase"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>

                    <button
                        @click="handleAction('list', toList)"
                        class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition"
                        title="Make it a List"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </button>

                    <button
                        @click="handleAction('examples', addExamples)"
                        class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition"
                        title="Add Examples"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </button>
                </template>

                <!-- Error Display -->
                <div v-if="error && !isLoading && !hasPreview" class="px-2 py-1 text-xs text-red-600 bg-red-50 rounded">
                    {{ error }}
                </div>
            </div>
        </div>
    </Teleport>
</template>
