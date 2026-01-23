<script setup>
import { Link } from '@inertiajs/vue3';
import SeoHead from '@/Components/SeoHead.vue';

const props = defineProps({
    appUrl: String,
});
</script>

<template>
    <SeoHead
        title="Terms of Service - Copy Company"
        description="Read the terms and conditions for using Copy Company, including subscription terms, content policies, and acceptable use."
        :url="`${appUrl}/terms-of-service`"
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
                    <h1 class="font-serif text-4xl lg:text-5xl font-light text-[#0b1215] mb-4">Terms of Service</h1>
                    <p class="text-[#0b1215]/60">Last updated: January 20, 2026</p>
                </div>

                <!-- Content -->
                <div class="prose prose-lg max-w-none">
                    <div class="bg-white rounded-3xl p-8 lg:p-12 border border-[#0b1215]/5 space-y-8">
                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Agreement to Terms</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                These Terms of Service ("Terms") constitute a legally binding agreement between you and Modern Market LLC ("Company," "we," "our," or "us") concerning your access to and use of Copy Company (the "Service"). By accessing or using the Service, you agree to be bound by these Terms. If you disagree with any part of these Terms, you may not access the Service.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Description of Service</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                Copy Company is a content platform that enables users to create, manage, and distribute content across multiple channels including blogs, newsletters, and social media. The Service includes AI-powered content assistance tools, subscriber management, and social media publishing features.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Account Registration</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed mb-4">To use the Service, you must:</p>
                            <ul class="list-disc list-inside space-y-2 text-[#0b1215]/70">
                                <li>Be at least 18 years of age</li>
                                <li>Provide accurate, complete, and current information during registration</li>
                                <li>Maintain the security of your password and account</li>
                                <li>Promptly update your account information if it changes</li>
                                <li>Accept responsibility for all activities that occur under your account</li>
                            </ul>
                            <p class="text-[#0b1215]/70 leading-relaxed mt-4">
                                You must notify us immediately upon becoming aware of any breach of security or unauthorized use of your account.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Subscription and Payments</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed mb-4">
                                Some features of the Service require a paid subscription. By subscribing to a paid plan, you agree to the following:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-[#0b1215]/70">
                                <li><strong class="text-[#0b1215]">Billing:</strong> You authorize us to charge the payment method on file for all applicable fees on a recurring basis.</li>
                                <li><strong class="text-[#0b1215]">Price Changes:</strong> We may change subscription prices with 30 days' notice. Continued use after a price change constitutes acceptance.</li>
                                <li><strong class="text-[#0b1215]">Refunds:</strong> Subscription fees are generally non-refundable except as required by law or as explicitly stated in our refund policy.</li>
                                <li><strong class="text-[#0b1215]">Cancellation:</strong> You may cancel your subscription at any time. Your subscription will remain active until the end of the current billing period.</li>
                                <li><strong class="text-[#0b1215]">Email Costs:</strong> Email sending costs are billed based on usage at the rate of $0.40 per 1,000 emails sent.</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Your Content</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed mb-4">
                                You retain ownership of all content you create, upload, or publish through the Service ("Your Content"). By using the Service, you grant us a limited license to:
                            </p>
                            <ul class="list-disc list-inside space-y-2 text-[#0b1215]/70">
                                <li>Store and display Your Content on the Service</li>
                                <li>Send Your Content to your subscribers via email</li>
                                <li>Publish Your Content to your connected social media accounts</li>
                                <li>Process Your Content through AI features to provide requested functionality</li>
                            </ul>
                            <p class="text-[#0b1215]/70 leading-relaxed mt-4">
                                This license terminates when you delete Your Content or close your account.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Acceptable Use</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed mb-4">You agree not to use the Service to:</p>
                            <ul class="list-disc list-inside space-y-2 text-[#0b1215]/70">
                                <li>Violate any applicable laws or regulations</li>
                                <li>Infringe on the intellectual property rights of others</li>
                                <li>Send unsolicited emails or spam to non-subscribers</li>
                                <li>Distribute malware, viruses, or other harmful code</li>
                                <li>Harass, threaten, or harm other users</li>
                                <li>Impersonate any person or entity</li>
                                <li>Collect or harvest personal information without consent</li>
                                <li>Interfere with or disrupt the Service or servers</li>
                                <li>Attempt to gain unauthorized access to any part of the Service</li>
                                <li>Use the Service for any illegal or unauthorized purpose</li>
                            </ul>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Email and Newsletter Compliance</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed mb-4">When using our newsletter features, you agree to:</p>
                            <ul class="list-disc list-inside space-y-2 text-[#0b1215]/70">
                                <li>Only send emails to subscribers who have opted in to receive communications</li>
                                <li>Honor all unsubscribe requests promptly</li>
                                <li>Comply with all applicable anti-spam laws including CAN-SPAM, GDPR, and CASL</li>
                                <li>Include accurate sender information in all emails</li>
                                <li>Not send deceptive or misleading content</li>
                            </ul>
                            <p class="text-[#0b1215]/70 leading-relaxed mt-4">
                                We reserve the right to suspend or terminate accounts that violate these email policies.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Social Media Integration</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                When you connect social media accounts to the Service, you are responsible for ensuring your use complies with the terms of service of those platforms. We are not responsible for any actions taken by social media platforms regarding your accounts or content.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">AI Features</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                Our Service includes AI-powered features to assist with content creation. You acknowledge that AI-generated content may require review and editing. You are solely responsible for the accuracy and appropriateness of any content you publish, whether AI-assisted or not.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Intellectual Property</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                The Service and its original content (excluding Your Content), features, and functionality are and will remain the exclusive property of Modern Market LLC. The Service is protected by copyright, trademark, and other laws. Our trademarks may not be used without our prior written consent.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Termination</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                We may terminate or suspend your account immediately, without prior notice or liability, for any reason, including breach of these Terms. Upon termination, your right to use the Service will cease immediately. You may terminate your account at any time by contacting us or using the account deletion feature.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Disclaimer of Warranties</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                THE SERVICE IS PROVIDED ON AN "AS IS" AND "AS AVAILABLE" BASIS WITHOUT ANY WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, NON-INFRINGEMENT, OR COURSE OF PERFORMANCE. WE DO NOT WARRANT THAT THE SERVICE WILL BE UNINTERRUPTED, SECURE, OR ERROR-FREE.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Limitation of Liability</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                IN NO EVENT SHALL MODERN MARKET LLC, ITS DIRECTORS, EMPLOYEES, PARTNERS, AGENTS, SUPPLIERS, OR AFFILIATES BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING WITHOUT LIMITATION, LOSS OF PROFITS, DATA, USE, GOODWILL, OR OTHER INTANGIBLE LOSSES, RESULTING FROM YOUR USE OF THE SERVICE. OUR TOTAL LIABILITY SHALL NOT EXCEED THE AMOUNT YOU PAID US IN THE TWELVE MONTHS PRECEDING THE CLAIM.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Indemnification</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                You agree to defend, indemnify, and hold harmless Modern Market LLC and its affiliates, officers, directors, employees, and agents from any claims, damages, obligations, losses, liabilities, costs, or debt arising from your use of the Service or violation of these Terms.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Governing Law</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                These Terms shall be governed by and construed in accordance with the laws of the State of Delaware, United States, without regard to its conflict of law provisions.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Changes to Terms</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                We reserve the right to modify or replace these Terms at any time. If a revision is material, we will provide at least 30 days' notice prior to any new terms taking effect. Continued use of the Service after any changes constitutes acceptance of those changes.
                            </p>
                        </section>

                        <section>
                            <h2 class="text-2xl font-medium text-[#0b1215] mb-4">Contact Us</h2>
                            <p class="text-[#0b1215]/70 leading-relaxed">
                                If you have any questions about these Terms, please contact us at:
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
