<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    brand: Object,
});

const form = useForm({
    topics: [''],
    goals: '',
    content_count: 20,
});

const addTopic = () => {
    if (form.topics.length < 10) {
        form.topics.push('');
    }
};

const removeTopic = (index) => {
    if (form.topics.length > 1) {
        form.topics.splice(index, 1);
    }
};

const submit = () => {
    // Filter out empty topics
    form.topics = form.topics.filter(t => t.trim() !== '');

    if (form.topics.length === 0) {
        form.topics = [''];
        return;
    }

    form.post('/content-sprints');
};
</script>

<template>
    <Head title="Start Content Sprint" />

    <AppLayout current-page="sprints">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-8">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-[#0b1215]">Let's brainstorm your content</h2>
                    <p class="mt-1 text-sm text-[#0b1215]/60">
                        Tell us about your topics and goals, and we'll generate blog post ideas for you.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Topics -->
                    <div>
                        <label class="block text-sm font-medium text-[#0b1215] mb-2">
                            Topics to cover
                        </label>
                        <div class="space-y-3">
                            <div
                                v-for="(topic, index) in form.topics"
                                :key="index"
                                class="flex items-center gap-2"
                            >
                                <input
                                    v-model="form.topics[index]"
                                    type="text"
                                    placeholder="e.g., productivity, remote work, leadership"
                                    class="flex-1 border border-[#0b1215]/20 rounded-xl px-3 py-2 focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                                />
                                <button
                                    v-if="form.topics.length > 1"
                                    type="button"
                                    @click="removeTopic(index)"
                                    class="p-2 text-[#0b1215]/40 hover:text-red-500"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <button
                            v-if="form.topics.length < 10"
                            type="button"
                            @click="addTopic"
                            class="mt-2 text-sm text-[#a1854f] hover:text-[#a1854f]/80 font-medium"
                        >
                            + Add another topic
                        </button>
                        <p v-if="form.errors.topics" class="mt-1 text-sm text-red-600">
                            {{ form.errors.topics }}
                        </p>
                    </div>

                    <!-- Goals -->
                    <div>
                        <label class="block text-sm font-medium text-[#0b1215] mb-2">
                            Content goals (optional)
                        </label>
                        <textarea
                            v-model="form.goals"
                            rows="3"
                            placeholder="e.g., Establish thought leadership in my industry, drive newsletter signups, educate readers about..."
                            class="w-full border border-[#0b1215]/20 rounded-xl px-3 py-2 focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                        ></textarea>
                        <p v-if="form.errors.goals" class="mt-1 text-sm text-red-600">
                            {{ form.errors.goals }}
                        </p>
                    </div>

                    <!-- Content Count -->
                    <div>
                        <label class="block text-sm font-medium text-[#0b1215] mb-2">
                            Number of ideas to generate
                        </label>
                        <div class="flex items-center gap-4">
                            <input
                                v-model="form.content_count"
                                type="range"
                                min="5"
                                max="30"
                                step="5"
                                class="flex-1 accent-[#a1854f]"
                            />
                            <span class="text-lg font-semibold text-[#0b1215] w-12 text-center">
                                {{ form.content_count }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-[#0b1215]/50">
                            More ideas = longer generation time
                        </p>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end gap-3 pt-4">
                        <Link
                            href="/content-sprints"
                            class="px-4 py-2 text-[#0b1215] font-medium hover:bg-[#0b1215]/5 rounded-xl transition"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition disabled:opacity-50 flex items-center"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Starting...' : 'Generate Ideas' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
