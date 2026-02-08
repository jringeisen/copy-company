<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useToast } from '@/Composables/useToast';
import Button from '@/Components/Button.vue';

const page = usePage();
const toast = useToast();

const showModal = ref(false);
const screenshotPreview = ref(null);

const form = useForm({
    type: 'bug',
    priority: 'medium',
    description: '',
    page_url: '',
    user_agent: '',
    screenshot: null,
});

const types = [
    { value: 'bug', label: 'Bug Report', icon: '🐛' },
    { value: 'feature_request', label: 'Feature Request', icon: '💡' },
    { value: 'improvement', label: 'Improvement', icon: '⬆️' },
    { value: 'ui_ux', label: 'UI/UX Feedback', icon: '🎨' },
    { value: 'performance', label: 'Performance', icon: '⚡' },
    { value: 'other', label: 'Other', icon: '💬' },
];

const priorities = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
    { value: 'critical', label: 'Critical' },
];

const openModal = () => {
    form.page_url = window.location.href;
    form.user_agent = navigator.userAgent;
    showModal.value = true;
};

const closeModal = () => {
    if (!form.processing) {
        showModal.value = false;
        form.reset();
        form.clearErrors();
        screenshotPreview.value = null;
    }
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.screenshot = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            screenshotPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const handlePaste = (event) => {
    const items = event.clipboardData?.items;
    if (!items) return;

    for (let i = 0; i < items.length; i++) {
        if (items[i].type.indexOf('image') !== -1) {
            const file = items[i].getAsFile();
            form.screenshot = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                screenshotPreview.value = e.target.result;
            };
            reader.readAsDataURL(file);
            toast.success('Screenshot pasted!');
            break;
        }
    }
};

const removeScreenshot = () => {
    form.screenshot = null;
    screenshotPreview.value = null;
};

const submit = () => {
    form.post('/feedback', {
        forceFormData: true,
        onSuccess: () => {
            closeModal();
            toast.success('Feedback submitted successfully!');
        },
        onError: () => {
            toast.error('Failed to submit feedback. Please check the form and try again.');
        },
    });
};
</script>

