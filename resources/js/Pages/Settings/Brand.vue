<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    brand: Object,
});

const voiceSettings = props.brand.voice_settings || {};

const form = useForm({
    name: props.brand.name,
    slug: props.brand.slug,
    tagline: props.brand.tagline || '',
    description: props.brand.description || '',
    industry: props.brand.industry || '',
    primary_color: props.brand.primary_color || '#6366f1',
    secondary_color: props.brand.secondary_color || '#4f46e5',
    voice_settings: {
        tone: voiceSettings.tone || '',
        style: voiceSettings.style || '',
        sample_texts: voiceSettings.sample_texts || [],
    },
});

const newSampleText = ref('');

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

const tones = [
    { value: 'professional', label: 'Professional', description: 'Polished and business-appropriate' },
    { value: 'casual', label: 'Casual', description: 'Relaxed and approachable' },
    { value: 'friendly', label: 'Friendly', description: 'Warm and personable' },
    { value: 'formal', label: 'Formal', description: 'Traditional and respectful' },
    { value: 'persuasive', label: 'Persuasive', description: 'Compelling and action-oriented' },
];

const styles = [
    { value: 'conversational', label: 'Conversational', description: 'Like talking to a friend' },
    { value: 'academic', label: 'Academic', description: 'Research-backed and thorough' },
    { value: 'storytelling', label: 'Storytelling', description: 'Narrative-driven with examples' },
    { value: 'instructional', label: 'Instructional', description: 'Step-by-step and educational' },
];

const addSampleText = () => {
    if (newSampleText.value.trim() && form.voice_settings.sample_texts.length < 3) {
        form.voice_settings.sample_texts.push(newSampleText.value.trim());
        newSampleText.value = '';
    }
};

const removeSampleText = (index) => {
    form.voice_settings.sample_texts.splice(index, 1);
};

const submit = () => {
    form.put(`/settings/brand/${props.brand.id}`);
};
</script>

