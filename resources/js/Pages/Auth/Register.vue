<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    industry: '',
    biggest_struggle: '',
    referral_source: '',
});

const industries = [
    { value: 'blogging', label: 'Blogging / Content Creation' },
    { value: 'ecommerce', label: 'E-commerce' },
    { value: 'saas', label: 'SaaS / Technology' },
    { value: 'marketing', label: 'Marketing / Agency' },
    { value: 'coaching', label: 'Coaching / Consulting' },
    { value: 'education', label: 'Education' },
    { value: 'health', label: 'Health & Wellness' },
    { value: 'finance', label: 'Finance' },
    { value: 'real_estate', label: 'Real Estate' },
    { value: 'nonprofit', label: 'Non-profit' },
    { value: 'other', label: 'Other' },
];

const struggles = [
    { value: 'growing_audience', label: 'Growing my audience' },
    { value: 'creating_consistently', label: 'Creating content consistently' },
    { value: 'managing_platforms', label: 'Managing multiple platforms' },
    { value: 'email_deliverability', label: 'Email deliverability' },
    { value: 'monetizing', label: 'Monetizing my content' },
    { value: 'finding_time', label: 'Finding time to create' },
    { value: 'understanding_analytics', label: 'Understanding analytics' },
    { value: 'other', label: 'Other' },
];

const referralSources = [
    { value: 'google', label: 'Google search' },
    { value: 'social_media', label: 'Social media' },
    { value: 'friend', label: 'Friend / Colleague' },
    { value: 'blog', label: 'Blog / Article' },
    { value: 'podcast', label: 'Podcast' },
    { value: 'youtube', label: 'YouTube' },
    { value: 'other', label: 'Other' },
];

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <GuestLayout title="Create your account" subtitle="Get started with Copy Company today.">
        <form @submit.prevent="submit">
            <div>
                <label for="name" class="block text-sm font-medium text-[#0b1215] mb-2">Name</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30 transition"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Your name"
                />
                <p v-if="form.errors.name" class="mt-2 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>

            <div class="mt-5">
                <label for="email" class="block text-sm font-medium text-[#0b1215] mb-2">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30 transition"
                    required
                    autocomplete="username"
                    placeholder="you@example.com"
                />
                <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
            </div>

            <div class="mt-5">
                <label for="password" class="block text-sm font-medium text-[#0b1215] mb-2">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30 transition"
                    required
                    autocomplete="new-password"
                    placeholder="Create a password"
                />
                <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>

            <div class="mt-5">
                <label for="password_confirmation" class="block text-sm font-medium text-[#0b1215] mb-2">Confirm Password</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30 transition"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm your password"
                />
                <p v-if="form.errors.password_confirmation" class="mt-2 text-sm text-red-600">{{ form.errors.password_confirmation }}</p>
            </div>

            <!-- Divider -->
            <div class="mt-8 mb-6 border-t border-[#0b1215]/10 pt-6">
                <p class="text-sm text-[#0b1215]/60 text-center">Help us personalize your experience</p>
            </div>

            <div>
                <label for="industry" class="block text-sm font-medium text-[#0b1215] mb-2">What industry are you in?</label>
                <select
                    id="industry"
                    v-model="form.industry"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30 transition appearance-none"
                    required
                >
                    <option value="" disabled>Select your industry</option>
                    <option v-for="industry in industries" :key="industry.value" :value="industry.value">
                        {{ industry.label }}
                    </option>
                </select>
                <p v-if="form.errors.industry" class="mt-2 text-sm text-red-600">{{ form.errors.industry }}</p>
            </div>

            <div class="mt-5">
                <label for="biggest_struggle" class="block text-sm font-medium text-[#0b1215] mb-2">What's your biggest struggle?</label>
                <select
                    id="biggest_struggle"
                    v-model="form.biggest_struggle"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30 transition appearance-none"
                    required
                >
                    <option value="" disabled>Select your biggest challenge</option>
                    <option v-for="struggle in struggles" :key="struggle.value" :value="struggle.value">
                        {{ struggle.label }}
                    </option>
                </select>
                <p v-if="form.errors.biggest_struggle" class="mt-2 text-sm text-red-600">{{ form.errors.biggest_struggle }}</p>
            </div>

            <div class="mt-5">
                <label for="referral_source" class="block text-sm font-medium text-[#0b1215] mb-2">How did you hear about us?</label>
                <select
                    id="referral_source"
                    v-model="form.referral_source"
                    class="w-full px-4 py-3 bg-[#f7f7f7] border-0 rounded-xl focus:outline-none focus:ring-2 focus:ring-[#a1854f]/30 transition appearance-none"
                    required
                >
                    <option value="" disabled>Select how you found us</option>
                    <option v-for="source in referralSources" :key="source.value" :value="source.value">
                        {{ source.label }}
                    </option>
                </select>
                <p v-if="form.errors.referral_source" class="mt-2 text-sm text-red-600">{{ form.errors.referral_source }}</p>
            </div>

            <button
                type="submit"
                class="w-full mt-6 px-6 py-3.5 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 transition text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                :disabled="form.processing"
            >
                Create account
            </button>
        </form>

        <template #footer>
            <span class="text-sm text-[#0b1215]/60">Already have an account?</span>
            <Link href="/login" class="ml-1 text-sm text-[#0b1215] font-medium hover:underline">
                Sign in
            </Link>
        </template>
    </GuestLayout>
</template>
