<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppNavigation from '@/Components/AppNavigation.vue';

const props = defineProps({
    sprints: Array,
    brand: Object,
});

const statusColors = {
    pending: 'bg-gray-100 text-gray-700',
    generating: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
};
</script>

<template>
    <Head title="Content Sprints" />

    <div class="min-h-screen bg-gray-50">
        <AppNavigation current-page="sprints" />

        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Intro section -->
            <div class="mb-8 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-6 text-white">
                <h2 class="text-xl font-semibold mb-2">Generate a Month of Content</h2>
                <p class="text-indigo-100">
                    Content Sprints use AI to brainstorm blog post ideas based on your topics and goals.
                    Select the ideas you like, and they'll become draft posts ready for you to write.
                </p>
            </div>

            <!-- Sprints List -->
            <div v-if="sprints.length > 0" class="space-y-4">
                <Link
                    v-for="sprint in sprints"
                    :key="sprint.id"
                    :href="`/content-sprints/${sprint.id}`"
                    class="block bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium text-gray-900">{{ sprint.title }}</h3>
                            <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                <span>{{ sprint.created_at }}</span>
                                <span v-if="sprint.ideas_count">{{ sprint.ideas_count }} ideas</span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="topic in sprint.topics.slice(0, 5)"
                                    :key="topic"
                                    class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded"
                                >
                                    {{ topic }}
                                </span>
                            </div>
                        </div>
                        <span
                            :class="statusColors[sprint.status]"
                            class="px-3 py-1 text-sm font-medium rounded-full capitalize"
                        >
                            {{ sprint.status }}
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900">No content sprints yet</h3>
                <p class="mt-2 text-sm text-gray-500">
                    Start a sprint to generate a month of blog post ideas.
                </p>
                <Link
                    href="/content-sprints/create"
                    class="mt-4 inline-block px-4 py-2 bg-primary-600 text-white font-medium rounded-lg hover:bg-primary-700 transition"
                >
                    Start Your First Sprint
                </Link>
            </div>
        </main>
    </div>
</template>
