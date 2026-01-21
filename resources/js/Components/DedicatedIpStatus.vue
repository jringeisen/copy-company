<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const dedicatedIp = computed(() => page.props.dedicatedIp);

const statusConfig = computed(() => {
    const status = dedicatedIp.value?.status;

    return {
        none: {
            label: 'No Dedicated IP',
            bgColor: 'bg-gray-100 dark:bg-gray-800',
            textColor: 'text-gray-600 dark:text-gray-400',
            icon: null,
        },
        provisioning: {
            label: 'Provisioning...',
            bgColor: 'bg-yellow-50 dark:bg-yellow-900/20',
            textColor: 'text-yellow-700 dark:text-yellow-400',
            icon: 'clock',
        },
        warming: {
            label: 'Warming Up',
            bgColor: 'bg-blue-50 dark:bg-blue-900/20',
            textColor: 'text-blue-700 dark:text-blue-400',
            icon: 'fire',
        },
        active: {
            label: 'Active',
            bgColor: 'bg-green-50 dark:bg-green-900/20',
            textColor: 'text-green-700 dark:text-green-400',
            icon: 'check',
        },
        suspended: {
            label: 'Suspended',
            bgColor: 'bg-red-50 dark:bg-red-900/20',
            textColor: 'text-red-700 dark:text-red-400',
            icon: 'pause',
        },
        released: {
            label: 'Released',
            bgColor: 'bg-gray-100 dark:bg-gray-800',
            textColor: 'text-gray-600 dark:text-gray-400',
            icon: null,
        },
    }[status] || {
        label: 'Unknown',
        bgColor: 'bg-gray-100 dark:bg-gray-800',
        textColor: 'text-gray-600 dark:text-gray-400',
        icon: null,
    };
});

const showComponent = computed(() => {
    return dedicatedIp.value && dedicatedIp.value.has_dedicated_ip;
});
</script>

<template>
    <div v-if="showComponent" class="rounded-lg p-4" :class="statusConfig.bgColor">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium" :class="statusConfig.textColor">
                Dedicated IP
            </span>
            <span
                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium"
                :class="statusConfig.textColor"
            >
                <svg v-if="statusConfig.icon === 'check'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else-if="statusConfig.icon === 'fire'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z" />
                </svg>
                <svg v-else-if="statusConfig.icon === 'clock'" class="w-3.5 h-3.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-else-if="statusConfig.icon === 'pause'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ statusConfig.label }}
            </span>
        </div>

        <!-- Warmup Progress Bar -->
        <div v-if="dedicatedIp.is_warming" class="mt-3">
            <div class="flex items-center justify-between text-xs mb-1">
                <span :class="statusConfig.textColor">
                    Day {{ dedicatedIp.warmup_day }} of 20
                </span>
                <span :class="statusConfig.textColor">
                    {{ dedicatedIp.warmup_progress }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                <div
                    class="bg-blue-500 h-2 rounded-full transition-all duration-500"
                    :style="{ width: `${dedicatedIp.warmup_progress}%` }"
                />
            </div>
            <p v-if="dedicatedIp.warmup_paused" class="text-xs mt-2 text-yellow-600 dark:text-yellow-400">
                Warmup paused - send a newsletter to resume
            </p>
        </div>

        <!-- Active IP Address -->
        <div v-if="dedicatedIp.status === 'active' && dedicatedIp.ip_address" class="mt-2">
            <span class="text-xs font-mono" :class="statusConfig.textColor">
                {{ dedicatedIp.ip_address }}
            </span>
        </div>

        <!-- Suspended Warning -->
        <p v-if="dedicatedIp.status === 'suspended'" class="text-xs mt-2" :class="statusConfig.textColor">
            Sending via shared pool. Contact support to resolve.
        </p>
    </div>
</template>
