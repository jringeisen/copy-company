<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    subtitle: String,
});
</script>

<template>
    <div class="min-h-screen bg-[#fcfbf8] flex flex-col">
        <!-- Main Content -->
        <main class="flex-1 flex flex-col items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                <!-- Logo -->
                <div class="text-center mb-8">
                    <Link href="/">
                        <img src="/images/logo.svg" alt="Copy Company" class="h-12 mx-auto" />
                    </Link>
                </div>

                <!-- Title Section -->
                <div class="text-center mb-8" v-if="title">
                    <h1 class="font-serif text-3xl lg:text-4xl font-light text-[#0b1215] mb-3">
                        {{ title }}
                    </h1>
                    <p v-if="subtitle" class="text-[#0b1215]/60">
                        {{ subtitle }}
                    </p>
                </div>

                <!-- Form Card -->
                <div class="bg-white rounded-3xl p-8 border border-[#0b1215]/5 shadow-sm">
                    <slot />
                </div>

                <!-- Footer Slot -->
                <div class="mt-6 text-center">
                    <slot name="footer" />
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="py-6 px-6 lg:px-12 border-t border-[#0b1215]/5">
            <div class="max-w-7xl mx-auto text-center text-[#0b1215]/50 text-sm">
                &copy; {{ new Date().getFullYear() }} Copy Company. All rights reserved.
            </div>
        </footer>
    </div>
</template>

<style>
.font-serif {
    font-family: 'Georgia', 'Times New Roman', serif;
}
</style>
