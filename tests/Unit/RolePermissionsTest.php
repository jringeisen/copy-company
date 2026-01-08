<?php

use App\Models\Account;
use App\Models\Brand;
use App\Models\ContentSprint;
use App\Models\Media;
use App\Models\Post;
use App\Models\SocialPost;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create all permissions
    $permissions = [
        'brands.create', 'brands.update', 'brands.delete',
        'posts.create', 'posts.update', 'posts.delete', 'posts.publish',
        'subscribers.view', 'subscribers.export', 'subscribers.delete',
        'social.manage', 'social.publish',
        'media.upload', 'media.delete',
        'sprints.create', 'sprints.manage',
        'settings.brand', 'settings.email-domain', 'settings.social',
        'team.invite', 'team.manage', 'team.remove',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    // Create admin role with all permissions
    $adminRole = Role::findOrCreate('admin', 'web');
    $adminRole->syncPermissions(Permission::all());

    // Create member role with specific permissions
    $memberRole = Role::findOrCreate('member', 'web');
    $memberRole->syncPermissions([
        'brands.update',
        'posts.create', 'posts.update', 'posts.delete', 'posts.publish',
        'subscribers.view',
        'social.manage', 'social.publish',
        'media.upload', 'media.delete',
        'sprints.create', 'sprints.manage',
        'settings.brand', 'settings.social',
    ]);

    // Create viewer role with minimal permissions
    $viewerRole = Role::findOrCreate('viewer', 'web');
    $viewerRole->syncPermissions(['subscribers.view']);
});

/**
 * Helper to create a user with a role in an account
 */
function createUserWithRole(string $role): array
{
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $account->users()->attach($user->id, ['role' => $role]);
    $brand = Brand::factory()->forAccount($account)->create();

    session(['current_account_id' => $account->id]);
    setPermissionsTeamId($account->id);
    $user->assignRole($role);

    return [$user, $account, $brand];
}

// ============================================
// ADMIN ROLE TESTS
// ============================================

describe('Admin Role', function () {
    test('admin has all permissions', function () {
        [$admin] = createUserWithRole('admin');

        $allPermissions = [
            'brands.create', 'brands.update', 'brands.delete',
            'posts.create', 'posts.update', 'posts.delete', 'posts.publish',
            'subscribers.view', 'subscribers.export', 'subscribers.delete',
            'social.manage', 'social.publish',
            'media.upload', 'media.delete',
            'sprints.create', 'sprints.manage',
            'settings.brand', 'settings.email-domain', 'settings.social',
            'team.invite', 'team.manage', 'team.remove',
        ];

        foreach ($allPermissions as $permission) {
            expect($admin->can($permission))->toBeTrue("Admin should have permission: {$permission}");
        }
    });

    test('admin can create posts', function () {
        [$admin, $account, $brand] = createUserWithRole('admin');

        expect($admin->can('create', Post::class))->toBeTrue();
    });

    test('admin can update posts', function () {
        [$admin, $account, $brand] = createUserWithRole('admin');
        $post = Post::factory()->forBrand($brand)->forUser($admin)->create();

        expect($admin->can('update', $post))->toBeTrue();
    });

    test('admin can delete posts', function () {
        [$admin, $account, $brand] = createUserWithRole('admin');
        $post = Post::factory()->forBrand($brand)->forUser($admin)->create();

        expect($admin->can('delete', $post))->toBeTrue();
    });

    test('admin can manage subscribers', function () {
        [$admin, $account, $brand] = createUserWithRole('admin');
        $subscriber = Subscriber::factory()->forBrand($brand)->create();

        expect($admin->can('view', $subscriber))->toBeTrue();
        expect($admin->can('delete', $subscriber))->toBeTrue();
        expect($admin->can('subscribers.export'))->toBeTrue();
    });

    test('admin can manage social posts', function () {
        [$admin, $account, $brand] = createUserWithRole('admin');
        $socialPost = SocialPost::factory()->forBrand($brand)->create();

        expect($admin->can('create', SocialPost::class))->toBeTrue();
        expect($admin->can('update', $socialPost))->toBeTrue();
        expect($admin->can('delete', $socialPost))->toBeTrue();
    });

    test('admin can manage media', function () {
        [$admin, $account, $brand] = createUserWithRole('admin');

        expect($admin->can('create', Media::class))->toBeTrue();
        expect($admin->can('media.delete'))->toBeTrue();
    });

    test('admin can manage content sprints', function () {
        [$admin, $account, $brand] = createUserWithRole('admin');

        expect($admin->can('create', ContentSprint::class))->toBeTrue();
        expect($admin->can('sprints.manage'))->toBeTrue();
    });

    test('admin can manage team', function () {
        [$admin] = createUserWithRole('admin');

        expect($admin->can('team.invite'))->toBeTrue();
        expect($admin->can('team.manage'))->toBeTrue();
        expect($admin->can('team.remove'))->toBeTrue();
    });

    test('admin can access all settings', function () {
        [$admin] = createUserWithRole('admin');

        expect($admin->can('settings.brand'))->toBeTrue();
        expect($admin->can('settings.email-domain'))->toBeTrue();
        expect($admin->can('settings.social'))->toBeTrue();
    });
});

