<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\activities\AcceptBookingRequest;
use App\Http\Requests\activities\CancelBookingRequest;
use App\Http\Requests\activities\CompleteBookingRequest;
use App\Http\Requests\activities\RejectBookingRequest;
use App\Http\Requests\activities\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Activities\Booking;
use App\Models\Currency;
use App\Models\Activities\Service as ActivityService;
use App\Services\BookingCommissionService;
use App\Services\BookingPaymentService;
use App\Services\BookingNotificationService;
use App\Services\CurrencyConversionService;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
        private BookingNotificationService $bookingNotificationService,
        private CurrencyConversionService $currencyConversionService,
        private BookingPaymentService $bookingPaymentService,
        private BookingCommissionService $bookingCommissionService,
    ) {}

    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $query = Booking::query()->with([
            'professionel',
            'client',
            'service.currency',
            'serviceCurrency',
            'clientCurrency',
            'settlementCurrency',
            'bookingPrices.ageRange',
            'bookingPrices.currency',
        ]);

        if ($this->permissionService->isAdmin($user)) {
            // Admins can view every booking.
        } elseif ($user?->role === 'professionel') {
            $query->where('professionel_id', $user->id);
        } elseif ($user?->role === 'client') {
            $query->where('client_id', $user->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        if ($request->filled('reference')) {
            $reference = trim((string) $request->input('reference'));
            $query->where('reference', 'like', "%{$reference}%");
        }

        $bookings = $query
            ->latest()
            ->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'bookings' => BookingResource::collection($bookings->getCollection()),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ], 'Liste des réservations récupérée avec succès.');
    }

    public function show(Booking $booking)
    {
        $user = Auth::guard('sanctum')->user();
        $this->loadBookingRelations($booking);

        if (!$this->permissionService->canViewBooking($user, $booking)) {
            return $this->errorResponse(
                'Réservation introuvable.',
                ['booking' => 'Cette réservation n\'est pas disponible.'],
                404
            );
        }

        return $this->successResponse(
            new BookingResource($booking),
            'Réservation récupérée avec succès.'
        );
    }

    public function store(StoreBookingRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'client')) {
            return $authorization;
        }

        $client = $request->user();
        $data = $request->validated();
        $service = ActivityService::query()
            ->with([
                'professionel',
                'currency',
                'servicePrices' => fn($query) => $query->where('is_approved', true),
                'servicePrices.service.currency',
            ])
            ->findOrFail($data['service_id']);

        if (
            !$service->is_active
            || !$service->professionel?->is_active
            || !$service->professionel?->is_approved
        ) {
            return $this->errorResponse(
                'Service indisponible.',
                ['service_id' => 'Ce service n\'est pas disponible pour la réservation.'],
                422
            );
        }

        if (!$service->currency_id) {
            return $this->errorResponse(
                'Devise du service manquante.',
                ['service' => 'Ce service ne dispose pas encore d\'une devise de facturation.'],
                422
            );
        }

        $clientCurrencyId = (int) ($data['client_currency_id'] ?? $client->default_currency_id);

        if (!$clientCurrencyId || !Currency::query()->whereKey($clientCurrencyId)->exists()) {
            return $this->errorResponse(
                'Devise de paiement invalide.',
                ['client_currency_id' => 'Veuillez définir une devise de paiement valide pour cette réservation.'],
                422
            );
        }

        try {
            $conversion = $this->currencyConversionService->convert(
                1,
                (int) $service->currency_id,
                $clientCurrencyId
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                'Taux de change indisponible.',
                ['client_currency_id' => $e->getMessage()],
                422
            );
        }

        $ageRanges = collect($data['age_ranges'])->keyBy('age_range_id');
        $approvedPrices = $service->servicePrices
            ->whereIn('age_range_id', $ageRanges->keys())
            ->keyBy('age_range_id');
        $missingAgeRanges = $ageRanges->keys()->diff($approvedPrices->keys())->values()->all();

        if ($missingAgeRanges !== []) {
            return $this->errorResponse(
                'Tarification indisponible.',
                ['age_ranges' => 'Une ou plusieurs tranches d\'âge sélectionnées ne disposent pas d\'un prix approuvé pour ce service.'],
                422
            );
        }

        $commissionPercentage = $this->bookingCommissionService->getActivePercentage();

        $booking = DB::transaction(function () use ($client, $data, $service, $ageRanges, $approvedPrices, $clientCurrencyId, $conversion, $commissionPercentage) {
            $reference = $this->generateBookingReference();
            $startTime = Carbon::createFromFormat('H:i', $data['start_time']);
            $endTime = (clone $startTime)->addMinutes((int) $service->duration_minutes);

            $booking = Booking::query()->create([
                'reference' => $reference,
                'professionel_id' => $service->professionel_id,
                'client_id' => $client->id,
                'service_id' => $service->id,
                'service_currency_id' => $service->currency_id,
                'client_currency_id' => $clientCurrencyId,
                'settlement_currency_id' => $service->currency_id,
                'booking_date' => $data['booking_date'],
                'start_time' => $startTime->format('H:i:s'),
                'location' => $data['location'],
                'client_address' => $data['location'] === 'home' ? ($data['client_address'] ?? null) : null,
                'latitude' => $data['location'] === 'home' ? ($data['latitude'] ?? null) : null,
                'longitude' => $data['location'] === 'home' ? ($data['longitude'] ?? null) : null,
                'status' => 'pending',
                'payment_status' => 'pending',
                'service_to_client_exchange_rate' => $conversion['rate'],
                'service_subtotal_amount' => 0,
                'service_total_amount' => 0,
                'client_total_amount' => 0,
                'settlement_total_amount' => 0,
                'client_refunded_amount' => 0,
                'platform_fee_percentage' => $commissionPercentage,
                'platform_fee_amount' => 0,
                'professionel_net_amount' => 0,
                'booking_details' => $data['booking_details'] ?? null,
                'extra_fees' => 0,
            ]);

            foreach ($ageRanges as $ageRangeId => $ageRange) {
                $approvedPrice = $approvedPrices->get($ageRangeId);

                $booking->bookingPrices()->create([
                    'age_range_id' => $ageRangeId,
                    'currency_id' => $service->currency_id,
                    'number' => $ageRange['number'],
                    'price' => $approvedPrice->price,
                ]);
            }

            return $this->bookingPaymentService->refreshBookingMonetarySnapshot($booking);
        });

        $this->loadBookingRelations($booking);
        $this->bookingNotificationService->notifyProfessionelBookingSubmitted($booking);

        return $this->successResponse(
            new BookingResource($booking),
            'Réservation créée avec succès.',
            201
        );
    }

    public function accept(AcceptBookingRequest $request, Booking $booking)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        if (!$this->permissionService->canManageBookingAsProfessionel($request->user(), $booking)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['booking' => 'Vous ne pouvez accepter que vos propres réservations.'],
                403
            );
        }

        if (!in_array($booking->status, ['pending', 'accepted'], true)) {
            return $this->errorResponse(
                'Action impossible.',
                ['status' => 'Seules les réservations en attente ou déjà acceptées peuvent être acceptées.'],
                422
            );
        }

        $data = $request->validated();
        $booking->update([
            'status' => 'accepted',
            'professionel_comment' => $data['professionel_comment'] ?? $booking->professionel_comment,
            'extra_fees' => $data['extra_fees'] ?? $booking->extra_fees,
            'cancel_reason' => null,
        ]);

        $booking = $this->bookingPaymentService->refreshBookingMonetarySnapshot($booking);

        $this->loadBookingRelations($booking);
        $this->bookingNotificationService->notifyClientBookingAccepted($booking);

        return $this->successResponse(
            new BookingResource($booking),
            'Réservation acceptée avec succès.'
        );
    }

    public function reject(RejectBookingRequest $request, Booking $booking)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        if (!$this->permissionService->canManageBookingAsProfessionel($request->user(), $booking)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['booking' => 'Vous ne pouvez refuser que vos propres réservations.'],
                403
            );
        }

        if (!in_array($booking->status, ['pending', 'accepted'], true)) {
            return $this->errorResponse(
                'Action impossible.',
                ['status' => 'Seules les réservations en attente ou acceptées peuvent être refusées.'],
                422
            );
        }

        $booking->update([
            'status' => 'rejected',
            'professionel_comment' => $request->validated('professionel_comment'),
            'extra_fees' => 0,
        ]);

        $this->bookingPaymentService->refundBookingToClientWallet(
            $booking->fresh(),
            'Réservation refusée par le professionnel.'
        );

        $booking->refresh();
        $this->loadBookingRelations($booking);
        $this->bookingNotificationService->notifyClientBookingRejected($booking);

        return $this->successResponse(
            new BookingResource($booking),
            'Réservation refusée avec succès.'
        );
    }

    public function cancel(CancelBookingRequest $request, Booking $booking)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'client')) {
            return $authorization;
        }

        if (!$this->permissionService->canManageBookingAsClient($request->user(), $booking)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['booking' => 'Vous ne pouvez annuler que vos propres réservations.'],
                403
            );
        }

        if (!in_array($booking->status, ['pending', 'accepted'], true)) {
            return $this->errorResponse(
                'Action impossible.',
                ['status' => 'Seules les réservations en attente ou acceptées peuvent être annulées.'],
                422
            );
        }

        $booking->update([
            'status' => 'cancelled',
            'cancel_reason' => $request->validated('cancel_reason'),
        ]);

        $this->bookingPaymentService->refundBookingToClientWallet(
            $booking->fresh(),
            'Réservation annulée par le client.'
        );

        $booking->refresh();
        $this->loadBookingRelations($booking);
        $this->bookingNotificationService->notifyProfessionelBookingCancelled($booking);

        return $this->successResponse(
            new BookingResource($booking),
            'Réservation annulée avec succès.'
        );
    }

    public function complete(CompleteBookingRequest $request, Booking $booking)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'client')) {
            return $authorization;
        }

        if (!$this->permissionService->canManageBookingAsClient($request->user(), $booking)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['booking' => 'Vous ne pouvez terminer que vos propres réservations.'],
                403
            );
        }

        if ($booking->status !== 'accepted') {
            return $this->errorResponse(
                'Action impossible.',
                ['status' => 'Seules les réservations acceptées peuvent être marquées comme terminées.'],
                422
            );
        }

        if ($booking->payment_status !== 'completed') {
            return $this->errorResponse(
                'Action impossible.',
                ['payment_status' => 'La réservation doit être entièrement payée avant d\'être marquée comme terminée.'],
                422
            );
        }

        $booking->update([
            'status' => 'completed',
        ]);

        $this->bookingPaymentService->releaseBookingFundsToProfessionel($booking->fresh());

        $booking->refresh();
        $this->loadBookingRelations($booking);
        $this->bookingNotificationService->notifyProfessionelBookingCompleted($booking);

        return $this->successResponse(
            new BookingResource($booking),
            'Réservation terminée avec succès.'
        );
    }

    private function loadBookingRelations(Booking $booking): void
    {
        $booking->loadMissing([
            'professionel',
            'client',
            'service.currency',
            'serviceCurrency',
            'clientCurrency',
            'settlementCurrency',
            'bookingPrices.ageRange',
            'bookingPrices.currency',
        ]);
    }

    private function generateBookingReference(): string
    {
        $nextId = (Booking::query()->withTrashed()->lockForUpdate()->max('id') ?? 0) + 1;

        return 'BK-' . now()->format('Y') . str_pad((string) $nextId, 3, '0', STR_PAD_LEFT);
    }
}
