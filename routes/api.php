<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\auth\ActeurController;
use App\Http\Controllers\auth\UserController;
use App\Http\Controllers\Activities\AgeRangeController;
use App\Http\Controllers\Activities\BookingController;
use App\Http\Controllers\Activities\BookingReviewController;
use App\Http\Controllers\Activities\CategoryController;
use App\Http\Controllers\Activities\ProAvailabilityController;
use App\Http\Controllers\Activities\ProPortfolioController;
use App\Http\Controllers\Activities\ServiceController;
use App\Http\Controllers\Activities\ServicePriceController;
use App\Http\Controllers\BookingCommissionSettingController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\Djomy\PaymentController;
use App\Http\Controllers\Djomy\PaymentLinkController;
use App\Http\Controllers\Djomy\WebhookController;
use App\Http\Controllers\ExchangeRateController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WalletTransactionController;
use App\Http\Controllers\WithdrawalRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::get('/verify-email/{uuid}', [AuthController::class, 'verifyEmail'])->name('auth.verify-email');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/resend-email', [AuthController::class, 'resendEmail']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->prefix('v1/users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/admins', [UserController::class, 'getAdmins']);
    Route::post('/', [UserController::class, 'store']);
    Route::get('/me', [UserController::class, 'me']);
    Route::put('/me', [UserController::class, 'update']);
    Route::patch('/me/password', [UserController::class, 'changePassword']);
    Route::post('/me/avatar', [UserController::class, 'updateAvatar']);
    Route::post('/avatar', [UserController::class, 'updateAvatar']);
    Route::get('/{uuid}', [UserController::class, 'show']);
    Route::patch('/{uuid}/approve', [UserController::class, 'approveUser']);
    Route::patch('/{uuid}/active', [UserController::class, 'activeUser']);
    Route::delete('/{uuid}', [UserController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('v1/acteurs')->group(function () {
    Route::get('/professionel/me', [ActeurController::class, 'getProfessionelProfile']);
    Route::put('/professionel/me', [ActeurController::class, 'userProfessionel']);
    Route::get('/client/me', [ActeurController::class, 'getClientProfile']);
    Route::put('/client/me', [ActeurController::class, 'userClient']);
});

Route::prefix('v1/salons')->group(function () {
    Route::get('/', [SalonController::class, 'index']);
    Route::get('/{uuid}', [SalonController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/professionel/me', [SalonController::class, 'getProfessionelSalons']);
        Route::post('/', [SalonController::class, 'store']);
        Route::put('/{uuid}', [SalonController::class, 'update']);
        Route::patch('/{uuid}/active', [SalonController::class, 'switchActive']);
        Route::delete('/{uuid}', [SalonController::class, 'destroy']);
    });
});

Route::prefix('v1/age-ranges')->group(function () {
    Route::get('/', [AgeRangeController::class, 'index']);
    Route::get('/{ageRange}', [AgeRangeController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [AgeRangeController::class, 'store']);
        Route::put('/{ageRange}', [AgeRangeController::class, 'update']);
        Route::patch('/{ageRange}/status', [AgeRangeController::class, 'switchStatus']);
        Route::delete('/{ageRange}', [AgeRangeController::class, 'destroy']);
    });
});

Route::prefix('v1/categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('/{category}', [CategoryController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('/{category}', [CategoryController::class, 'update']);
        Route::patch('/{category}/status', [CategoryController::class, 'switchStatus']);
        Route::delete('/{category}', [CategoryController::class, 'destroy']);
    });
});

Route::prefix('v1/currencies')->group(function () {
    Route::get('/', [CurrencyController::class, 'index']);
    Route::get('/{currency}', [CurrencyController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [CurrencyController::class, 'store']);
        Route::put('/{currency}', [CurrencyController::class, 'update']);
        Route::delete('/{currency}', [CurrencyController::class, 'destroy']);
    });
});

Route::prefix('v1/exchange-rates')->group(function () {
    Route::get('/', [ExchangeRateController::class, 'index']);
    Route::get('/{exchangeRate}', [ExchangeRateController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ExchangeRateController::class, 'store']);
        Route::put('/{exchangeRate}', [ExchangeRateController::class, 'update']);
        Route::patch('/{exchangeRate}/status', [ExchangeRateController::class, 'switchStatus']);
        Route::delete('/{exchangeRate}', [ExchangeRateController::class, 'destroy']);
    });
});

Route::middleware('auth:sanctum')->prefix('v1/booking-commission-settings')->group(function () {
    Route::get('/', [BookingCommissionSettingController::class, 'index']);
    Route::get('/active', [BookingCommissionSettingController::class, 'active']);
    Route::get('/{bookingCommissionSetting}', [BookingCommissionSettingController::class, 'show']);
    Route::post('/', [BookingCommissionSettingController::class, 'store']);
    Route::put('/{bookingCommissionSetting}', [BookingCommissionSettingController::class, 'update']);
    Route::patch('/{bookingCommissionSetting}/status', [BookingCommissionSettingController::class, 'switchStatus']);
});

