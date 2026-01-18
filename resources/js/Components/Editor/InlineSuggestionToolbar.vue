<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    editor: {
        type: Object,
        required: true,
    },
    suggestionRange: {
        type: Object,
        required: true,
    },
    originalText: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['accept', 'reject']);

const toolbarRef = ref(null);
const position = ref({ top: 0, left: 0 });
const showOriginalTooltip = ref(false);

// Update toolbar position based on suggestion range
const updatePosition = () => {
    if (!props.editor || !toolbarRef.value || !props.suggestionRange) return;

    const { view } = props.editor;
    const endPos = props.suggestionRange.to;

    // Get coordinates from ProseMirror at the end of the suggestion
    const coords = view.coordsAtPos(endPos);

    // Get toolbar dimensions
    const toolbarRect = toolbarRef.value.getBoundingClientRect();

    // Position to the right of the suggestion end, slightly below
    let left = coords.right + 8;
    let top = coords.top;

    // Keep within viewport bounds
    const padding = 10;
    if (left + toolbarRect.width > window.innerWidth - padding) {
        // If there's not enough space on the right, position on the left
        left = coords.left - toolbarRect.width - 8;
    }

    // If still not enough space, center below
    if (left < padding) {
        left = coords.left;
        top = coords.bottom + 8;
    }

    // Ensure top is within viewport
    if (top < padding) {
        top = padding;
    }

    position.value = { top, left };
};

// Handle keyboard shortcuts
const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        emit('accept');
    } else if (e.key === 'Escape') {
        e.preventDefault();
        emit('reject');
    }
};

// Watch for range changes to update position
watch(() => props.suggestionRange, () => {
    setTimeout(updatePosition, 0);
}, { deep: true });

onMounted(() => {
    setTimeout(updatePosition, 0);
    window.addEventListener('resize', updatePosition);
    window.addEventListener('scroll', updatePosition, true);
    document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('resize', updatePosition);
    window.removeEventListener('scroll', updatePosition, true);
    document.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <Teleport to="body">
        <div
            ref="toolbarRef"
            class="fixed z-50"
            :style="{ top: position.top + 'px', left: position.left + 'px' }"
            @mousedown.prevent
        >
            <div class="flex flex-col gap-2">
                <!-- Toolbar Buttons -->
                <div class="flex items-center gap-1 px-2 py-1.5 bg-white rounded-lg shadow-lg border border-gray-200">
                    <!-- Accept Button -->
                    <button
                        @click="emit('accept')"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-md transition"
                        title="Accept suggestion (Enter)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Accept
                    </button>

                    <!-- Reject Button -->
                    <button
                        @click="emit('reject')"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition"
                        title="Reject suggestion (Esc)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reject
                    </button>

                    <!-- Show Original Button -->
                    <div class="relative">
                        <button
                            @mouseenter="showOriginalTooltip = true"
                            @mouseleave="showOriginalTooltip = false"
                            @focus="showOriginalTooltip = true"
                            @blur="showOriginalTooltip = false"
                            class="p-1.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition"
                            title="Show original text"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>

                        <!-- Original Text Tooltip -->
                        <div
                            v-if="showOriginalTooltip"
                            class="absolute bottom-full right-0 mb-2 px-4 py-3 bg-gray-900 text-white text-sm rounded-lg shadow-lg min-w-[300px] max-w-lg max-h-64 overflow-y-auto whitespace-pre-wrap"
                        >
                            <div class="text-xs text-gray-400 mb-2 font-medium">Original text:</div>
                            <div class="leading-relaxed">{{ originalText }}</div>
                            <!-- Tooltip Arrow -->
                            <div class="absolute top-full right-4 border-4 border-transparent border-t-gray-900"></div>
                        </div>
                    </div>
                </div>

                <!-- Keyboard Hints -->
                <div class="text-xs text-gray-500 text-center">
                    <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded">Enter</kbd> to accept,
                    <kbd class="px-1.5 py-0.5 bg-gray-100 border border-gray-300 rounded">Esc</kbd> to reject
                </div>
            </div>
        </div>
    </Teleport>
</template>
