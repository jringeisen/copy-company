<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    parentId: [Number, String],
});

const emit = defineEmits(['close']);

const form = useForm({
    name: '',
    parent_id: null,
});

watch(() => props.parentId, (val) => {
    form.parent_id = val;
}, { immediate: true });

watch(() => props.show, (val) => {
    if (!val) {
        form.reset();
        form.clearErrors();
    }
});

const submit = () => {
    form.post('/media/folders', {
        onSuccess: () => {
            form.reset();
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
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')"></div>

                    <!-- Modal -->
                    <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full">
                        <form @submit.prevent="submit">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Create Folder</h3>

                                <div>
                                    <label for="folder-name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Folder Name
                                    </label>
                                    <input
                                        id="folder-name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="e.g., Blog Images"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                        autofocus
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                        {{ form.errors.name }}
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-3 p-4 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                                <button
                                    type="button"
                                    @click="emit('close')"
                                    :disabled="form.processing"
                                    class="flex-1 px-4 py-2 text-gray-700 font-medium bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing || !form.name"
                                    class="flex-1 px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition disabled:opacity-50"
                                >
                                    Create Folder
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
