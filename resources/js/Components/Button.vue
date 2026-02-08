<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'danger', 'ghost', 'gold'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    loadingText: {
        type: String,
        default: 'Processing...',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'button',
    },
    href: {
        type: String,
        default: null,
    },
    method: {
        type: String,
        default: 'get',
    },
    as: {
        type: String,
        default: undefined,
    },
});

const variantClasses = {
    primary: 'bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 focus:ring-[#a1854f]/30',
    secondary: 'bg-white text-[#0b1215] font-medium border border-[#0b1215]/20 rounded-xl hover:bg-[#0b1215]/5 focus:ring-[#a1854f]/30',
    danger: 'bg-red-600 text-white font-medium rounded-full hover:bg-red-700 focus:ring-red-500',
    ghost: 'bg-transparent text-[#0b1215] font-medium rounded-xl hover:bg-[#0b1215]/5 focus:ring-[#a1854f]/30',
    gold: 'bg-[#a1854f]/10 text-[#a1854f] font-medium rounded-xl hover:bg-[#a1854f]/20 focus:ring-[#a1854f]/30',
};

const sizeClasses = {
    sm: 'px-3 py-1.5 text-sm',
    md: 'px-4 py-2.5 text-sm',
    lg: 'px-5 py-3.5 text-base',
};

const classes = computed(() => [
    'inline-flex items-center justify-center transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
    variantClasses[props.variant],
    sizeClasses[props.size],
]);

const isDisabled = computed(() => props.disabled || props.loading);
</script>

<template>
    <Link
        v-if="href"
        :href="href"
        :method="method"
        v-bind="as ? { as } : {}"
        :class="classes"
    >
        <slot />
    </Link>
    <button
        v-else
        :type="type"
        :disabled="isDisabled"
        :class="classes"
    >
        <template v-if="loading">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ loadingText }}
        </template>
        <slot v-else />
    </button>
</template>
