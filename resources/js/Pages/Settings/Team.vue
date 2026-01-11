<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    account: Object,
    members: Array,
    pendingInvitations: Array,
    isAdmin: Boolean,
    roles: Array,
});

const showInviteModal = ref(false);

const inviteForm = useForm({
    email: '',
    role: 'member',
});

const submitInvite = () => {
    inviteForm.post('/settings/team/invite', {
        preserveScroll: true,
        onSuccess: () => {
            showInviteModal.value = false;
            inviteForm.reset();
        },
    });
};

const updateRole = (userId, newRole) => {
    router.patch(`/settings/team/${userId}/role`, {
        role: newRole,
    }, {
        preserveScroll: true,
    });
};

const removeMember = (member) => {
    if (!confirm(`Are you sure you want to remove ${member.name} from this account?`)) {
        return;
    }
    router.delete(`/settings/team/${member.id}`, {
        preserveScroll: true,
    });
};

const cancelInvitation = (invitation) => {
    if (!confirm(`Are you sure you want to cancel the invitation for ${invitation.email}?`)) {
        return;
    }
    router.delete(`/settings/team/invitations/${invitation.id}`, {
        preserveScroll: true,
    });
};

const resendInvitation = (invitation) => {
    router.post(`/settings/team/invitations/${invitation.id}/resend`, {}, {
        preserveScroll: true,
    });
};

const getRoleBadgeClass = (role) => {
    switch (role) {
        case 'admin':
            return 'bg-[#a1854f]/20 text-[#a1854f]';
        case 'member':
            return 'bg-[#0b1215]/10 text-[#0b1215]/80';
        case 'viewer':
            return 'bg-[#0b1215]/5 text-[#0b1215]/60';
        default:
            return 'bg-[#0b1215]/5 text-[#0b1215]/60';
    }
};
</script>

