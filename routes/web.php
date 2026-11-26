<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminProfilePhotoController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\VibeController;
use App\Http\Controllers\Api\WfcScoreController;
use App\Http\Controllers\CafeController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerFeedbackController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\AiPromptPreviewController;
use App\Http\Controllers\GeminiChatImageController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\MerchantApplicationController;
use App\Http\Controllers\MerchantRegistrationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OwnerOrderController;
use App\Http\Controllers\RoasteryController;
use App\Http\Controllers\SavedController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\TongkronganController;
use App\Http\Controllers\WhatsAppAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes with rate limiting
Route::middleware(['throttle:web', 'customer.site'])->group(function () {
    Route::get('/', [CafeController::class, 'index'])->name('home');
    Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
    Route::get('/saved', [SavedController::class, 'index'])->name('saved');
    Route::get('/information', [InformationController::class, 'index'])->name('information');
    Route::get('/information/{information:slug}', [InformationController::class, 'show'])->name('information.show');
    Route::get('/profile', fn () => auth()->check() ? redirect()->route('account') : redirect()->route('login'))->name('profile');
    Route::get('/auth/google/customer/redirect', [SocialAuthController::class, 'redirect'])
        ->defaults('audience', 'customer')
        ->middleware('guest:customer')
        ->middleware('throttle:10,1')
        ->name('auth.google.customer');
    Route::get('/auth/google/callback', [SocialAuthController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('auth.google.callback');
    Route::post('/login/whatsapp/request', [WhatsAppAuthController::class, 'requestLoginCode'])
        ->defaults('audience', 'customer')
        ->middleware('guest:customer')
        ->middleware('throttle:5,1')
        ->name('auth.whatsapp.login.request');
    Route::post('/login/whatsapp/verify', [WhatsAppAuthController::class, 'verifyLoginCode'])
        ->middleware('guest:customer')
        ->middleware('throttle:10,1')
        ->name('auth.whatsapp.login.verify');
    Route::get('/admin/auth/google/redirect', [SocialAuthController::class, 'redirect'])
        ->defaults('audience', 'admin')
        ->middleware('guest:web')
        ->middleware('throttle:10,1')
        ->name('owner.auth.google.redirect');
    Route::post('/admin/login/whatsapp/request', [WhatsAppAuthController::class, 'requestLoginCode'])
        ->defaults('audience', 'admin')
        ->middleware('guest:web')
        ->middleware('throttle:5,1')
        ->name('owner.auth.whatsapp.login.request');
    Route::post('/admin/login/whatsapp/verify', [WhatsAppAuthController::class, 'verifyLoginCode'])
        ->middleware('guest:web')
        ->middleware('throttle:10,1')
        ->name('owner.auth.whatsapp.login.verify');
    Route::get('/admin/register', [MerchantRegistrationController::class, 'create'])
        ->middleware(['guest:web', 'guest:customer'])
        ->name('merchant-registration.create');
    Route::post('/admin/register', [MerchantRegistrationController::class, 'store'])
        ->middleware(['guest:web', 'guest:customer', 'throttle:5,1'])
        ->name('merchant-registration.store');
    Route::middleware('guest')->group(function () {
        Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
        Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    });

    Route::get('/roastery', [RoasteryController::class, 'index'])->name('roastery');
    Route::get('/roastery/{roastery}', [RoasteryController::class, 'show'])->name('roastery.show');
    Route::get('/cafes/{cafe}', [CafeController::class, 'show'])->name('cafes.show');
    Route::redirect('/cafes', '/explore'); // Ensure no 404 for list request

    // Customers must sign in before checkout. Ordering and stock remain scoped to one seller.
    Route::get('/cart', [OrderController::class, 'cart'])->name('cart.show');
    Route::post('/cafes/{cafe}/menu/{menuItem}/cart', [OrderController::class, 'addItem'])->middleware('auth')->name('cart.items.add');
    Route::post('/roasteries/{roastery}/menu/{menuItem}/cart', [OrderController::class, 'addRoasteryItem'])->middleware('auth')->name('roastery.cart.items.add');
    Route::patch('/cart/items/{menuItem}', [OrderController::class, 'updateItem'])->middleware('auth')->name('cart.items.update');
    Route::patch('/cart/items/{menuItem}/addons/{addonMenuItem}', [OrderController::class, 'updateAddon'])->middleware('auth')->name('cart.addons.update');
    Route::delete('/cart', [OrderController::class, 'clearCart'])->middleware('auth')->name('cart.clear');
    Route::middleware('auth')->group(function () {
        Route::get('/account', AccountController::class)->name('account');
        Route::get('/account/profile', [CustomerProfileController::class, 'edit'])->name('account.profile.edit');
        Route::patch('/account/profile', [CustomerProfileController::class, 'update'])->name('account.profile.update');
        Route::get('/account/security/whatsapp', [WhatsAppAuthController::class, 'showPhoneLink'])
            ->defaults('audience', 'customer')
            ->name('account.whatsapp.edit');
        Route::post('/account/security/whatsapp/request', [WhatsAppAuthController::class, 'requestPhoneLinkCode'])
            ->defaults('audience', 'customer')
            ->middleware('throttle:5,1')
            ->name('account.whatsapp.request');
        Route::post('/account/security/whatsapp/verify', [WhatsAppAuthController::class, 'verifyPhoneLinkCode'])
            ->defaults('audience', 'customer')
            ->middleware('throttle:10,1')
            ->name('account.whatsapp.verify');
        Route::get('/account/feedback', [CustomerFeedbackController::class, 'indexForCustomer'])->name('account.feedback.index');
        Route::get('/account/merchant-application', [MerchantApplicationController::class, 'create'])->name('merchant-applications.create');
        Route::post('/account/merchant-application', [MerchantApplicationController::class, 'store'])->middleware('throttle:5,1')->name('merchant-applications.store');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notificationId}/open', [NotificationController::class, 'open'])
            ->name('notifications.open');
        Route::get('/checkout/{cafe}', [OrderController::class, 'checkout'])->name('checkout.show');
        Route::post('/checkout/{cafe}', [OrderController::class, 'placeOrder'])->name('checkout.store');
        Route::get('/checkout/roastery/{roastery}', [OrderController::class, 'checkoutRoastery'])->name('roastery.checkout.show');
        Route::post('/checkout/roastery/{roastery}', [OrderController::class, 'placeRoasteryOrder'])->name('roastery.checkout.store');
        Route::get('/orders/history', [OrderController::class, 'history'])->name('orders.history');
        Route::post('/orders/{code}/cancel', [OrderController::class, 'cancel'])
            ->middleware('throttle:5,1')
            ->name('orders.cancel');
        Route::get('/orders/{code}/feedback', [CustomerFeedbackController::class, 'createForOrder'])->name('orders.feedback.create');
        Route::post('/orders/{code}/feedback', [CustomerFeedbackController::class, 'storeForOrder'])
            ->middleware('throttle:5,1')
            ->name('orders.feedback.store');
    });

    Route::get('/orders/{code}/status', [OrderController::class, 'status'])->middleware('throttle:60,1')->name('orders.status');
    Route::post('/orders/{code}/payment', [OrderController::class, 'submitPayment'])->middleware('throttle:10,1')->name('orders.payment.submit');
    Route::post('/orders/{code}/confirm-received', [OrderController::class, 'confirmReceived'])
        ->middleware(['auth:customer', 'throttle:10,1'])
        ->name('orders.confirm-received');
    Route::get('/orders/{code}/receipt', [OrderController::class, 'receipt'])->middleware('throttle:30,1')->name('orders.receipt');
    Route::get('/orders/{code}', [OrderController::class, 'show'])->middleware('throttle:30,1')->name('orders.show');

    // Kedai/order operations are additionally scoped to the authenticated cafe owner.
    Route::prefix('owner')->name('owner.')->middleware('auth:web')->group(function () {
        Route::get('/dashboard', [OwnerOrderController::class, 'dashboard'])->name('dashboard');
        Route::get('/orders', [OwnerOrderController::class, 'index'])->name('orders.index');
        Route::post('/pickup', [OwnerOrderController::class, 'pickup'])->middleware('throttle:20,1')->name('orders.pickup');
        Route::get('/orders/{code}', [OwnerOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{code}/confirm-payment', [OwnerOrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
        Route::post('/orders/{code}/confirm-cash-payment', [OwnerOrderController::class, 'confirmCashPayment'])->name('orders.confirm-cash-payment');
        Route::post('/orders/{code}/reject-payment', [OwnerOrderController::class, 'rejectPayment'])->name('orders.reject-payment');
        Route::post('/orders/{code}/delivery-fee', [OwnerOrderController::class, 'setDeliveryFee'])->name('orders.delivery-fee');
        Route::post('/orders/{code}/status', [OwnerOrderController::class, 'status'])->name('orders.status');
        Route::post('/orders/{code}/cancel', [OwnerOrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('/orders/{code}/payments/{paymentId}/proof', [OwnerOrderController::class, 'proof'])->name('orders.payment-proof');
        Route::get('/orders/{code}/print', [OwnerOrderController::class, 'printReceipt'])->name('orders.print');
        Route::post('/orders/{code}/print/reprint', [OwnerOrderController::class, 'reprint'])->name('orders.reprint');
        Route::post('/print-jobs/{receiptPrintJob}/status', [OwnerOrderController::class, 'updatePrintJob'])->name('print-jobs.status');
    });

    Route::prefix('admin/security/whatsapp')->name('owner.account.whatsapp.')->middleware('auth:web')->group(function (): void {
        Route::get('/', [WhatsAppAuthController::class, 'showPhoneLink'])->defaults('audience', 'admin')->name('edit');
        Route::post('/request', [WhatsAppAuthController::class, 'requestPhoneLinkCode'])
            ->defaults('audience', 'admin')
            ->middleware('throttle:5,1')
            ->name('request');
        Route::post('/verify', [WhatsAppAuthController::class, 'verifyPhoneLinkCode'])
            ->defaults('audience', 'admin')
            ->middleware('throttle:10,1')
            ->name('verify');
    });

    Route::get('/feedback', [CustomerFeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback', [CustomerFeedbackController::class, 'store'])->middleware('throttle:5,1')->name('feedback.store');

    // WFC Scoring (Protected)
    Route::post('/cafes/{cafe:id}/wfc-score', [WfcScoreController::class, 'store'])
        ->middleware('auth')
        ->name('cafes.wfc-score');

    // Vibe Meter API (Public read, rate-limited write)
    Route::get('/cafes/{cafe:id}/vibe', [VibeController::class, 'show'])
        ->name('cafes.vibe.show');
    Route::post('/cafes/{cafe:id}/vibe', [VibeController::class, 'store'])
        ->name('cafes.vibe.store');

    // Bookmark API (Auth Required)
    Route::middleware('auth')->prefix('api/bookmarks')->group(function () {
        Route::get('/', [BookmarkController::class, 'index'])->name('bookmarks.index');
        Route::post('/toggle', [BookmarkController::class, 'toggle'])->name('bookmarks.toggle');
        Route::post('/sync', [BookmarkController::class, 'sync'])->name('bookmarks.sync');
    });

    // Check-in & Badges (Auth Required)
    Route::middleware('auth')->group(function () {
        Route::post('/cafes/{cafe:id}/check-in', [CheckInController::class, 'store'])
            ->name('cafes.check-in');
        Route::get('/api/badges', [CheckInController::class, 'badges'])
            ->name('badges.index');
        Route::get('/profile/badges', function () {
            return view('profile.badges');
        })->name('profile.badges');
    });

    // Tongkrongan (Public — no auth required)
    Route::get('/tongkrongan/search-cafes', [TongkronganController::class, 'searchCafes'])
        ->name('tongkrongan.search-cafes');
    Route::get('/tongkrongan/buat', [TongkronganController::class, 'create'])
        ->name('tongkrongan.create');
    Route::post('/tongkrongan', [TongkronganController::class, 'store'])
        ->name('tongkrongan.store');
    Route::get('/tongkrongan/{tongkrongan:uuid}', [TongkronganController::class, 'show'])
        ->name('tongkrongan.show');
    Route::get('/tongkrongan/{tongkrongan:uuid}/votes', [TongkronganController::class, 'getVotes'])
        ->name('tongkrongan.votes');
    Route::post('/tongkrongan/{tongkrongan:uuid}/vote/{item}', [TongkronganController::class, 'vote'])
        ->name('tongkrongan.vote');

    // Auth Actions
    Route::post('/logout', function (Request $request) {
        auth('customer')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->middleware('auth')->name('logout');
});

Route::middleware(['auth', 'throttle:web'])
    ->get('/admin/ai-conversations/{conversation}/messages/{message}/image', GeminiChatImageController::class)
    ->name('admin.ai-chat-images.show');

Route::middleware(['auth', 'throttle:web'])
    ->get('/admin/ai-prompt-previews/{promptKey}', AiPromptPreviewController::class)
    ->name('admin.ai-prompt-previews.show');

Route::middleware(['auth', 'throttle:web'])
    ->patch('/admin/profile-photo', [AdminProfilePhotoController::class, 'update'])
    ->name('admin.profile-photo.update');