// ============================================
// MEMBER ROLE TESTS
// ============================================

describe('Member Role', function () {
    test('member has content creation permissions', function () {
        [$member] = createUserWithRole('member');

        $memberPermissions = [
            'brands.update',
            'posts.create', 'posts.update', 'posts.delete', 'posts.publish',
            'subscribers.view',
            'social.manage', 'social.publish',
            'media.upload', 'media.delete',
            'sprints.create', 'sprints.manage',
            'settings.brand', 'settings.social',
        ];

        foreach ($memberPermissions as $permission) {
            expect($member->can($permission))->toBeTrue("Member should have permission: {$permission}");
        }
    });

    test('member cannot create or delete brands', function () {
        [$member] = createUserWithRole('member');

        expect($member->can('brands.create'))->toBeFalse();
        expect($member->can('brands.delete'))->toBeFalse();
    });

    test('member cannot export or delete subscribers', function () {
        [$member] = createUserWithRole('member');

        expect($member->can('subscribers.export'))->toBeFalse();
        expect($member->can('subscribers.delete'))->toBeFalse();
    });

    test('member cannot manage team', function () {
        [$member] = createUserWithRole('member');

        expect($member->can('team.invite'))->toBeFalse();
        expect($member->can('team.manage'))->toBeFalse();
        expect($member->can('team.remove'))->toBeFalse();
    });

    test('member cannot access email domain settings', function () {
        [$member] = createUserWithRole('member');

        expect($member->can('settings.email-domain'))->toBeFalse();
    });

    test('member can create posts', function () {
        [$member, $account, $brand] = createUserWithRole('member');

        expect($member->can('create', Post::class))->toBeTrue();
    });

    test('member can update posts', function () {
        [$member, $account, $brand] = createUserWithRole('member');
        $post = Post::factory()->forBrand($brand)->forUser($member)->create();

        expect($member->can('update', $post))->toBeTrue();
    });

    test('member can delete posts', function () {
        [$member, $account, $brand] = createUserWithRole('member');
        $post = Post::factory()->forBrand($brand)->forUser($member)->create();

        expect($member->can('delete', $post))->toBeTrue();
    });

    test('member can view subscribers but not delete', function () {
        [$member, $account, $brand] = createUserWithRole('member');
        $subscriber = Subscriber::factory()->forBrand($brand)->create();

        expect($member->can('view', $subscriber))->toBeTrue();
        expect($member->can('delete', $subscriber))->toBeFalse();
    });

    test('member can create social posts', function () {
        [$member, $account, $brand] = createUserWithRole('member');

        expect($member->can('create', SocialPost::class))->toBeTrue();
    });

    test('member can upload media', function () {
        [$member, $account, $brand] = createUserWithRole('member');

        expect($member->can('create', Media::class))->toBeTrue();
    });

    test('member can create content sprints', function () {
        [$member, $account, $brand] = createUserWithRole('member');

        expect($member->can('create', ContentSprint::class))->toBeTrue();
    });
});

// ============================================
// VIEWER ROLE TESTS
// ============================================

