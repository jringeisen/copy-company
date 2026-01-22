<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    brand: Object,
    post: Object,
});

const subscribeForm = useForm({
    email: '',
});

const subscribeSuccess = ref(false);
const subscribeError = ref('');

const subscribe = () => {
    subscribeError.value = '';
    subscribeForm.post(`/blog/${props.brand.slug}/subscribe`, {
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
        <title>{{ post.seo_title || post.title }} | {{ brand.name }}</title>
        <meta name="description" :content="post.seo_description || post.excerpt" />
    </Head>

    <div class="min-h-screen bg-white">
        <!-- Header -->
        <header class="border-b border-gray-100">
            <div class="max-w-3xl mx-auto px-6 py-6">
                <Link :href="`/blog/${brand.slug}`" class="flex items-center space-x-3 group">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span class="text-gray-600 group-hover:text-gray-900 font-medium">{{ brand.name }}</span>
                </Link>
            </div>
        </header>

        <!-- Article -->
        <article class="max-w-3xl mx-auto px-6 py-12">
            <!-- Featured Image -->
            <img
                v-if="post.featured_image"
                :src="post.featured_image"
                :alt="post.title"
                class="w-full h-96 object-cover rounded-xl mb-8"
            />

            <!-- Title -->
            <h1 class="text-4xl font-bold text-gray-900 leading-tight">
                {{ post.title }}
            </h1>

            <!-- Meta -->
            <div class="mt-4 flex items-center text-gray-500">
                <time>{{ post.published_at }}</time>
            </div>

            <!-- Excerpt -->
            <p v-if="post.excerpt" class="mt-6 text-xl text-gray-600 leading-relaxed">
                {{ post.excerpt }}
            </p>

            <!-- Content -->
            <div
                class="mt-8 prose prose-lg max-w-none prose-headings:font-bold prose-a:text-primary-600 prose-img:rounded-lg"
                v-html="post.content_html"
            ></div>

            <!-- Subscribe CTA after article -->
            <div class="mt-16 border-t border-gray-100 pt-12">
                <div class="bg-gray-50 rounded-xl p-8 text-center">
                    <h3 class="text-xl font-bold text-gray-900">Enjoyed this post?</h3>
                    <p class="mt-2 text-gray-600">Subscribe to get new posts from {{ brand.name }} delivered to your inbox.</p>

                    <div class="mt-6 max-w-md mx-auto">
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
                                class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            />
                            <button
                                type="submit"
                                :disabled="subscribeForm.processing"
                                class="px-6 py-3 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 transition disabled:opacity-50"
                            >
                                {{ subscribeForm.processing ? '...' : 'Subscribe' }}
                            </button>
                        </form>
                        <p v-if="subscribeError" class="mt-2 text-sm text-red-600">{{ subscribeError }}</p>
                    </div>
                </div>
            </div>
        </article>

        <!-- Footer -->
        <footer class="border-t border-gray-100">
            <div class="max-w-3xl mx-auto px-6 py-8">
                <div class="flex items-center justify-between">
                    <Link :href="`/blog/${brand.slug}`" class="text-gray-600 hover:text-gray-900">
                        &larr; Back to {{ brand.name }}
                    </Link>
                </div>
            </div>
        </footer>
    </div>
</template>
