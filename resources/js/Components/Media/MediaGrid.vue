<script setup>
defineProps({
    media: Array,
    selectedIds: Array,
    selectable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['toggle', 'select', 'edit-alt']);
</script>

<template>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div
            v-for="item in media"
            :key="item.id"
            :class="[
                'relative group bg-white rounded-xl overflow-hidden border-2 transition cursor-pointer',
                selectedIds.includes(item.id) ? 'border-[#a1854f] ring-2 ring-[#a1854f]/20' : 'border-[#0b1215]/10 hover:border-[#0b1215]/30'
            ]"
            @click="selectable ? emit('toggle', item.id) : emit('select', item)"
        >
            <!-- Image -->
            <div class="aspect-square bg-[#f7f7f7]">
                <img
                    :src="item.thumbnail_url || item.url"
                    :alt="item.alt_text || item.filename"
                    class="w-full h-full object-cover"
                    loading="lazy"
                />
            </div>

            <!-- Selection Indicator -->
            <div
                v-if="selectable"
                :class="[
                    'absolute top-2 left-2 w-6 h-6 rounded-full flex items-center justify-center transition',
                    selectedIds.includes(item.id)
                        ? 'bg-[#a1854f] text-white'
                        : 'bg-white/80 text-[#0b1215]/40 opacity-0 group-hover:opacity-100'
                ]"
            >
                <svg v-if="selectedIds.includes(item.id)" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                <span v-else class="text-xs">+</span>
            </div>

            <!-- Edit Button (only when selectable) -->
            <button
                v-if="selectable"
                @click.stop="emit('edit-alt', item)"
                class="absolute top-2 right-2 w-6 h-6 rounded-full bg-white/80 text-[#0b1215]/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition hover:bg-white hover:text-[#0b1215]"
                title="Edit alt text"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
            </button>

            <!-- Info Overlay -->
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-[#0b1215]/70 to-transparent p-2 opacity-0 group-hover:opacity-100 transition">
                <p class="text-white text-xs truncate">{{ item.filename }}</p>
                <p class="text-white/70 text-xs">{{ item.dimensions || item.human_size }}</p>
            </div>
        </div>
    </div>
</template>
