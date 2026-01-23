<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    url: { type: String, default: '' },
    image: { type: String, default: '' },
    type: { type: String, default: 'website' },
    siteName: { type: String, default: 'Copy Company' },
    publishedAt: { type: String, default: '' },
    jsonLd: { type: [Object, Array], default: null },
});

const jsonLdScript = computed(() => {
    if (!props.jsonLd) {
        return null;
    }

    return JSON.stringify(
        Array.isArray(props.jsonLd) ? props.jsonLd : [props.jsonLd]
    );
});
</script>

<template>
    <Head :title="title">
        <meta v-if="description" head-key="description" name="description" :content="description" />

        <meta head-key="og:title" property="og:title" :content="title" />
        <meta v-if="description" head-key="og:description" property="og:description" :content="description" />
        <meta v-if="image" head-key="og:image" property="og:image" :content="image" />
        <meta v-if="url" head-key="og:url" property="og:url" :content="url" />
        <meta head-key="og:type" property="og:type" :content="type" />
        <meta head-key="og:site_name" property="og:site_name" :content="siteName" />

        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="title" />
        <meta v-if="description" head-key="twitter:description" name="twitter:description" :content="description" />
        <meta v-if="image" head-key="twitter:image" name="twitter:image" :content="image" />

        <meta v-if="publishedAt" head-key="article:published_time" property="article:published_time" :content="publishedAt" />

        <link v-if="url" head-key="canonical" rel="canonical" :href="url" />

        <component v-if="jsonLdScript" :is="'script'" head-key="json-ld" type="application/ld+json" v-text="jsonLdScript" />
    </Head>
</template>
