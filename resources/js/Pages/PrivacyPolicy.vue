<script setup>
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';

const props = defineProps({
    appUrl: String,
});
</script>

<template>
    <SeoHead
        title="Privacy Policy - Copy Company"
        description="Learn how Copy Company collects, uses, and protects your personal information and content data."
        :url="`${appUrl}/privacy-policy`"
    />

    <div class="min-h-screen bg-[#fcfbf8]">
        <!-- Navigation -->
        <nav class="py-6 px-6 lg:px-12 border-b border-[#0b1215]/5">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <Link href="/">
                    <img src="/images/logo.svg" alt="Copy Company" class="h-10" />
                </Link>
                <div class="flex items-center gap-8">
                    <Link href="/login" class="text-sm text-[#0b1215]/70 hover:text-[#0b1215] transition">Sign in</Link>
                    <Link
                        href="/register"
                        class="px-6 py-2.5 bg-[#0b1215] text-white text-sm rounded-full hover:bg-[#0b1215]/90 transition"
                    >
                        Get Started
                    </Link>
                </div>
            </div>
        </nav>

        <main class="py-16 lg:py-24 px-6 lg:px-12">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="text-center mb-16">
                    <p class="text-[#a1854f] text-sm tracking-widest uppercase mb-4">Legal</p>
                    <h1 class="font-serif text-4xl lg:text-5xl font-light text-[#0b1215] mb-4">Privacy Policy</h1>
                    <p class="text-[#0b1215]/60">Last updated: January 20, 2026</p>
                </div>

                <!-- Content -->
                <div class="prose prose-lg max-w-none">
                    <div class="bg-white rounded-3xl p-8 lg:p-12 border border-[#0b1215]/5 space-y-8">
                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Introduction</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                Modern Market LLC ("we," "our," or "us") operates Copy Company (the "Service"). This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our Service. Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the Service.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Information We Collect</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed mb-4">We collect information that you provide directly to us, including:</p>
                            <ul class="list-disc list-inside space-y-2 text-[#0b1215]/70">
                                <li><strong class="text-[#0b1215]">Account Information:</strong> When you register for an account, we collect your name, email address, and password.</li>
                                <li><strong class="text-[#0b1215]">Brand Information:</strong> Information about your brand, including brand name, description, industry, and branding preferences.</li>
                                <li><strong class="text-[#0b1215]">Content:</strong> Blog posts, newsletters, social media posts, and other content you create using our Service.</li>
                                <li><strong class="text-[#0b1215]">Subscriber Data:</strong> Email addresses and related information of your newsletter subscribers.</li>
                                <li><strong class="text-[#0b1215]">Payment Information:</strong> When you subscribe to a paid plan, payment information is collected and processed by our payment processor, Stripe.</li>
                                <li><strong class="text-[#0b1215]">Social Media Accounts:</strong> When you connect social media accounts, we receive access tokens to publish content on your behalf.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Automatically Collected Information</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed mb-4">When you access our Service, we may automatically collect:</p>
                            <ul class="list-disc list-inside space-y-2 text-[#0b1215]/70">
                                <li><strong class="text-[#0b1215]">Log Data:</strong> IP address, browser type, operating system, referring URLs, and pages viewed.</li>
                                <li><strong class="text-[#0b1215]">Device Information:</strong> Information about the device you use to access our Service.</li>
                                <li><strong class="text-[#0b1215]">Cookies:</strong> We use cookies and similar tracking technologies to track activity on our Service and hold certain information.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">How We Use Your Information</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed mb-4">We use the information we collect to:</p>
                            <ul class="list-disc list-inside space-y-2 text-[#0b1215]/70">
                                <li>Provide, maintain, and improve our Service</li>
                                <li>Process transactions and send related information</li>
                                <li>Send newsletters on your behalf to your subscribers</li>
                                <li>Publish social media posts to your connected accounts</li>
                                <li>Provide AI-powered content assistance features</li>
                                <li>Send you technical notices, updates, and support messages</li>
                                <li>Respond to your comments, questions, and requests</li>
                                <li>Monitor and analyze trends, usage, and activities</li>
                                <li>Detect, investigate, and prevent fraudulent transactions and other illegal activities</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">AI and Content Processing</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                Our Service includes AI-powered features that process your content to provide suggestions, generate ideas, and assist with writing. Content processed by AI features is used solely to provide the requested functionality. We do not use your content to train our AI models or share it with third parties except as necessary to provide the Service.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Information Sharing</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed mb-4">We may share your information in the following circumstances:</p>
                            <ul class="list-disc list-inside space-y-2 text-[#0b1215]/70">
                                <li><strong class="text-[#0b1215]">Service Providers:</strong> We work with third-party service providers (hosting, email delivery, payment processing, analytics) who need access to your information to perform their services.</li>
                                <li><strong class="text-[#0b1215]">Social Media Platforms:</strong> When you connect social accounts, your content is shared with those platforms for publishing.</li>
                                <li><strong class="text-[#0b1215]">Legal Requirements:</strong> We may disclose information if required by law or in response to valid legal requests.</li>
                                <li><strong class="text-[#0b1215]">Business Transfers:</strong> In connection with any merger, sale, or acquisition, your information may be transferred.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Data Retention</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                We retain your information for as long as your account is active or as needed to provide you services. You can request deletion of your account and associated data at any time by contacting us. We may retain certain information as required by law or for legitimate business purposes.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Your Rights</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed mb-4">Depending on your location, you may have certain rights regarding your personal information:</p>
                            <ul class="list-disc list-inside space-y-2 text-[#0b1215]/70">
                                <li>Access and receive a copy of your personal data</li>
                                <li>Correct inaccurate personal data</li>
                                <li>Request deletion of your personal data</li>
                                <li>Object to or restrict processing of your personal data</li>
                                <li>Data portability (export your content and subscriber data)</li>
                                <li>Withdraw consent at any time</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Data Security</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the Internet or electronic storage is 100% secure.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Children's Privacy</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                Our Service is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13. If you are a parent or guardian and believe your child has provided us with personal information, please contact us.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Changes to This Policy</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date. You are advised to review this Privacy Policy periodically for any changes.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Contact Us</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                If you have any questions about this Privacy Policy, please contact us at:
                            </p>
                            <div class="mt-4 p-6 bg-[#f7f7f7] rounded-2xl">
                                <p class="text-[#0b1215] font-medium">Modern Market LLC</p>
                                <p class="text-[#0b1215]/70">Email: support@modernmarket.co</p>
                            </div>
                        </section>
                    </div>
                </div>

                <!-- Back to Home -->
                <div class="mt-12 text-center">
                    <Link href="/" class="text-[#a1854f] hover:text-[#0b1215] transition text-sm">
                        &larr; Back to Home
                    </Link>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-[#fcfbf8] border-t border-[#0b1215]/5 py-12 px-6 lg:px-12">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <div class="text-[#0b1215]/50 text-sm">
                        &copy; {{ new Date().getFullYear() }} Copy Company. All rights reserved.
                    </div>
                    <div class="flex items-center gap-6 text-sm">
                        <Link href="/privacy-policy" class="text-[#0b1215]/70 hover:text-[#0b1215] transition">Privacy Policy</Link>
                        <Link href="/terms-of-service" class="text-[#0b1215]/70 hover:text-[#0b1215] transition">Terms of Service</Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
.font-serif {
    font-family: 'Georgia', 'Times New Roman', serif;
}
</style>
