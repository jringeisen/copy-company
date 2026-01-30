<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    media: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    alt_text: '',
});

watch(() => props.media, (val) => {
    if (val) {
        form.alt_text = val.alt_text || '';
    }
}, { immediate: true });

watch(() => props.show, (val) => {
    if (!val) {
        form.clearErrors();
    }
});

const submit = () => {
    if (!props.media) return;

    form.patch(`/media/${props.media.id}`, {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show && media" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')"></div>

                    <!-- Modal -->
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full">
                        <form @submit.prevent="submit">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-[#0b1215] mb-4">Edit Image Details</h3>

                                <!-- Preview -->
                                <div class="mb-4 bg-[#f7f7f7] rounded-xl overflow-hidden">
                                    <img
                                        :src="media.url"
                                        :alt="media.alt_text || media.filename"
                                        class="w-full h-48 object-contain"
                                    />
                                </div>

                                <!-- File Info -->
                                <div class="mb-4 text-sm text-[#0b1215]/60">
                                    <p><strong class="text-[#0b1215]">Filename:</strong> {{ media.filename }}</p>
                                    <p><strong class="text-[#0b1215]">Dimensions:</strong> {{ media.dimensions || 'Unknown' }}</p>
                                    <p><strong class="text-[#0b1215]">Size:</strong> {{ media.human_size }}</p>
                                </div>

                                <!-- Alt Text -->
                                <div>
                                    <label for="alt-text" class="block text-sm font-medium text-[#0b1215] mb-1">
                                        Alt Text
                                    </label>
                                    <textarea
                                        id="alt-text"
                                        v-model="form.alt_text"
                                        rows="2"
                                        placeholder="Describe this image for accessibility..."
                                        class="w-full px-3 py-2 border border-[#0b1215]/20 rounded-xl focus:ring-[#a1854f]/30 focus:border-[#a1854f]"
                                    ></textarea>
                                    <p class="mt-1 text-xs text-[#0b1215]/50">
                                        Alt text helps screen readers describe images to visually impaired users.
                                    </p>
                                    <p v-if="form.errors.alt_text" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.alt_text }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-3 p-4 border-t border-[#0b1215]/10 bg-[#f7f7f7] rounded-b-2xl">
                                <button
                                    type="button"
                                    @click="emit('close')"
                                    :disabled="form.processing"
                                    class="flex-1 px-4 py-2 text-[#0b1215] font-medium bg-white border border-[#0b1215]/20 rounded-xl hover:bg-[#0b1215]/5 transition disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1 px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50"
                                >
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
