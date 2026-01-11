<?php

use App\Http\Controllers\AIAssistantController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ContentSprintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoopController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MediaFolderController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\Public\BlogController;
use App\Http\Controllers\Public\SubscribeController;
use App\Http\Controllers\Settings\AccountInvitationController;
use App\Http\Controllers\Settings\EmailDomainController;
use App\Http\Controllers\Settings\TeamSettingsController;
use App\Http\Controllers\SocialPostController;
use App\Http\Controllers\SocialSettingsController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\Webhooks\SesWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Webhook routes (CSRF excluded in bootstrap/app.php)
Route::post('/webhooks/ses', [SesWebhookController::class, 'handle'])
    ->name('webhooks.ses');

// Coming soon page (production only)
Route::get('/coming-soon', function () {
    return Inertia::render('ComingSoon');
})->name('coming-soon');

// Public landing page
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Public blog routes (/@username format)
Route::get('/@{brand:slug}', [BlogController::class, 'index'])->name('public.blog.index');
Route::get('/@{brand:slug}/{post:slug}', [BlogController::class, 'show'])->name('public.blog.show');

// Public subscription routes
Route::post('/@{brand:slug}/subscribe', [SubscribeController::class, 'store'])
    ->middleware('throttle:6,1') // Rate limit: 6 requests per minute
    ->name('public.subscribe');
Route::get('/@{brand:slug}/confirm/{token}', [SubscribeController::class, 'confirm'])->name('public.subscribe.confirm');
Route::get('/@{brand:slug}/unsubscribe/{token}', [SubscribeController::class, 'unsubscribe'])->name('public.subscribe.unsubscribe');

// Public invitation acceptance route
Route::get('/invitations/{token}', [AccountInvitationController::class, 'accept'])->name('invitations.accept');

