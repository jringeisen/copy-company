<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Brand management
            'brands.create',
            'brands.update',
            'brands.delete',

            // Content
            'posts.create',
            'posts.update',
            'posts.delete',
            'posts.publish',

            // Subscribers
            'subscribers.view',
            'subscribers.export',
            'subscribers.delete',

            // Social
            'social.manage',
            'social.publish',

            // Media
            'media.upload',
            'media.delete',

            // Content Sprints
            'sprints.create',
            'sprints.manage',

            // Settings
            'settings.brand',
            'settings.email-domain',
            'settings.social',

            // Team management (admin only)
            'team.invite',
            'team.manage',
            'team.remove',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles with permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $memberRole = Role::create(['name' => 'member']);
        $memberRole->givePermissionTo([
            'brands.update',
            'posts.create',
            'posts.update',
            'posts.delete',
            'posts.publish',
            'subscribers.view',
            'social.manage',
            'social.publish',
            'media.upload',
            'media.delete',
            'sprints.create',
            'sprints.manage',
            'settings.brand',
            'settings.social',
        ]);

        $viewerRole = Role::create(['name' => 'viewer']);
        $viewerRole->givePermissionTo([
            'subscribers.view',
        ]);
    }
}
