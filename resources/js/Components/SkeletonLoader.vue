<script setup>
defineProps({
    type: {
        type: String,
        default: 'text',
        validator: (value) => ['text', 'card', 'table-row', 'avatar', 'button'].includes(value),
    },
    lines: {
        type: Number,
        default: 1,
    },
    width: {
        type: String,
        default: 'full',
    },
});
</script>

<template>
    <div class="animate-pulse">
        <!-- Text skeleton -->
        <template v-if="type === 'text'">
            <div v-for="i in lines" :key="i" class="space-y-2">
                <div
                    :class="[
                        'h-4 bg-[#0b1215]/10 rounded',
                        width === 'full' ? 'w-full' : width === '3/4' ? 'w-3/4' : width === '1/2' ? 'w-1/2' : width === '1/4' ? 'w-1/4' : 'w-full',
                        i < lines ? 'mb-2' : ''
                    ]"
                />
            </div>
        </template>

        <!-- Card skeleton -->
        <template v-else-if="type === 'card'">
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="h-5 bg-[#0b1215]/10 rounded w-2/3 mb-2" />
                        <div class="h-4 bg-[#0b1215]/10 rounded w-1/2" />
                    </div>
                    <div class="h-6 w-20 bg-[#0b1215]/10 rounded-full" />
                </div>
                <div class="space-y-2">
                    <div class="h-4 bg-[#0b1215]/10 rounded w-full" />
                    <div class="h-4 bg-[#0b1215]/10 rounded w-3/4" />
                </div>
            </div>
        </template>

        <!-- Table row skeleton -->
        <template v-else-if="type === 'table-row'">
            <tr>
                <td class="px-6 py-4">
                    <div class="h-4 w-4 bg-[#0b1215]/10 rounded" />
                </td>
                <td class="px-6 py-4">
                    <div class="h-5 bg-[#0b1215]/10 rounded w-48 mb-1" />
                    <div class="h-4 bg-[#0b1215]/10 rounded w-32" />
                </td>
                <td class="px-6 py-4">
                    <div class="h-6 w-20 bg-[#0b1215]/10 rounded-full" />
                </td>
                <td class="px-6 py-4">
                    <div class="h-4 bg-[#0b1215]/10 rounded w-12" />
                </td>
                <td class="px-6 py-4">
                    <div class="h-4 bg-[#0b1215]/10 rounded w-24" />
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="h-4 bg-[#0b1215]/10 rounded w-12 ml-auto" />
                </td>
            </tr>
        </template>

        <!-- Avatar skeleton -->
        <template v-else-if="type === 'avatar'">
            <div class="h-10 w-10 bg-[#0b1215]/10 rounded-full" />
        </template>

        <!-- Button skeleton -->
        <template v-else-if="type === 'button'">
            <div class="h-10 w-24 bg-[#0b1215]/10 rounded-full" />
        </template>
    </div>
</template>
