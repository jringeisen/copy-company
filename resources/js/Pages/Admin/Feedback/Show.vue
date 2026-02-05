<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    feedback: Object,
    statuses: Object,
});

const toast = useToast();

const form = useForm({
    status: props.feedback.status,
    admin_notes: props.feedback.admin_notes || '',
});

const submit = () => {
    form.put(`/admin/feedback/${props.feedback.id}`, {
        onSuccess: () => {
            toast.success('Feedback updated successfully');
        },
        onError: () => {
            toast.error('Failed to update feedback');
        },
    });
};

const getStatusBadgeClass = (color) => {
    const classes = {
        blue: 'bg-blue-100 text-blue-700',
        yellow: 'bg-yellow-100 text-yellow-700',
        green: 'bg-green-100 text-green-700',
        gray: 'bg-gray-100 text-gray-700',
    };
    return classes[color] || 'bg-gray-100 text-gray-700';
};

const getPriorityBadgeClass = (color) => {
    const classes = {
        gray: 'bg-gray-100 text-gray-700',
        blue: 'bg-blue-100 text-blue-700',
        yellow: 'bg-yellow-100 text-yellow-700',
        red: 'bg-red-100 text-red-700',
    };
    return classes[color] || 'bg-gray-100 text-gray-700';
};

const typeIcons = {
    bug: '🐛',
    lightbulb: '💡',
    'arrow-up': '⬆️',
    'paint-brush': '🎨',
    bolt: '⚡',
    chat: '💬',
};
</script>

<template>
    <Head :title="`Feedback #${feedback.id} - Admin`" />

    <AdminLayout current-page="feedback">
        <div class="max-w-4xl mx-auto">
            <!-- Back Button -->
            <Link
                href="/admin/feedback"
                class="inline-flex items-center gap-2 text-[#0b1215]/60 hover:text-[#0b1215] mb-6 text-sm font-medium"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Feedback
            </Link>

            <!-- Feedback Details -->
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-8 mb-6">
                <!-- Header -->
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <span class="text-4xl">{{ typeIcons[feedback.type_icon] || '💬' }}</span>
                        <div>
                            <h1 class="text-2xl font-bold text-[#0b1215]">{{ feedback.type_label }}</h1>
                            <p class="text-sm text-[#0b1215]/60 mt-1">
                                Submitted by {{ feedback.user_name }} &middot; {{ feedback.created_at }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            :class="getPriorityBadgeClass(feedback.priority_color)"
                            class="px-3 py-1.5 rounded-full text-sm font-medium"
                        >
                            {{ feedback.priority_label }}
                        </span>
                        <span
                            :class="getStatusBadgeClass(feedback.status_color)"
                            class="px-3 py-1.5 rounded-full text-sm font-medium"
                        >
                            {{ feedback.status_label }}
                        </span>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-[#0b1215]/60 mb-2">Description</h3>
                    <p class="text-[#0b1215] whitespace-pre-wrap">{{ feedback.description }}</p>
                </div>

                <!-- Screenshot -->
                <div v-if="feedback.screenshot_url" class="mb-6">
                    <h3 class="text-sm font-medium text-[#0b1215]/60 mb-2">Screenshot</h3>
                    <a :href="feedback.screenshot_url" target="_blank" class="inline-block">
                        <img
                            :src="feedback.screenshot_url"
                            alt="Screenshot"
                            class="max-w-full rounded-lg border border-[#0b1215]/10 hover:opacity-90 transition-opacity"
                        />
                    </a>
                </div>

                <!-- Metadata -->
                <div class="border-t border-[#0b1215]/10 pt-6">
                    <h3 class="text-sm font-medium text-[#0b1215]/60 mb-3">Additional Information</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-start gap-2">
                            <span class="font-medium text-[#0b1215] min-w-28">User:</span>
                            <span class="text-[#0b1215]/80">{{ feedback.user_name }} ({{ feedback.user_email }})</span>
                        </div>
                        <div v-if="feedback.brand_name" class="flex items-start gap-2">
                            <span class="font-medium text-[#0b1215] min-w-28">Brand:</span>
                            <span class="text-[#0b1215]/80">{{ feedback.brand_name }}</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="font-medium text-[#0b1215] min-w-28">Page URL:</span>
                            <a :href="feedback.page_url" target="_blank" class="text-blue-600 hover:underline break-all">
                                {{ feedback.page_url }}
                            </a>
                        </div>
                        <div class="flex items-start gap-2">
                            <span class="font-medium text-[#0b1215] min-w-28">Browser:</span>
                            <span class="text-[#0b1215]/80 text-xs break-all">{{ feedback.user_agent }}</span>
                        </div>
                        <div v-if="feedback.resolved_at" class="flex items-start gap-2">
                            <span class="font-medium text-[#0b1215] min-w-28">Resolved:</span>
                            <span class="text-[#0b1215]/80">{{ feedback.resolved_at }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Update Form -->
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-8">
                <h2 class="text-xl font-semibold text-[#0b1215] mb-6">Update Status & Response</h2>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-[#0b1215] mb-2">
                            Status
                        </label>
                        <select
                            id="status"
                            v-model="form.status"
                            class="w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f]"
                        >
                            <option v-for="(label, value) in statuses" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                        <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                    </div>

                    <!-- Admin Notes -->
                    <div>
                        <label for="admin_notes" class="block text-sm font-medium text-[#0b1215] mb-2">
                            Response / Notes
                        </label>
                        <textarea
                            id="admin_notes"
                            v-model="form.admin_notes"
                            rows="6"
                            placeholder="Add a response or internal notes..."
                            class="w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-2 focus:ring-[#a1854f]/30 focus:border-[#a1854f] resize-none"
                        ></textarea>
                        <p v-if="form.errors.admin_notes" class="mt-1 text-sm text-red-600">{{ form.errors.admin_notes }}</p>
                        <p class="mt-1 text-xs text-[#0b1215]/50">
                            The user will receive an email notification when the status changes.
                        </p>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-3 bg-[#0b1215] text-white font-semibold rounded-full hover:bg-[#0b1215]/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#a1854f] transition disabled:opacity-50"
                        >
                            <span v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Updating...
                            </span>
                            <span v-else>Update Feedback</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
