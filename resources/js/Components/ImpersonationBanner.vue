<script setup>
import { usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const impersonating = computed(() => page.props.impersonating);

const stopImpersonating = () => {
    router.post('/admin/impersonate/stop');
};
</script>

<template>
    <div
        v-if="impersonating"
        class="fixed top-0 left-0 right-0 z-50 bg-red-600 text-white text-center py-2.5 text-sm font-medium flex items-center justify-center gap-3"
    >
        <span>You are impersonating <strong>{{ impersonating.user_name }}</strong></span>
        <button
            @click="stopImpersonating"
            class="px-3 py-1 bg-white text-red-600 rounded-lg text-xs font-semibold hover:bg-red-50 transition-colors"
        >
            Stop Impersonating
        </button>
    </div>
</template>
