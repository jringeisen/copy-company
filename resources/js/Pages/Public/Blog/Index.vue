<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    brand: Object,
    posts: Array,
});

const subscribeForm = useForm({
    email: '',
});

const subscribeSuccess = ref(false);
const subscribeError = ref('');

const subscribe = () => {
    subscribeError.value = '';
    subscribeForm.post(`/@${props.brand.slug}/subscribe`, {
        preserveScroll: true,
        onSuccess: () => {
            subscribeSuccess.value = true;
            subscribeForm.reset();
        },
        onError: (errors) => {
            subscribeError.value = errors.email || 'Something went wrong. Please try again.';
        },
    });
};
</script>

<template>
    <Head>
        <title>{{ brand.name }}</title>
        <meta name="description" :content="brand.description || brand.tagline" />
    </Head>

    <div class="min-h-screen bg-white">
        <!-- Header -->
        <header class="border-b border-gray-100">
            <div class="max-w-3xl mx-auto px-6 py-12">
                <div class="text-center">
                    <img
                        v-if="brand.logo_path"
                        :src="brand.logo_path"
                        :alt="brand.name"
                        class="h-16 mx-auto mb-4"
                    />
                    <h1 class="text-3xl font-bold text-gray-900">{{ brand.name }}</h1>
                    <p v-if="brand.tagline" class="mt-2 text-lg text-gray-600">{{ brand.tagline }}</p>
                    <p v-if="brand.description" class="mt-4 text-gray-500 max-w-xl mx-auto">{{ brand.description }}</p>

                    <!-- Subscribe Form in Header -->
                    <div class="mt-8 max-w-md mx-auto">
                        <div v-if="subscribeSuccess" class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="text-green-800 font-medium">Thanks for subscribing!</p>
                            <p class="text-green-600 text-sm mt-1">Check your email to confirm your subscription.</p>
                        </div>
                        <form v-else @submit.prevent="subscribe" class="flex gap-2">
                            <input
                                v-model="subscribeForm.email"
                                type="email"
                                placeholder="Enter your email"
                                required
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                            <button
                                type="submit"
                                :disabled="subscribeForm.processing"
                                class="px-6 py-2 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition disabled:opacity-50"
                            >
                                {{ subscribeForm.processing ? '...' : 'Subscribe' }}
                            </button>
                        </form>
                        <p v-if="subscribeError" class="mt-2 text-sm text-red-600">{{ subscribeError }}</p>
                        <p v-else class="mt-2 text-sm text-gray-500">Get new posts delivered to your inbox.</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Posts -->
        <main class="max-w-3xl mx-auto px-6 py-12">
            <div v-if="posts.length > 0" class="space-y-12">
                <article v-for="post in posts" :key="post.id" class="group">
                    <Link :href="`/@${brand.slug}/${post.slug}`" class="block">
                        <img
                            v-if="post.featured_image"
                            :src="post.featured_image"
                            :alt="post.title"
                            class="w-full h-64 object-cover rounded-lg mb-4"
                        />
                        <h2 class="text-2xl font-bold text-gray-900 group-hover:text-primary-600 transition">
                            {{ post.title }}
                        </h2>
                        <p v-if="post.excerpt" class="mt-2 text-gray-600 line-clamp-3">
                            {{ post.excerpt }}
                        </p>
                        <time class="mt-3 block text-sm text-gray-500">
                            {{ post.published_at }}
                        </time>
                    </Link>
                </article>
            </div>

            <div v-else class="text-center py-12">
                <p class="text-gray-500">No posts yet. Check back soon!</p>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-100">
            <div class="max-w-3xl mx-auto px-6 py-8 text-center text-gray-500 text-sm">
                <p>&copy; {{ new Date().getFullYear() }} {{ brand.name }}. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
