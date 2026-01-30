<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        required: true,
        // Expected format: [{ value: 'xxx', label: 'Display Text' }, ...]
    },
    placeholder: {
        type: String,
        default: 'Search...',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(0);
const inputRef = ref(null);
const dropdownRef = ref(null);
const containerRef = ref(null);

// Get the display label for the current value
const selectedLabel = computed(() => {
    const option = props.options.find(opt => opt.value === props.modelValue);
    return option ? option.label : '';
});

// Filter options based on search query
const filteredOptions = computed(() => {
    if (!searchQuery.value) {
        return props.options;
    }
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(option =>
        option.label.toLowerCase().includes(query) ||
        option.value.toLowerCase().includes(query)
    );
});

// Reset highlighted index when filtered options change
watch(filteredOptions, () => {
    highlightedIndex.value = 0;
});

// When opening, set search query to current selection
watch(isOpen, (newVal) => {
    if (newVal) {
        searchQuery.value = '';
        highlightedIndex.value = 0;
        // Focus the input after the dropdown opens
        setTimeout(() => {
            inputRef.value?.focus();
        }, 0);
    }
});

const openDropdown = () => {
    if (!props.disabled) {
        isOpen.value = true;
    }
};

const closeDropdown = () => {
    isOpen.value = false;
};

const selectOption = (option) => {
    emit('update:modelValue', option.value);
    closeDropdown();
};

const handleKeydown = (event) => {
    if (!isOpen.value) {
        if (event.key === 'Enter' || event.key === 'ArrowDown') {
            openDropdown();
            event.preventDefault();
        }
        return;
    }

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            highlightedIndex.value = Math.min(
                highlightedIndex.value + 1,
                filteredOptions.value.length - 1
            );
            scrollToHighlighted();
            break;
        case 'ArrowUp':
            event.preventDefault();
            highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
            scrollToHighlighted();
            break;
        case 'Enter':
            event.preventDefault();
            if (filteredOptions.value[highlightedIndex.value]) {
                selectOption(filteredOptions.value[highlightedIndex.value]);
            }
            break;
        case 'Escape':
            closeDropdown();
            break;
    }
};

const scrollToHighlighted = () => {
    setTimeout(() => {
        const highlighted = dropdownRef.value?.querySelector('.highlighted');
        if (highlighted) {
            highlighted.scrollIntoView({ block: 'nearest' });
        }
    }, 0);
};

// Handle click outside
const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div ref="containerRef" class="relative">
        <!-- Selected value display / trigger button -->
        <button
            type="button"
            @click="openDropdown"
            @keydown="handleKeydown"
            :disabled="disabled"
            class="w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-[#a1854f]/30 focus:border-[#a1854f] bg-white text-left flex items-center justify-between disabled:bg-gray-100 disabled:cursor-not-allowed"
        >
            <span :class="selectedLabel ? 'text-[#0b1215]' : 'text-[#0b1215]/50'">
                {{ selectedLabel || placeholder }}
            </span>
            <svg
                class="w-5 h-5 text-[#0b1215]/40 transition-transform"
                :class="{ 'rotate-180': isOpen }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown -->
        <div
            v-show="isOpen"
            class="absolute z-50 w-full mt-1 bg-white border border-[#0b1215]/20 rounded-xl shadow-lg overflow-hidden"
        >
            <!-- Search input -->
            <div class="p-2 border-b border-[#0b1215]/10">
                <input
                    ref="inputRef"
                    v-model="searchQuery"
                    type="text"
                    :placeholder="placeholder"
                    @keydown="handleKeydown"
                    class="w-full px-3 py-2 border border-[#0b1215]/20 rounded-lg text-sm focus:ring-[#a1854f]/30 focus:border-[#a1854f]"
                />
            </div>

            <!-- Options list -->
            <div ref="dropdownRef" class="max-h-60 overflow-y-auto">
                <div
                    v-for="(option, index) in filteredOptions"
                    :key="option.value"
                    @click="selectOption(option)"
                    @mouseenter="highlightedIndex = index"
                    class="px-4 py-2 cursor-pointer text-sm transition-colors"
                    :class="[
                        highlightedIndex === index ? 'bg-[#0b1215]/5 highlighted' : '',
                        modelValue === option.value ? 'text-[#a1854f] font-medium' : 'text-[#0b1215]'
                    ]"
                >
                    {{ option.label }}
                </div>

                <!-- No results -->
                <div v-if="filteredOptions.length === 0" class="px-4 py-3 text-sm text-[#0b1215]/50 text-center">
                    No results found
                </div>
            </div>
        </div>
    </div>
</template>
