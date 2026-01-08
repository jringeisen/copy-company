import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    const permissions = computed(() => page.props.auth?.permissions || []);

    /**
     * Check if the user has a specific permission
     */
    const can = (permission) => {
        return permissions.value.includes(permission);
    };

    /**
     * Check if the user has any of the given permissions
     */
    const canAny = (...permissionList) => {
        return permissionList.some((permission) => permissions.value.includes(permission));
    };

    /**
     * Check if the user has all of the given permissions
     */
    const canAll = (...permissionList) => {
        return permissionList.every((permission) => permissions.value.includes(permission));
    };

    // Convenience methods for common permission groups
    const canManagePosts = computed(() => canAny('posts.create', 'posts.update', 'posts.delete'));
    const canCreatePosts = computed(() => can('posts.create'));
    const canUpdatePosts = computed(() => can('posts.update'));
    const canDeletePosts = computed(() => can('posts.delete'));
    const canPublishPosts = computed(() => can('posts.publish'));

    const canManageSocial = computed(() => can('social.manage'));
    const canPublishSocial = computed(() => can('social.publish'));

    const canManageMedia = computed(() => canAny('media.upload', 'media.delete'));
    const canUploadMedia = computed(() => can('media.upload'));
    const canDeleteMedia = computed(() => can('media.delete'));

    const canManageSubscribers = computed(() => canAny('subscribers.view', 'subscribers.export', 'subscribers.delete'));
    const canViewSubscribers = computed(() => can('subscribers.view'));
    const canExportSubscribers = computed(() => can('subscribers.export'));
    const canDeleteSubscribers = computed(() => can('subscribers.delete'));

    const canManageSprints = computed(() => canAny('sprints.create', 'sprints.manage'));
    const canCreateSprints = computed(() => can('sprints.create'));

    const canManageBrand = computed(() => canAny('brands.update', 'brands.delete'));
    const canUpdateBrand = computed(() => can('brands.update'));

    const canManageTeam = computed(() => canAny('team.invite', 'team.manage', 'team.remove'));
    const canInviteTeam = computed(() => can('team.invite'));

    const canManageSettings = computed(() => canAny('settings.brand', 'settings.email-domain', 'settings.social'));

    return {
        permissions,
        can,
        canAny,
        canAll,
        // Posts
        canManagePosts,
        canCreatePosts,
        canUpdatePosts,
        canDeletePosts,
        canPublishPosts,
        // Social
        canManageSocial,
        canPublishSocial,
        // Media
        canManageMedia,
        canUploadMedia,
        canDeleteMedia,
        // Subscribers
        canManageSubscribers,
        canViewSubscribers,
        canExportSubscribers,
        canDeleteSubscribers,
        // Sprints
        canManageSprints,
        canCreateSprints,
        // Brand
        canManageBrand,
        canUpdateBrand,
        // Team
        canManageTeam,
        canInviteTeam,
        // Settings
        canManageSettings,
    };
}
