<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-gradient-to-br from-primary-50 to-white">
        <!-- Navigation -->
        <nav class="p-6">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <span class="text-2xl font-bold text-gray-900">Content Platform</span>
                <div class="space-x-4">
                    <Link href="/login" class="text-gray-600 hover:text-gray-900">Log in</Link>
                    <Link
                        href="/register"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
                    >
                        Get Started
                    </Link>
                </div>
            </div>
        </nav>

        <main class="max-w-7xl mx-auto px-6">
            <!-- Hero Section -->
            <div class="py-20 text-center">
                <h1 class="text-5xl font-bold text-gray-900 mb-6">
                    Write Once, Publish Everywhere
                </h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto mb-10">
                    A creator-first content platform where your blog post becomes your newsletter,
                    and AI helps you reach your audience on every channel.
                </p>
                <div class="flex justify-center gap-4">
                    <Link
                        href="/register"
                        class="px-8 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition"
                    >
                        Start Creating
                    </Link>
                    <a
                        href="#features"
                        class="px-8 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition"
                    >
                        Learn More
                    </a>
                </div>
            </div>

            <!-- Core Features -->
            <div id="features" class="py-16">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Everything You Need to Create</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        From ideation to publication, we've got you covered with powerful tools that work together seamlessly.
                    </p>
                </div>

                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Blog Posts -->
                    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Rich Blog Editor</h3>
                        <p class="text-gray-600">
                            A distraction-free writing experience with markdown support. Draft, schedule, and publish posts to your hosted blog.
                        </p>
                    </div>

                    <!-- Newsletter -->
                    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Built-in Newsletter</h3>
                        <p class="text-gray-600">
                            Automatically send your blog posts to subscribers. Manage your list, import/export contacts, and track engagement.
                        </p>
                    </div>

                    <!-- Social Media -->
                    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Social Media Management</h3>
                        <p class="text-gray-600">
                            Create, schedule, and queue social posts. Manage content for multiple platforms from one place.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Social Publishing Section -->
            <div class="py-16 border-t border-gray-100">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">Publish Directly to Social Media</h2>
                        <p class="text-lg text-gray-600 mb-6">
                            Connect your social accounts and publish content directly from the platform. No more copying and pasting between apps.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <!-- Twitter/X -->
                            <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                                <span class="text-sm font-medium">X / Twitter</span>
                            </div>
                            <!-- Facebook -->
                            <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                <span class="text-sm font-medium">Facebook</span>
                            </div>
                            <!-- Instagram -->
                            <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-pink-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                                </svg>
                                <span class="text-sm font-medium">Instagram</span>
                            </div>
                            <!-- LinkedIn -->
                            <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-700" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                                <span class="text-sm font-medium">LinkedIn</span>
                            </div>
                            <!-- Pinterest -->
                            <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-red-600" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/>
                                </svg>
                                <span class="text-sm font-medium">Pinterest</span>
                            </div>
                            <!-- TikTok -->
                            <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                                </svg>
                                <span class="text-sm font-medium">TikTok</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Connected</div>
                                <div class="text-sm text-gray-500">@yourbrand</div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-600">
                                "Just published: 10 Tips for Better Content Marketing. Link in bio!"
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-500">Scheduled for 9:00 AM</span>
                                <span class="text-green-600 font-medium">Ready to publish</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Features Section -->
            <div class="py-16 border-t border-gray-100">
                <div class="grid md:grid-cols-2 gap-12 items-center">
                    <div class="order-2 md:order-1 bg-white rounded-xl shadow-lg p-6 border border-gray-200">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                            </div>
                            <span class="font-medium text-gray-900">AI Assistant</span>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div class="p-3 bg-primary-50 rounded-lg text-primary-800">
                                "Generate 5 blog post ideas about sustainable living"
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg text-gray-600">
                                <p class="font-medium mb-2">Here are 5 blog post ideas:</p>
                                <ol class="list-decimal list-inside space-y-1 text-gray-500">
                                    <li>Zero-Waste Kitchen Swaps</li>
                                    <li>Building a Capsule Wardrobe</li>
                                    <li>Composting for Beginners</li>
                                    <li>Eco-Friendly Travel Tips</li>
                                    <li>Sustainable Gift Guide</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="order-1 md:order-2">
                        <h2 class="text-3xl font-bold text-gray-900 mb-4">AI-Powered Content Creation</h2>
                        <p class="text-lg text-gray-600 mb-6">
                            Get help when you need it. Our AI assistant helps you brainstorm ideas, improve your writing, and generate social posts from your content.
                        </p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-600">Content Sprints for bulk idea generation</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-600">Writing assistant in the editor</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-600">Customizable brand voice settings</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-green-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="text-gray-600">Auto-generate social posts from blog content</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Content Calendar Section -->
            <div class="py-16 border-t border-gray-100">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Plan Your Content Calendar</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Visualize your content schedule across all platforms. Drag and drop to reschedule, and never miss a posting window.
                    </p>
                </div>
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                    <div class="grid grid-cols-7 gap-2 text-center text-sm font-medium text-gray-500 mb-4">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>
                    <div class="grid grid-cols-7 gap-2">
                        <div v-for="i in 7" :key="i" class="aspect-square p-2 bg-gray-50 rounded-lg">
                            <div class="text-xs text-gray-400 mb-1">{{ i + 7 }}</div>
                            <div v-if="i === 2" class="text-xs bg-primary-100 text-primary-700 rounded px-1 py-0.5 truncate">Blog Post</div>
                            <div v-if="i === 4" class="text-xs bg-purple-100 text-purple-700 rounded px-1 py-0.5 truncate">Twitter</div>
                            <div v-if="i === 4" class="text-xs bg-blue-100 text-blue-700 rounded px-1 py-0.5 truncate mt-1">LinkedIn</div>
                            <div v-if="i === 6" class="text-xs bg-pink-100 text-pink-700 rounded px-1 py-0.5 truncate">Instagram</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- More Features Grid -->
            <div class="py-16 border-t border-gray-100">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">And So Much More</h2>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Public Blog -->
                    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Hosted Public Blog</h3>
                        <p class="text-sm text-gray-600">Your own blog page at /@yourbrand. Share your content with the world.</p>
                    </div>

                    <!-- Subscriber Management -->
                    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Subscriber Management</h3>
                        <p class="text-sm text-gray-600">Import, export, and manage your email list. Track confirmed and unsubscribed users.</p>
                    </div>

                    <!-- Social Queue -->
                    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Social Post Queue</h3>
                        <p class="text-sm text-gray-600">Queue up posts and let them publish automatically. Never worry about posting times again.</p>
                    </div>

                    <!-- Brand Settings -->
                    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Brand Customization</h3>
                        <p class="text-sm text-gray-600">Set your brand colors, voice settings, and industry for personalized AI content.</p>
                    </div>

                    <!-- Content Sprints -->
                    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">Content Sprints</h3>
                        <p class="text-sm text-gray-600">Generate dozens of content ideas in one go. Perfect for planning your content strategy.</p>
                    </div>

                    <!-- Data Export -->
                    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mb-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-2">You Own Your Data</h3>
                        <p class="text-sm text-gray-600">Export your content and subscribers anytime. Your audience belongs to you.</p>
                    </div>
                </div>
            </div>

            <!-- Testimonials Section -->
            <div class="py-16 border-t border-gray-100">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Loved by Creators</h2>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        See what content creators are saying about their experience.
                    </p>
                </div>
                <div class="grid md:grid-cols-3 gap-8">
                    <!-- Testimonial 1 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-1 mb-4">
                            <svg v-for="n in 5" :key="n" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-6">
                            "This platform has completely transformed how I manage my content. I used to spend hours copying posts between platforms. Now I write once and publish everywhere in minutes."
                        </p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-full flex items-center justify-center text-white font-semibold">
                                SJ
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Sarah Johnson</div>
                                <div class="text-sm text-gray-500">Marketing Consultant</div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-1 mb-4">
                            <svg v-for="n in 5" :key="n" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-6">
                            "The Content Sprints feature is a game-changer. I generated a month's worth of blog ideas in 10 minutes. The AI actually understands my brand voice, which is rare."
                        </p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                                MC
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Marcus Chen</div>
                                <div class="text-sm text-gray-500">Tech Blogger</div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center gap-1 mb-4">
                            <svg v-for="n in 5" :key="n" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <p class="text-gray-600 mb-6">
                            "Finally, a platform that lets me own my subscriber list! I migrated from another service and the import was seamless. The built-in newsletter saves me $50/month."
                        </p>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center text-white font-semibold">
                                ER
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">Emily Rodriguez</div>
                                <div class="text-sm text-gray-500">Newsletter Creator</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="py-20 text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Ready to Simplify Your Content Workflow?</h2>
                <p class="text-lg text-gray-600 max-w-xl mx-auto mb-8">
                    Join creators who are saving hours every week by managing all their content in one place.
                </p>
                <Link
                    href="/register"
                    class="inline-flex px-8 py-3 bg-primary-600 text-white font-semibold rounded-lg hover:bg-primary-700 transition"
                >
                    Get Started for Free
                </Link>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 py-8">
            <div class="max-w-7xl mx-auto px-6 text-center text-gray-500 text-sm">
                &copy; {{ new Date().getFullYear() }} Content Platform. All rights reserved.
            </div>
        </footer>
    </div>
</template>