<template>
    <div>
        <!-- Floating Button -->
        <button
            @click="openModal"
            class="fixed bottom-6 right-6 z-40 bg-[#0b1215] text-white p-4 rounded-full shadow-lg hover:bg-[#0b1215]/90 focus:outline-none focus:ring-2 focus:ring-[#a1854f] focus:ring-offset-2 transition-all hover:scale-105"
            title="Send Feedback"
        >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
            </svg>
        </button>

        <!-- Feedback Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <!-- Backdrop -->
                        <div
                            class="fixed inset-0 bg-black/50 backdrop-blur-sm"
                            @click="closeModal"
                        ></div>

                        <!-- Modal -->
                        <Transition
                            enter-active-class="duration-200 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="duration-150 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div
                                v-if="showModal"
                                class="relative bg-white rounded-2xl shadow-xl max-w-2xl w-full transform transition-all"
                                @paste="handlePaste"
                            >
                                <!-- Header -->
                                <div class="flex items-center justify-between p-6 border-b border-[#0b1215]/10">
                                    <h2 class="text-xl font-semibold text-[#0b1215]">Send Feedback</h2>
                                    <button
                                        @click="closeModal"
                                        class="text-[#0b1215]/60 hover:text-[#0b1215] rounded-lg p-1 hover:bg-[#0b1215]/5"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Form -->
                                <form @submit.prevent="submit" class="p-6 space-y-5">
                                    <!-- Type Selector -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#0b1215] mb-2">
                                            What type of feedback is this?
                                        </label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <button
                                                v-for="type in types"
                                                :key="type.value"
                                                type="button"
                                                @click="form.type = type.value"
                                                :class="[
                                                    'flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all',
                                                    form.type === type.value
                                                        ? 'border-[#a1854f] bg-[#a1854f]/5'
                                                        : 'border-[#0b1215]/10 hover:border-[#0b1215]/20 hover:bg-[#0b1215]/5'
                                                ]"
                                            >
                                                <span class="text-2xl mb-1">{{ type.icon }}</span>
                                                <span class="text-xs font-medium text-[#0b1215]">{{ type.label }}</span>
                                            </button>
                                        </div>
                                        <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                                    </div>

                                    <!-- Priority Selector -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#0b1215] mb-2">
                                            Priority
                                        </label>
                                        <div class="grid grid-cols-4 gap-2">
                                            <button
                                                v-for="priority in priorities"
                                                :key="priority.value"
                                                type="button"
                                                @click="form.priority = priority.value"
                                                :class="[
                                                    'py-2 px-4 rounded-xl border-2 text-sm font-medium transition-all',
                                                    form.priority === priority.value
                                                        ? 'border-[#a1854f] bg-[#a1854f]/5 text-[#0b1215]'
                                                        : 'border-[#0b1215]/10 text-[#0b1215]/60 hover:border-[#0b1215]/20 hover:bg-[#0b1215]/5'
                                                ]"
                                            >
                                                {{ priority.label }}
                                            </button>
                                        </div>
                                        <p v-if="form.errors.priority" class="mt-1 text-sm text-red-600">{{ form.errors.priority }}</p>
                                    </div>

                                    <!-- Description -->
                                    <div>
                                        <label for="feedback-description" class="block text-sm font-medium text-[#0b1215] mb-2">
                                            Description
                                        </label>
                                        <textarea
                                            id="feedback-description"
                                            v-model="form.description"
                                            rows="4"
                                            placeholder="Please describe your feedback in detail..."
                                            class="w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f] transition-colors resize-none"
                                        ></textarea>
                                        <div class="flex items-center justify-between mt-1">
                                            <p v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</p>
                                            <p class="text-xs text-[#0b1215]/50 ml-auto">{{ form.description.length }} / 2000</p>
                                        </div>
                                    </div>

                                    <!-- Screenshot Upload -->
                                    <div>
                                        <label class="block text-sm font-medium text-[#0b1215] mb-2">
                                            Screenshot <span class="text-[#0b1215]/40 font-normal">(optional)</span>
                                        </label>

                                        <div v-if="!screenshotPreview" class="border-2 border-dashed border-[#0b1215]/20 rounded-xl p-6 text-center hover:border-[#a1854f]/50 hover:bg-[#a1854f]/5 transition-colors">
                                            <input
                                                type="file"
                                                @change="handleFileChange"
                                                accept="image/*"
                                                class="hidden"
                                                id="screenshot-upload"
                                            />
                                            <label for="screenshot-upload" class="cursor-pointer">
                                                <svg class="mx-auto h-12 w-12 text-[#0b1215]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <p class="mt-2 text-sm text-[#0b1215]/60">
                                                    Click to upload or paste (Ctrl/Cmd+V) a screenshot
                                                </p>
                                                <p class="text-xs text-[#0b1215]/40 mt-1">PNG, JPG up to 5MB</p>
                                            </label>
                                        </div>

                                        <div v-else class="relative border border-[#0b1215]/20 rounded-xl p-3">
                                            <img :src="screenshotPreview" alt="Screenshot preview" class="w-full rounded-lg" />
                                            <button
                                                type="button"
                                                @click="removeScreenshot"
                                                class="absolute top-5 right-5 bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition-colors shadow-lg"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                        <p v-if="form.errors.screenshot" class="mt-1 text-sm text-red-600">{{ form.errors.screenshot }}</p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center gap-3 pt-4 border-t border-[#0b1215]/10">
                                        <Button
                                            variant="secondary"
                                            @click="closeModal"
                                            :disabled="form.processing"
                                            class="flex-1 rounded-full"
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            type="submit"
                                            :loading="form.processing"
                                            loading-text="Submitting..."
                                            :disabled="!form.description || form.description.length < 10"
                                            class="flex-1 font-semibold"
                                        >
                                            Submit Feedback
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>
