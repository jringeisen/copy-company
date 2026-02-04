import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useSubscription() {
    const page = usePage();

    const subscription = computed(() => page.props.subscription || {});

    const plan = computed(() => subscription.value.plan);
    const planLabel = computed(() => subscription.value.plan_label || 'No Plan');
    const onTrial = computed(() => subscription.value.on_trial || false);
    const isFreeTrial = computed(() => subscription.value.is_free_trial_only || false);
    const isSubscribed = computed(() => subscription.value.is_subscribed || false);
    const hasSubscription = computed(() => subscription.value.has_subscription || false);

    // Limit checks
    const canCreatePost = computed(() => subscription.value.can_create_post ?? true);
    const canCreateSprint = computed(() => subscription.value.can_create_sprint ?? true);
    const canAddSocial = computed(() => subscription.value.can_add_social ?? true);
    const canSendNewsletter = computed(() => subscription.value.can_send_newsletter ?? false);

    // Feature checks
    const features = computed(() => subscription.value.features || {});
    const hasCustomDomain = computed(() => features.value.custom_domain || false);
    const hasCustomEmailDomain = computed(() => features.value.custom_email_domain || false);
    const hasRemoveBranding = computed(() => features.value.remove_branding || false);
    const hasAnalytics = computed(() => features.value.analytics || false);
    const hasMarketingStrategy = computed(() => features.value.marketing_strategy || false);

    /**
     * Get the required plan for a feature
     */
    const getRequiredPlan = (feature) => {
        const requiredPlans = {
            posts: 'creator',
            content_sprints: 'creator',
            social_accounts: 'creator',
            custom_domain: 'creator',
            analytics: 'creator',
            marketing_strategy: 'creator',
            custom_email_domain: 'pro',
            remove_branding: 'pro',
        };
        return requiredPlans[feature] || 'starter';
    };

    return {
        subscription,
        plan,
        planLabel,
        onTrial,
        isFreeTrial,
        isSubscribed,
        hasSubscription,
        // Limits
        canCreatePost,
        canCreateSprint,
        canAddSocial,
        canSendNewsletter,
        // Features
        features,
        hasCustomDomain,
        hasCustomEmailDomain,
        hasRemoveBranding,
        hasAnalytics,
        hasMarketingStrategy,
        // Helpers
        getRequiredPlan,
    };
}
