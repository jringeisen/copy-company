<script setup>
defineProps({
    categorySlug: {
        type: String,
        required: true,
    },
    articleSlug: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <a
        :href="`/help/${categorySlug}/${articleSlug}`"
        target="_blank"
        rel="noopener noreferrer"
        title="Learn more"
        class="group w-6 h-6 inline-flex items-center justify-center rounded-full border border-[#0b1215]/10 hover:border-[#a1854f]/30 hover:bg-[#a1854f]/5 transition"
    >
        <span class="text-xs font-medium text-[#0b1215]/40 group-hover:text-[#a1854f]">?</span>
    </a>
</template>
