<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\CheckInController;
use App\Http\Controllers\Api\VibeController;
use App\Http\Controllers\Api\WfcScoreController;
use App\Http\Controllers\CafeController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerFeedbackController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\InformationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OwnerOrderController;
use App\Http\Controllers\RoasteryController;
use App\Http\Controllers\SavedController;
use App\Http\Controllers\TongkronganController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes with rate limiting
Route::middleware(['throttle:web'])->group(function () {
    Route::get('/', [CafeController::class, 'index'])->name('home');
    Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
    Route::get('/saved', [SavedController::class, 'index'])->name('saved');
    Route::get('/information', [InformationController::class, 'index'])->name('information');
    Route::get('/information/{information:slug}', [InformationController::class, 'show'])->name('information.show');
    Route::get('/profile', fn () => auth()->check() ? redirect()->route('account') : redirect()->route('login'))->name('profile');
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
    Route::post('/cafes/{cafe}/menu/{menuItem}/cart', [OrderController::class, 'addItem'])->name('cart.items.add');
    Route::post('/roasteries/{roastery}/menu/{menuItem}/cart', [OrderController::class, 'addRoasteryItem'])->name('roastery.cart.items.add');
    Route::patch('/cart/items/{menuItem}', [OrderController::class, 'updateItem'])->name('cart.items.update');
    Route::delete('/cart', [OrderController::class, 'clearCart'])->name('cart.clear');
    Route::middleware('auth')->group(function () {
        Route::get('/account', AccountController::class)->name('account');
        Route::get('/checkout/{cafe}', [OrderController::class, 'checkout'])->name('checkout.show');
        Route::post('/checkout/{cafe}', [OrderController::class, 'placeOrder'])->name('checkout.store');
        Route::get('/checkout/roastery/{roastery}', [OrderController::class, 'checkoutRoastery'])->name('roastery.checkout.show');
        Route::post('/checkout/roastery/{roastery}', [OrderController::class, 'placeRoasteryOrder'])->name('roastery.checkout.store');
        Route::get('/orders/history', [OrderController::class, 'history'])->name('orders.history');
    });
    Route::get('/order-tracking', [OrderController::class, 'lookupForm'])->name('orders.lookup');
    Route::post('/order-tracking', [OrderController::class, 'lookup'])->middleware('throttle:10,1')->name('orders.lookup.submit');
    Route::get('/orders/{code}/status', [OrderController::class, 'status'])->middleware('throttle:60,1')->name('orders.status');
    Route::post('/orders/{code}/payment', [OrderController::class, 'submitPayment'])->middleware('throttle:10,1')->name('orders.payment.submit');
    Route::get('/orders/{code}/receipt', [OrderController::class, 'receipt'])->middleware('throttle:30,1')->name('orders.receipt');
    Route::get('/orders/{code}', [OrderController::class, 'show'])->middleware('throttle:30,1')->name('orders.show');

    // Kedai/order operations are additionally scoped to the authenticated cafe owner.
    Route::prefix('owner')->name('owner.')->middleware('auth')->group(function () {
        Route::get('/orders', [OwnerOrderController::class, 'index'])->name('orders.index');
        Route::post('/pickup', [OwnerOrderController::class, 'pickup'])->middleware('throttle:20,1')->name('orders.pickup');
        Route::get('/orders/{code}', [OwnerOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{code}/confirm-payment', [OwnerOrderController::class, 'confirmPayment'])->name('orders.confirm-payment');
        Route::post('/orders/{code}/confirm-cash-payment', [OwnerOrderController::class, 'confirmCashPayment'])->name('orders.confirm-cash-payment');
        Route::post('/orders/{code}/reject-payment', [OwnerOrderController::class, 'rejectPayment'])->name('orders.reject-payment');
        Route::post('/orders/{code}/status', [OwnerOrderController::class, 'status'])->name('orders.status');
        Route::post('/orders/{code}/cancel', [OwnerOrderController::class, 'cancel'])->name('orders.cancel');
        Route::get('/orders/{code}/payments/{paymentId}/proof', [OwnerOrderController::class, 'proof'])->name('orders.payment-proof');
        Route::get('/orders/{code}/print', [OwnerOrderController::class, 'printReceipt'])->name('orders.print');
        Route::post('/orders/{code}/print/reprint', [OwnerOrderController::class, 'reprint'])->name('orders.reprint');
        Route::post('/print-jobs/{receiptPrintJob}/status', [OwnerOrderController::class, 'updatePrintJob'])->name('print-jobs.status');
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
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    })->middleware('auth')->name('logout');
});