describe('Viewer Role', function () {
    test('viewer has only view permissions', function () {
        [$viewer] = createUserWithRole('viewer');

        expect($viewer->can('subscribers.view'))->toBeTrue();
    });

    test('viewer cannot create anything', function () {
        [$viewer] = createUserWithRole('viewer');

        expect($viewer->can('posts.create'))->toBeFalse();
        expect($viewer->can('social.manage'))->toBeFalse();
        expect($viewer->can('media.upload'))->toBeFalse();
        expect($viewer->can('sprints.create'))->toBeFalse();
        expect($viewer->can('brands.create'))->toBeFalse();
    });

    test('viewer cannot update anything', function () {
        [$viewer] = createUserWithRole('viewer');

        expect($viewer->can('posts.update'))->toBeFalse();
        expect($viewer->can('brands.update'))->toBeFalse();
    });

    test('viewer cannot delete anything', function () {
        [$viewer] = createUserWithRole('viewer');

        expect($viewer->can('posts.delete'))->toBeFalse();
        expect($viewer->can('subscribers.delete'))->toBeFalse();
        expect($viewer->can('media.delete'))->toBeFalse();
        expect($viewer->can('brands.delete'))->toBeFalse();
    });

    test('viewer cannot publish anything', function () {
        [$viewer] = createUserWithRole('viewer');

        expect($viewer->can('posts.publish'))->toBeFalse();
        expect($viewer->can('social.publish'))->toBeFalse();
    });

    test('viewer cannot manage team', function () {
        [$viewer] = createUserWithRole('viewer');

        expect($viewer->can('team.invite'))->toBeFalse();
        expect($viewer->can('team.manage'))->toBeFalse();
        expect($viewer->can('team.remove'))->toBeFalse();
    });

    test('viewer cannot access settings', function () {
        [$viewer] = createUserWithRole('viewer');

        expect($viewer->can('settings.brand'))->toBeFalse();
        expect($viewer->can('settings.email-domain'))->toBeFalse();
        expect($viewer->can('settings.social'))->toBeFalse();
    });

    test('viewer cannot export subscribers', function () {
        [$viewer] = createUserWithRole('viewer');

        expect($viewer->can('subscribers.export'))->toBeFalse();
    });

    test('viewer cannot create posts via policy', function () {
        [$viewer, $account, $brand] = createUserWithRole('viewer');

        expect($viewer->can('create', Post::class))->toBeFalse();
    });

    test('viewer cannot update posts via policy', function () {
        // Create an admin to own the post
        $admin = User::factory()->create();
        [$viewer, $account, $brand] = createUserWithRole('viewer');
        $account->users()->attach($admin->id, ['role' => 'admin']);

        $post = Post::factory()->forBrand($brand)->forUser($admin)->create();

        expect($viewer->can('update', $post))->toBeFalse();
    });

    test('viewer cannot delete posts via policy', function () {
        $admin = User::factory()->create();
        [$viewer, $account, $brand] = createUserWithRole('viewer');
        $account->users()->attach($admin->id, ['role' => 'admin']);

        $post = Post::factory()->forBrand($brand)->forUser($admin)->create();

        expect($viewer->can('delete', $post))->toBeFalse();
    });

    test('viewer can view subscribers via policy', function () {
        [$viewer, $account, $brand] = createUserWithRole('viewer');
        $subscriber = Subscriber::factory()->forBrand($brand)->create();

        expect($viewer->can('view', $subscriber))->toBeTrue();
    });

    test('viewer cannot delete subscribers via policy', function () {
        [$viewer, $account, $brand] = createUserWithRole('viewer');
        $subscriber = Subscriber::factory()->forBrand($brand)->create();

        expect($viewer->can('delete', $subscriber))->toBeFalse();
    });

    test('viewer cannot create social posts via policy', function () {
        [$viewer, $account, $brand] = createUserWithRole('viewer');

        expect($viewer->can('create', SocialPost::class))->toBeFalse();
    });

    test('viewer cannot upload media via policy', function () {
        [$viewer, $account, $brand] = createUserWithRole('viewer');

        expect($viewer->can('create', Media::class))->toBeFalse();
    });

    test('viewer cannot create content sprints via policy', function () {
        [$viewer, $account, $brand] = createUserWithRole('viewer');

        expect($viewer->can('create', ContentSprint::class))->toBeFalse();
    });
});

// ============================================
// CROSS-ACCOUNT ISOLATION TESTS
// ============================================

describe('Cross-Account Isolation', function () {
    test('admin cannot access posts from other accounts', function () {
        [$admin, $account, $brand] = createUserWithRole('admin');

        // Create another account with a post
        $otherAccount = Account::factory()->create();
        $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
        $otherPost = Post::factory()->forBrand($otherBrand)->create();

        expect($admin->can('view', $otherPost))->toBeFalse();
        expect($admin->can('update', $otherPost))->toBeFalse();
        expect($admin->can('delete', $otherPost))->toBeFalse();
    });

    test('member cannot access posts from other accounts', function () {
        [$member, $account, $brand] = createUserWithRole('member');

        $otherAccount = Account::factory()->create();
        $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
        $otherPost = Post::factory()->forBrand($otherBrand)->create();

        expect($member->can('view', $otherPost))->toBeFalse();
        expect($member->can('update', $otherPost))->toBeFalse();
        expect($member->can('delete', $otherPost))->toBeFalse();
    });

    test('admin cannot access subscribers from other accounts', function () {
        [$admin, $account, $brand] = createUserWithRole('admin');

        $otherAccount = Account::factory()->create();
        $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
        $otherSubscriber = Subscriber::factory()->forBrand($otherBrand)->create();

        expect($admin->can('view', $otherSubscriber))->toBeFalse();
        expect($admin->can('delete', $otherSubscriber))->toBeFalse();
    });

    test('admin cannot access social posts from other accounts', function () {
        [$admin, $account, $brand] = createUserWithRole('admin');

        $otherAccount = Account::factory()->create();
        $otherBrand = Brand::factory()->forAccount($otherAccount)->create();
        $otherSocialPost = SocialPost::factory()->forBrand($otherBrand)->create();

        expect($admin->can('view', $otherSocialPost))->toBeFalse();
        expect($admin->can('update', $otherSocialPost))->toBeFalse();
        expect($admin->can('delete', $otherSocialPost))->toBeFalse();
    });
});
