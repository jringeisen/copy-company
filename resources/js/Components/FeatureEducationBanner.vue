<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    gradient: {
        type: String,
        default: 'from-primary-500 to-indigo-600',
    },
    ctaText: {
        type: String,
        default: null,
    },
    ctaHref: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div :class="`mb-8 bg-gradient-to-r ${gradient} rounded-xl p-6 text-white`">
        <div class="flex items-start gap-4">
            <slot name="icon">
                <!-- Default icon slot - can be overridden -->
            </slot>
            <div class="flex-1">
                <h2 class="text-xl font-semibold mb-2">{{ title }}</h2>
                <p class="text-white/90">{{ description }}</p>
                <slot name="extra"></slot>
            </div>
            <slot name="cta">
                <Link
                    v-if="ctaText && ctaHref"
                    :href="ctaHref"
                    class="shrink-0 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg font-medium transition"
                >
                    {{ ctaText }}
                </Link>
            </slot>
        </div>
    </div>
</template>