Route::middleware('auth:sanctum')->prefix('v1/pro-availabilities')->group(function () {
    Route::get('/', [ProAvailabilityController::class, 'index']);
    Route::get('/{proAvailability}', [ProAvailabilityController::class, 'show']);
    Route::post('/', [ProAvailabilityController::class, 'store']);
    Route::put('/{proAvailability}', [ProAvailabilityController::class, 'update']);
    Route::patch('/{proAvailability}/status', [ProAvailabilityController::class, 'switchStatus']);
    Route::delete('/{proAvailability}', [ProAvailabilityController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->prefix('v1/wallets')->group(function () {
    Route::get('/', [WalletController::class, 'index']);
    Route::get('/{wallet}', [WalletController::class, 'show']);
});

Route::middleware('auth:sanctum')->prefix('v1/wallet-transactions')->group(function () {
    Route::get('/', [WalletTransactionController::class, 'index']);
    Route::get('/{walletTransaction}', [WalletTransactionController::class, 'show']);
});

Route::middleware('auth:sanctum')->prefix('v1/withdrawal-requests')->group(function () {
    Route::get('/', [WithdrawalRequestController::class, 'index']);
    Route::post('/', [WithdrawalRequestController::class, 'store']);
    Route::get('/{withdrawalRequest}', [WithdrawalRequestController::class, 'show']);
    Route::patch('/{withdrawalRequest}/process', [WithdrawalRequestController::class, 'process']);
    Route::patch('/{withdrawalRequest}/cancel', [WithdrawalRequestController::class, 'cancel']);
});

Route::prefix('v1/services')->group(function () {
    Route::get('/', [ServiceController::class, 'index']);
    Route::get('/{service}', [ServiceController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ServiceController::class, 'store']);
        Route::put('/{service}', [ServiceController::class, 'update']);
        Route::patch('/{service}/status', [ServiceController::class, 'switchStatus']);
        Route::delete('/{service}', [ServiceController::class, 'destroy']);
    });
});

Route::prefix('v1/service-prices')->group(function () {
    Route::get('/', [ServicePriceController::class, 'index']);
    Route::get('/{servicePrice}', [ServicePriceController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ServicePriceController::class, 'store']);
        Route::put('/{servicePrice}', [ServicePriceController::class, 'update']);
        Route::delete('/{servicePrice}', [ServicePriceController::class, 'destroy']);
    });
});

Route::prefix('v1/pro-portfolios')->group(function () {
    Route::get('/', [ProPortfolioController::class, 'index']);
    Route::get('/{proPortfolio}', [ProPortfolioController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [ProPortfolioController::class, 'store']);
        Route::put('/{proPortfolio}', [ProPortfolioController::class, 'update']);
        Route::patch('/{proPortfolio}/active', [ProPortfolioController::class, 'switchActive']);
        Route::delete('/{proPortfolio}', [ProPortfolioController::class, 'destroy']);
    });
});

Route::prefix('v1/bookings')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/', [BookingController::class, 'index']);
        Route::get('/{booking}', [BookingController::class, 'show']);
        Route::post('/', [BookingController::class, 'store']);
        Route::patch('/{booking}/accept', [BookingController::class, 'accept']);
        Route::patch('/{booking}/reject', [BookingController::class, 'reject']);
        Route::patch('/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::patch('/{booking}/complete', [BookingController::class, 'complete']);
    });
});

Route::prefix('v1/booking-reviews')->group(function () {
    Route::get('/', [BookingReviewController::class, 'index']);
    Route::get('/{bookingReview}', [BookingReviewController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/', [BookingReviewController::class, 'store']);
        Route::put('/{bookingReview}', [BookingReviewController::class, 'update']);
        Route::patch('/{bookingReview}/visibility', [BookingReviewController::class, 'switchVisibility']);
    });
});


// Direct payments (OM, MOMO, KULU, YMO, SOUTRA_MONEY, PAYCARD)
Route::middleware('auth:sanctum')->prefix('v1/payments')->group(function () {
    Route::post('/initiate', [PaymentController::class, 'initiate']);
    Route::get('/{reference}/status', [PaymentController::class, 'status']);
});

// Payment links (all methods including CARD/VISA/MASTERCARD)
Route::middleware('auth:sanctum')->prefix('v1/payment-links')->group(function () {
    Route::post('/', [PaymentLinkController::class, 'create']);
    Route::get('/', [PaymentLinkController::class, 'index']);
    Route::get('/{reference}', [PaymentLinkController::class, 'show']);
});

// Webhook — receives async payment status updates from Djomy
// ⚠️ Register your public URL in the Djomy merchant dashboard
// ⚠️ Exclude from CSRF in App\Http\Middleware\VerifyCsrfToken:
//    protected $except = ['api/webhooks/djomy'];
Route::post('v1/webhooks/djomy', [WebhookController::class, 'handle'])
    ->withoutMiddleware(['auth', 'throttle']) // Djomy must reach this freely
    ->name('djomy.webhook');