<template>
    <Head title="Team Settings" />

    <AppLayout current-page="team">
        <div class="max-w-3xl mx-auto">
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-[#0b1215]">Team Settings</h1>
                    <p class="text-[#0b1215]/60 mt-1">Manage who has access to {{ account.name }}</p>
                </div>
                <button
                    v-if="isAdmin"
                    @click="showInviteModal = true"
                    class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 focus:outline-none focus:ring-2 focus:ring-[#0b1215]/20 focus:ring-offset-2 transition"
                >
                    Invite Member
                </button>
            </div>

            <!-- Team Members -->
            <div class="bg-white rounded-2xl border border-[#0b1215]/10">
                <div class="px-6 py-4 border-b border-[#0b1215]/10">
                    <h2 class="text-lg font-semibold text-[#0b1215]">Team Members</h2>
                </div>
                <ul class="divide-y divide-[#0b1215]/10">
                    <li
                        v-for="member in members"
                        :key="member.id"
                        class="px-6 py-4 flex items-center justify-between"
                    >
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-[#a1854f]/10 rounded-full flex items-center justify-center">
                                <span class="text-[#a1854f] font-medium">{{ member.name.charAt(0).toUpperCase() }}</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-[#0b1215]">
                                    {{ member.name }}
                                    <span v-if="member.is_current_user" class="text-[#0b1215]/50">(You)</span>
                                </p>
                                <p class="text-sm text-[#0b1215]/50">{{ member.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <select
                                v-if="isAdmin && !member.is_current_user"
                                :value="member.role"
                                @change="updateRole(member.id, $event.target.value)"
                                class="text-sm border-[#0b1215]/20 rounded-xl focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                            >
                                <option v-for="role in roles" :key="role.value" :value="role.value">
                                    {{ role.label }}
                                </option>
                            </select>
                            <span
                                v-else
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                                :class="getRoleBadgeClass(member.role)"
                            >
                                {{ member.role }}
                            </span>
                            <button
                                v-if="isAdmin && !member.is_current_user"
                                @click="removeMember(member)"
                                class="p-1 text-[#0b1215]/40 hover:text-red-500 transition"
                                title="Remove member"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Pending Invitations -->
            <div v-if="pendingInvitations.length > 0" class="mt-8 bg-white rounded-2xl border border-[#0b1215]/10">
                <div class="px-6 py-4 border-b border-[#0b1215]/10">
                    <h2 class="text-lg font-semibold text-[#0b1215]">Pending Invitations</h2>
                </div>
                <ul class="divide-y divide-[#0b1215]/10">
                    <li
                        v-for="invitation in pendingInvitations"
                        :key="invitation.id"
                        class="px-6 py-4 flex items-center justify-between"
                    >
                        <div>
                            <p class="text-sm font-medium text-[#0b1215]">{{ invitation.email }}</p>
                            <p class="text-sm text-[#0b1215]/50">
                                Invited as {{ invitation.role }} by {{ invitation.invited_by }}
                            </p>
                            <p class="text-xs text-[#0b1215]/40 mt-1">
                                Expires {{ new Date(invitation.expires_at).toLocaleDateString() }}
                            </p>
                        </div>
                        <div v-if="isAdmin" class="flex items-center gap-2">
                            <button
                                @click="resendInvitation(invitation)"
                                class="px-3 py-1.5 text-sm font-medium text-[#a1854f] bg-[#a1854f]/10 rounded-xl hover:bg-[#a1854f]/20 transition"
                            >
                                Resend
                            </button>
                            <button
                                @click="cancelInvitation(invitation)"
                                class="px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 rounded-xl hover:bg-red-100 transition"
                            >
                                Cancel
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Empty state for no pending invitations -->
            <div v-else-if="isAdmin" class="mt-8 bg-white rounded-2xl border border-[#0b1215]/10 p-6">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-[#0b1215]/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-[#0b1215]">No pending invitations</h3>
                    <p class="mt-1 text-sm text-[#0b1215]/50">Invite team members to collaborate on your brands.</p>
                </div>
            </div>
        </div>

        <!-- Invite Modal -->
        <Teleport to="body">
        <div v-if="showInviteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showInviteModal = false"></div>

                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-[#0b1215] mb-4">Invite Team Member</h3>

                    <form @submit.prevent="submitInvite" class="space-y-4">
                        <div>
                            <label for="invite_email" class="block text-sm font-medium text-[#0b1215]">Email Address</label>
                            <input
                                id="invite_email"
                                v-model="inviteForm.email"
                                type="email"
                                class="mt-1 block w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                                placeholder="colleague@example.com"
                                required
                            />
                            <p v-if="inviteForm.errors.email" class="mt-1 text-sm text-red-600">{{ inviteForm.errors.email }}</p>
                        </div>

                        <div>
                            <label for="invite_role" class="block text-sm font-medium text-[#0b1215]">Role</label>
                            <select
                                id="invite_role"
                                v-model="inviteForm.role"
                                class="mt-1 block w-full px-4 py-3 border border-[#0b1215]/20 rounded-xl focus:ring-[#0b1215]/20 focus:border-[#0b1215]/40"
                            >
                                <option v-for="role in roles" :key="role.value" :value="role.value">
                                    {{ role.label }}
                                </option>
                            </select>
                            <p v-if="inviteForm.errors.role" class="mt-1 text-sm text-red-600">{{ inviteForm.errors.role }}</p>
                        </div>

                        <div class="flex justify-end gap-3 mt-6">
                            <button
                                type="button"
                                @click="showInviteModal = false"
                                class="px-4 py-2 text-sm font-medium text-[#0b1215] bg-white border border-[#0b1215]/20 rounded-xl hover:bg-[#0b1215]/5 transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                class="px-4 py-2 bg-[#0b1215] text-white font-medium rounded-full hover:bg-[#0b1215]/90 focus:outline-none focus:ring-2 focus:ring-[#0b1215]/20 focus:ring-offset-2 transition"
                                :class="{ 'opacity-50': inviteForm.processing }"
                                :disabled="inviteForm.processing"
                            >
                                <span v-if="inviteForm.processing">Sending...</span>
                                <span v-else>Send Invitation</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
    </AppLayout>
</template>
