<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const show = ref(false);
const message = ref('');
const type = ref('success');

const flash = computed(() => page.props.flash);

watch(flash, (newFlash) => {
    if (newFlash?.success) {
        message.value = newFlash.success;
        type.value = 'success';
        show.value = true;
        setTimeout(() => show.value = false, 4000);
    } else if (newFlash?.error) {
        message.value = newFlash.error;
        type.value = 'error';
        show.value = true;
        setTimeout(() => show.value = false, 4000);
    } else if (newFlash?.warning) {
        message.value = newFlash.warning;
        type.value = 'warning';
        show.value = true;
        setTimeout(() => show.value = false, 4000);
    } else if (newFlash?.info) {
        message.value = newFlash.info;
        type.value = 'info';
        show.value = true;
        setTimeout(() => show.value = false, 4000);
    } else if (newFlash?.message) {
        message.value = newFlash.message;
        type.value = 'info';
        show.value = true;
        setTimeout(() => show.value = false, 4000);
    }
}, { immediate: true, deep: true });

const close = () => {
    show.value = false;
};

const typeClasses = computed(() => {
    const classes = {
        success: 'bg-green-50 text-green-800 border-green-200',
        error: 'bg-red-50 text-red-800 border-red-200',
        warning: 'bg-amber-50 text-amber-800 border-amber-200',
        info: 'bg-blue-50 text-blue-800 border-blue-200',
    };
    return classes[type.value] || classes.info;
});

const iconColor = computed(() => {
    const colors = {
        success: 'text-green-400',
        error: 'text-red-400',
        warning: 'text-amber-400',
        info: 'text-blue-400',
    };
    return colors[type.value] || colors.info;
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="transform opacity-0 translate-y-2"
            enter-to-class="transform opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="transform opacity-100 translate-y-0"
            leave-to-class="transform opacity-0 translate-y-2"
        >
            <div
                v-if="show"
                class="fixed top-4 right-4 z-50 max-w-sm w-full"
            >
                <div :class="typeClasses" class="rounded-lg border p-4 shadow-lg">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <!-- Success Icon -->
                            <svg v-if="type === 'success'" :class="iconColor" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <!-- Error Icon -->
                            <svg v-else-if="type === 'error'" :class="iconColor" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <!-- Warning Icon -->
                            <svg v-else-if="type === 'warning'" :class="iconColor" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <!-- Info Icon -->
                            <svg v-else :class="iconColor" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium">{{ message }}</p>
                        </div>
                        <div class="ml-4 flex-shrink-0">
                            <button
                                @click="close"
                                class="inline-flex rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                                :class="{
                                    'text-green-500 hover:text-green-600 focus:ring-green-500': type === 'success',
                                    'text-red-500 hover:text-red-600 focus:ring-red-500': type === 'error',
                                    'text-amber-500 hover:text-amber-600 focus:ring-amber-500': type === 'warning',
                                    'text-blue-500 hover:text-blue-600 focus:ring-blue-500': type === 'info',
                                }"
                            >
                                <span class="sr-only">Dismiss</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
