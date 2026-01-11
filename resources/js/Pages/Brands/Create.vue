<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const form = useForm({
    name: '',
    slug: '',
    tagline: '',
    description: '',
    industry: '',
    primary_color: '#6366f1',
});

const slugEdited = ref(false);

// Auto-generate slug from name unless manually edited
watch(() => form.name, (newName) => {
    if (!slugEdited.value) {
        form.slug = newName
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }
});

const onSlugInput = () => {
    slugEdited.value = true;
};

const industries = [
    'Technology',
    'Marketing',
    'Finance',
    'Health & Wellness',
    'Education',
    'Creative & Design',
    'Food & Beverage',
    'Travel',
    'Real Estate',
    'E-commerce',
    'Other',
];

const submit = () => {
    form.post('/brands');
};
</script>

<template>
    <Head title="Create Your Brand" />

    <div class="min-h-screen bg-[#fcfbf8] py-12">
        <div class="max-w-2xl mx-auto px-4">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-[#0b1215]">Create Your Brand</h1>
                <p class="mt-2 text-[#0b1215]/60">Set up your brand identity to start publishing content.</p>
            </div>

            <div class="bg-white rounded-2xl border border-[#0b1215]/10 p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Brand Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-[#0b1215]">Brand Name *</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                            placeholder="My Awesome Brand"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <!-- Slug -->
                    <div>
                        <label for="slug" class="block text-sm font-medium text-[#0b1215]">URL Slug *</label>
                        <div class="mt-1 flex rounded-xl overflow-hidden border border-[#0b1215]/20">
                            <span class="inline-flex items-center px-4 bg-[#f7f7f7] text-[#0b1215]/50 text-sm border-r border-[#0b1215]/20">
                                {{ $page.props.appUrl || 'yoursite.com' }}/@
                            </span>
                            <input
                                id="slug"
                                v-model="form.slug"
                                @input="onSlugInput"
                                type="text"
                                class="flex-1 block w-full px-4 py-3 border-0 focus:ring-[#0b1215]/20"
                                placeholder="my-brand"
                                required
                            />
                        </div>
                        <p class="mt-1 text-sm text-[#0b1215]/50">This will be your public URL. Only lowercase letters, numbers, and hyphens.</p>
                        <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                    </div>

                    <!-- Tagline -->
                    <div>
                        <label for="tagline" class="block text-sm font-medium text-[#0b1215]">Tagline</label>
                        <input
                            id="tagline"
                            v-model="form.tagline"
                            type="text"
                            class="mt-1 block w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                            placeholder="A brief description of what you do"
                        />
                        <p v-if="form.errors.tagline" class="mt-1 text-sm text-red-600">{{ form.errors.tagline }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-[#0b1215]">Description</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                            placeholder="Tell your audience what your brand is about..."
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <!-- Industry -->
                    <div>
                        <label for="industry" class="block text-sm font-medium text-[#0b1215]">Industry</label>
                        <select
                            id="industry"
                            v-model="form.industry"
                            class="mt-1 block w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                        >
                            <option value="">Select an industry (optional)</option>
                            <option v-for="industry in industries" :key="industry" :value="industry">
                                {{ industry }}
                            </option>
                        </select>
                        <p class="mt-1 text-sm text-[#0b1215]/50">Helps AI understand your content context better.</p>
                        <p v-if="form.errors.industry" class="mt-1 text-sm text-red-600">{{ form.errors.industry }}</p>
                    </div>

                    <!-- Primary Color -->
                    <div>
                        <label for="primary_color" class="block text-sm font-medium text-[#0b1215]">Brand Color</label>
                        <div class="mt-1 flex items-center gap-3">
                            <input
                                id="primary_color"
                                v-model="form.primary_color"
                                type="color"
                                class="h-10 w-16 rounded-xl border border-[#0b1215]/20 cursor-pointer"
                            />
                            <input
                                v-model="form.primary_color"
                                type="text"
                                class="flex-1 px-4 py-2 border border-[#0b1215]/20 rounded-xl focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                                placeholder="#6366f1"
                            />
                        </div>
                        <p v-if="form.errors.primary_color" class="mt-1 text-sm text-red-600">{{ form.errors.primary_color }}</p>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-between pt-4">
                        <Link href="/dashboard" class="text-[#0b1215]/60 hover:text-[#0b1215] transition-colors">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            class="px-6 py-3 bg-[#0b1215] text-white font-semibold rounded-full hover:bg-[#0b1215]/90 focus:outline-none focus:ring-2 focus:ring-[#0b1215]/20 focus:ring-offset-2 transition"
                            :class="{ 'opacity-50': form.processing }"
                            :disabled="form.processing"
                        >
                            Create Brand
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
