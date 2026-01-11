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
        default: 'from-[#0b1215] to-[#0b1215]/80',
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
    <div :class="`mb-8 bg-gradient-to-r ${gradient} rounded-2xl p-6 text-white`">
        <div class="flex items-start gap-4">
            <slot name="icon">
                <!-- Default icon slot - can be overridden -->
            </slot>
            <div class="flex-1">
                <h2 class="text-xl font-semibold mb-2">{{ title }}</h2>
                <p class="text-white/80">{{ description }}</p>
                <slot name="extra"></slot>
            </div>
            <slot name="cta">
                <Link
                    v-if="ctaText && ctaHref"
                    :href="ctaHref"
                    class="shrink-0 px-4 py-2 bg-[#a1854f] hover:bg-[#a1854f]/90 rounded-full font-medium transition"
                >
                    {{ ctaText }}
                </Link>
            </slot>
        </div>
    </div>
</template>
