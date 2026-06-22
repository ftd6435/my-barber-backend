<?php

namespace App\Http\Controllers\Activities;

use App\Http\Controllers\Controller;
use App\Http\Requests\activities\StoreBookingReviewRequest;
use App\Http\Requests\activities\SwitchBookingReviewVisibilityRequest;
use App\Http\Requests\activities\UpdateBookingReviewRequest;
use App\Http\Resources\BookingReviewResource;
use App\Models\Activities\Booking;
use App\Models\Activities\BookinReview;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingReviewController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $query = BookinReview::query()->with(['client', 'professionel', 'booking.service']);

        if ($this->permissionService->isAdmin($user)) {
            // Admins can view every review.
        } elseif ($user?->role === 'professionel') {
            $query->where(function ($reviewQuery) use ($user) {
                $reviewQuery
                    ->where('is_visible', true)
                    ->orWhere('professionel_id', $user->id);
            });
        } elseif ($user?->role === 'client') {
            $query->where(function ($reviewQuery) use ($user) {
                $reviewQuery
                    ->where('is_visible', true)
                    ->orWhere('client_id', $user->id);
            });
        } else {
            $query->where('is_visible', true);
        }

        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->integer('booking_id'));
        }

        if ($request->filled('professionel_id')) {
            $query->where('professionel_id', $request->integer('professionel_id'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        $reviews = $query
            ->latest()
            ->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'booking_reviews' => BookingReviewResource::collection($reviews->getCollection()),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ], 'Liste des avis de réservation récupérée avec succès.');
    }

    public function show(BookinReview $bookingReview)
    {
        $user = Auth::guard('sanctum')->user();
        $this->loadReviewRelations($bookingReview);

        if (!$this->permissionService->canViewBookingReview($user, $bookingReview)) {
            return $this->errorResponse(
                'Avis de réservation introuvable.',
                ['booking_review' => 'Cet avis de réservation n\'est pas disponible.'],
                404
            );
        }

        return $this->successResponse(
            new BookingReviewResource($bookingReview),
            'Avis de réservation récupéré avec succès.'
        );
    }

    public function store(StoreBookingReviewRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'client')) {
            return $authorization;
        }

        $booking = Booking::query()->findOrFail($request->integer('booking_id'));

        if (!$this->permissionService->canManageBookingAsClient($request->user(), $booking)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['booking_id' => 'Vous ne pouvez ajouter un avis qu\'à l\'une de vos propres réservations.'],
                403
            );
        }

        if ($booking->status !== 'completed') {
            return $this->errorResponse(
                'Action impossible.',
                ['booking_id' => 'Vous ne pouvez laisser un avis que sur une réservation terminée.'],
                422
            );
        }

        if (BookinReview::query()->where('booking_id', $booking->id)->exists()) {
            return $this->errorResponse(
                'Avis déjà existant.',
                ['booking_id' => 'Cette réservation possède déjà un avis.'],
                422
            );
        }

        $bookingReview = BookinReview::query()->create([
            'booking_id' => $booking->id,
            'client_id' => $request->user()->id,
            'professionel_id' => $booking->professionel_id,
            'review' => $request->validated('review'),
            'rating' => $request->validated('rating'),
        ]);

        $this->loadReviewRelations($bookingReview);

        return $this->successResponse(
            new BookingReviewResource($bookingReview),
            'Avis de réservation créé avec succès.',
            201
        );
    }

    public function update(UpdateBookingReviewRequest $request, BookinReview $bookingReview)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'client')) {
            return $authorization;
        }

        $this->loadReviewRelations($bookingReview);

        if (!$this->permissionService->canManageBookingReviewAsClient($request->user(), $bookingReview)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['booking_review' => 'Vous ne pouvez modifier que vos propres avis de réservation.'],
                403
            );
        }

        $data = [];

        if ($request->has('review')) {
            $data['review'] = $request->validated('review');
        }

        if ($request->has('rating')) {
            $data['rating'] = $request->validated('rating');
        }

        $bookingReview->update($data);

        $this->loadReviewRelations($bookingReview);

        return $this->successResponse(
            new BookingReviewResource($bookingReview),
            'Avis de réservation mis à jour avec succès.'
        );
    }

    public function switchVisibility(SwitchBookingReviewVisibilityRequest $request, BookinReview $bookingReview)
    {
        if ($authorization = $this->permissionService->authorizeRole($request->user(), 'professionel')) {
            return $authorization;
        }

        $this->loadReviewRelations($bookingReview);

        if (!$this->permissionService->canSwitchBookingReviewVisibility($request->user(), $bookingReview)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['booking_review' => 'Seul le professionnel concerné par cette réservation peut modifier la visibilité de cet avis.'],
                403
            );
        }

        $bookingReview->update([
            'is_visible' => $request->boolean('is_visible'),
        ]);

        $this->loadReviewRelations($bookingReview);

        return $this->successResponse(
            new BookingReviewResource($bookingReview),
            'Visibilité de l\'avis de réservation mise à jour avec succès.'
        );
    }

    private function loadReviewRelations(BookinReview $bookingReview): void
    {
        $bookingReview->loadMissing([
            'client',
            'professionel',
            'booking.service',
        ]);
    }
}