// Public media routes (permanent URLs that redirect to signed S3 URLs)
Route::get('/m/{media}', [MediaController::class, 'view'])->name('media.view');
Route::get('/m/{media}/thumb', [MediaController::class, 'thumbnail'])->name('media.thumbnail');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Onboarding routes
    Route::post('/onboarding/step/{step}', [OnboardingController::class, 'markStepComplete'])->name('onboarding.step');
    Route::post('/onboarding/dismiss', [OnboardingController::class, 'dismiss'])->name('onboarding.dismiss');

    // Calendar route
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // Content Sprint routes
    Route::resource('content-sprints', ContentSprintController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('/content-sprints/{contentSprint}/accept', [ContentSprintController::class, 'accept'])->name('content-sprints.accept');
    Route::post('/content-sprints/{contentSprint}/retry', [ContentSprintController::class, 'retry'])->name('content-sprints.retry');

    // Brand routes
    Route::get('/brands/create', [BrandController::class, 'create'])->name('brands.create');
    Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
    Route::post('/brands/{brand}/switch', [BrandController::class, 'switch'])->name('brands.switch');
    Route::get('/settings/brand', [BrandController::class, 'edit'])->name('settings.brand');
    Route::put('/settings/brand/{brand}', [BrandController::class, 'update'])->name('settings.brand.update');

    // Social Settings routes (OAuth)
    Route::get('/settings/social', [SocialSettingsController::class, 'index'])->name('settings.social');
    Route::get('/settings/social/{platform}/redirect', [SocialSettingsController::class, 'redirect'])->name('settings.social.redirect');
    Route::get('/settings/social/{platform}/callback', [SocialSettingsController::class, 'callback'])->name('settings.social.callback');
    Route::get('/settings/social/{platform}/select', [SocialSettingsController::class, 'showAccountSelection'])->name('settings.social.select');
    Route::post('/settings/social/{platform}/select', [SocialSettingsController::class, 'storeAccountSelection'])->name('settings.social.select.store');
    Route::delete('/settings/social/{platform}', [SocialSettingsController::class, 'disconnect'])->name('settings.social.disconnect');

    // Email Domain Settings routes
    Route::get('/settings/email-domain', [EmailDomainController::class, 'index'])->name('settings.email-domain');
    Route::post('/settings/email-domain', [EmailDomainController::class, 'initiate'])->name('settings.email-domain.initiate');
    Route::post('/settings/email-domain/check', [EmailDomainController::class, 'checkStatus'])->name('settings.email-domain.check');
    Route::put('/settings/email-domain/from', [EmailDomainController::class, 'updateFrom'])->name('settings.email-domain.update-from');
    Route::delete('/settings/email-domain', [EmailDomainController::class, 'remove'])->name('settings.email-domain.remove');

    // Team Settings routes
    Route::get('/settings/team', [TeamSettingsController::class, 'index'])->name('settings.team');
    Route::post('/settings/team/invite', [AccountInvitationController::class, 'store'])->name('team.invite');
    Route::patch('/settings/team/{user}/role', [TeamSettingsController::class, 'updateRole'])->name('team.update-role');
    Route::delete('/settings/team/{user}', [TeamSettingsController::class, 'removeMember'])->name('team.remove');
    Route::delete('/settings/team/invitations/{invitation}', [AccountInvitationController::class, 'destroy'])->name('team.invitation.cancel');
    Route::post('/settings/team/invitations/{invitation}/resend', [AccountInvitationController::class, 'resend'])->name('team.invitation.resend');

    // Post routes
    Route::delete('/posts/bulk-delete', [PostController::class, 'bulkDestroy'])->name('posts.bulk-destroy');
    Route::resource('posts', PostController::class);
    Route::post('/posts/{post}/publish', [PostController::class, 'publish'])->name('posts.publish');

    // Media Library routes
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::get('/media/list', [MediaController::class, 'list'])->name('media.list');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::patch('/media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::post('/media/bulk-delete', [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
    Route::post('/media/move', [MediaController::class, 'move'])->name('media.move');

    // Media Folder routes
    Route::get('/media/folders', [MediaFolderController::class, 'index'])->name('media.folders.index');
    Route::post('/media/folders', [MediaFolderController::class, 'store'])->name('media.folders.store');
    Route::patch('/media/folders/{folder}', [MediaFolderController::class, 'update'])->name('media.folders.update');
    Route::delete('/media/folders/{folder}', [MediaFolderController::class, 'destroy'])->name('media.folders.destroy');

    // Subscriber routes
    Route::get('/subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');
    Route::patch('/subscribers/{subscriber}', [SubscriberController::class, 'update'])->name('subscribers.update');
    Route::delete('/subscribers/{subscriber}', [SubscriberController::class, 'destroy'])->name('subscribers.destroy');
    Route::get('/subscribers/export', [SubscriberController::class, 'export'])->name('subscribers.export');
    Route::post('/subscribers/import', [SubscriberController::class, 'import'])->name('subscribers.import');

    // Newsletter routes
    Route::get('/newsletters', [NewsletterController::class, 'index'])->name('newsletters.index');
    Route::get('/newsletters/{newsletterSend}', [NewsletterController::class, 'show'])->name('newsletters.show');

    // Social Post routes
    Route::get('/social-posts', [SocialPostController::class, 'index'])->name('social-posts.index');
    Route::get('/social-posts/queue', [SocialPostController::class, 'queue'])->name('social-posts.queue');
    Route::post('/social-posts', [SocialPostController::class, 'store'])->name('social-posts.store');
    Route::put('/social-posts/{socialPost}', [SocialPostController::class, 'update'])->name('social-posts.update');
    Route::delete('/social-posts/{socialPost}', [SocialPostController::class, 'destroy'])->name('social-posts.destroy');
    Route::post('/social-posts/{socialPost}/queue', [SocialPostController::class, 'queuePost'])->name('social-posts.queue-post');
    Route::post('/social-posts/{socialPost}/schedule', [SocialPostController::class, 'schedule'])->name('social-posts.schedule');
    Route::post('/social-posts/{socialPost}/publish', [SocialPostController::class, 'publish'])->name('social-posts.publish');
    Route::post('/social-posts/{socialPost}/publish-now', [SocialPostController::class, 'publishNow'])->name('social-posts.publish-now');
    Route::post('/social-posts/{socialPost}/retry', [SocialPostController::class, 'retry'])->name('social-posts.retry');
    Route::post('/social-posts/bulk-schedule', [SocialPostController::class, 'bulkSchedule'])->name('social-posts.bulk-schedule');
    Route::post('/social-posts/bulk-publish-now', [SocialPostController::class, 'bulkPublishNow'])->name('social-posts.bulk-publish-now');

    // Loop routes
    Route::get('/loops', [LoopController::class, 'index'])->name('loops.index');
    Route::get('/loops/create', [LoopController::class, 'create'])->name('loops.create');
    Route::post('/loops', [LoopController::class, 'store'])->name('loops.store');
    Route::get('/loops/{loop}', [LoopController::class, 'show'])->name('loops.show');
    Route::get('/loops/{loop}/edit', [LoopController::class, 'edit'])->name('loops.edit');
    Route::put('/loops/{loop}', [LoopController::class, 'update'])->name('loops.update');
    Route::delete('/loops/{loop}', [LoopController::class, 'destroy'])->name('loops.destroy');
    Route::post('/loops/{loop}/toggle', [LoopController::class, 'toggle'])->name('loops.toggle');
    Route::post('/loops/{loop}/items', [LoopController::class, 'addItem'])->name('loops.items.store');
    Route::put('/loops/{loop}/items/{item}', [LoopController::class, 'updateItem'])->name('loops.items.update');
    Route::delete('/loops/{loop}/items/{item}', [LoopController::class, 'removeItem'])->name('loops.items.destroy');
    Route::post('/loops/{loop}/reorder', [LoopController::class, 'reorder'])->name('loops.reorder');
    Route::post('/loops/{loop}/import', [LoopController::class, 'import'])->name('loops.import');

    // AI Assistant routes
    Route::prefix('ai')->group(function () {
        Route::post('/draft', [AIAssistantController::class, 'draft'])->name('ai.draft');
        Route::post('/polish', [AIAssistantController::class, 'polish'])->name('ai.polish');
        Route::post('/continue', [AIAssistantController::class, 'continue'])->name('ai.continue');
        Route::post('/outline', [AIAssistantController::class, 'outline'])->name('ai.outline');
        Route::post('/tone', [AIAssistantController::class, 'changeTone'])->name('ai.tone');
        Route::post('/shorter', [AIAssistantController::class, 'shorter'])->name('ai.shorter');
        Route::post('/longer', [AIAssistantController::class, 'longer'])->name('ai.longer');
        Route::post('/ask', [AIAssistantController::class, 'ask'])->name('ai.ask');
        Route::post('/atomize', [AIAssistantController::class, 'atomize'])->name('ai.atomize');
    });

    // Logout route
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