<template>
    <Head title="Brand Settings" />

    <AppLayout current-page="brand-settings">
        <div class="max-w-3xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Brand Settings</h1>
                <p class="text-gray-600 mt-1">Update your brand identity and preferences</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Brand Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Brand Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                            required
                        />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>

                    <!-- Slug -->
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700">URL Slug</label>
                        <div class="mt-1 flex rounded-lg shadow-sm">
                            <span class="inline-flex items-center px-4 rounded-l-lg border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                /@
                            </span>
                            <input
                                id="slug"
                                v-model="form.slug"
                                type="text"
                                class="flex-1 block w-full px-4 py-3 border border-gray-300 rounded-r-lg focus:ring-primary-500 focus:border-primary-500"
                                required
                            />
                        </div>
                        <p class="mt-1 text-sm text-gray-500">
                            Your public blog URL:
                            <a
                                :href="`/@${form.slug}`"
                                target="_blank"
                                class="text-primary-600 hover:text-primary-700 hover:underline"
                            >
                                /@{{ form.slug }}
                                <svg class="inline-block w-3 h-3 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </p>
                        <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                    </div>

                    <!-- Tagline -->
                    <div>
                        <label for="tagline" class="block text-sm font-medium text-gray-700">Tagline</label>
                        <input
                            id="tagline"
                            v-model="form.tagline"
                            type="text"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                            placeholder="A brief description of what you do"
                        />
                        <p v-if="form.errors.tagline" class="mt-1 text-sm text-red-600">{{ form.errors.tagline }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                            placeholder="Tell your audience what your brand is about..."
                        ></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                    </div>

                    <!-- Industry -->
                    <div>
                        <label for="industry" class="block text-sm font-medium text-gray-700">Industry</label>
                        <select
                            id="industry"
                            v-model="form.industry"
                            class="mt-1 block w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="">Select an industry</option>
                            <option v-for="industry in industries" :key="industry" :value="industry">
                                {{ industry }}
                            </option>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">Helps AI understand your content context better.</p>
                        <p v-if="form.errors.industry" class="mt-1 text-sm text-red-600">{{ form.errors.industry }}</p>
                    </div>

                    <!-- Colors -->
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label for="primary_color" class="block text-sm font-medium text-gray-700">Primary Color</label>
                            <div class="mt-1 flex items-center gap-3">
                                <input
                                    id="primary_color"
                                    v-model="form.primary_color"
                                    type="color"
                                    class="h-10 w-16 rounded border border-gray-300 cursor-pointer"
                                />
                                <input
                                    v-model="form.primary_color"
                                    type="text"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                            <p v-if="form.errors.primary_color" class="mt-1 text-sm text-red-600">{{ form.errors.primary_color }}</p>
                        </div>

                        <div>
                            <label for="secondary_color" class="block text-sm font-medium text-gray-700">Secondary Color</label>
                            <div class="mt-1 flex items-center gap-3">
                                <input
                                    id="secondary_color"
                                    v-model="form.secondary_color"
                                    type="color"
                                    class="h-10 w-16 rounded border border-gray-300 cursor-pointer"
                                />
                                <input
                                    v-model="form.secondary_color"
                                    type="text"
                                    class="flex-1 px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                            <p v-if="form.errors.secondary_color" class="mt-1 text-sm text-red-600">{{ form.errors.secondary_color }}</p>
                        </div>
                    </div>

                    <!-- AI Voice Settings -->
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">AI Voice Settings</h3>
                        <p class="text-sm text-gray-500 mb-6">Customize how the AI writes content for your brand. These settings help the AI match your unique voice and style.</p>

                        <!-- Tone -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Writing Tone</label>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <label
                                    v-for="tone in tones"
                                    :key="tone.value"
                                    class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none"
                                    :class="form.voice_settings.tone === tone.value ? 'border-primary-500 bg-primary-50' : 'border-gray-300 hover:border-gray-400'"
                                >
                                    <input
                                        type="radio"
                                        :value="tone.value"
                                        v-model="form.voice_settings.tone"
                                        class="sr-only"
                                    />
                                    <div class="flex flex-col">
                                        <span class="block text-sm font-medium" :class="form.voice_settings.tone === tone.value ? 'text-primary-900' : 'text-gray-900'">
                                            {{ tone.label }}
                                        </span>
                                        <span class="mt-1 text-xs" :class="form.voice_settings.tone === tone.value ? 'text-primary-700' : 'text-gray-500'">
                                            {{ tone.description }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Style -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Writing Style</label>
                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    v-for="style in styles"
                                    :key="style.value"
                                    class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none"
                                    :class="form.voice_settings.style === style.value ? 'border-primary-500 bg-primary-50' : 'border-gray-300 hover:border-gray-400'"
                                >
                                    <input
                                        type="radio"
                                        :value="style.value"
                                        v-model="form.voice_settings.style"
                                        class="sr-only"
                                    />
                                    <div class="flex flex-col">
                                        <span class="block text-sm font-medium" :class="form.voice_settings.style === style.value ? 'text-primary-900' : 'text-gray-900'">
                                            {{ style.label }}
                                        </span>
                                        <span class="mt-1 text-xs" :class="form.voice_settings.style === style.value ? 'text-primary-700' : 'text-gray-500'">
                                            {{ style.description }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Sample Texts -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sample Writing (Optional)</label>
                            <p class="text-sm text-gray-500 mb-3">Add up to 3 examples of your writing style. The AI will learn from these to better match your voice.</p>

                            <!-- Existing samples -->
                            <div v-if="form.voice_settings.sample_texts.length > 0" class="space-y-3 mb-4">
                                <div
                                    v-for="(sample, index) in form.voice_settings.sample_texts"
                                    :key="index"
                                    class="relative bg-gray-50 rounded-lg p-4 pr-12"
                                >
                                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ sample }}</p>
                                    <button
                                        type="button"
                                        @click="removeSampleText(index)"
                                        class="absolute top-2 right-2 text-gray-400 hover:text-red-500"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Add new sample -->
                            <div v-if="form.voice_settings.sample_texts.length < 3">
                                <textarea
                                    v-model="newSampleText"
                                    rows="3"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-500 focus:border-primary-500"
                                    placeholder="Paste a paragraph from your blog, newsletter, or social media that represents your writing style..."
                                ></textarea>
                                <button
                                    type="button"
                                    @click="addSampleText"
                                    :disabled="!newSampleText.trim()"
                                    class="mt-2 px-4 py-2 text-sm font-medium text-primary-600 hover:text-primary-700 disabled:text-gray-400 disabled:cursor-not-allowed"
                                >
                                    + Add Sample
                                </button>
                            </div>
                            <p v-else class="text-sm text-gray-500">Maximum of 3 samples added.</p>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button
                            type="submit"
                            class="px-6 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition"
                            :class="{ 'opacity-50': form.processing }"
                            :disabled="form.processing"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
