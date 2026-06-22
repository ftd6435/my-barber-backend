<?php

namespace App\Services;

use App\Models\Activities\Booking;
use App\Models\Activities\BookinReview;
use App\Models\Activities\ProPortfolio;
use App\Models\Activities\Service;
use App\Models\Activities\ServicePrice;
use App\Models\Salon;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class PermissionService
{
    use ApiResponses;

    public function isAdmin(?User $user): bool
    {
        return $user !== null && in_array($user->role, ['super_admin', 'admin'], true);
    }

    public function authorizeRoles(?User $user, array $roles, ?string $message = null)
    {
        if (!$user || !in_array($user->role, $roles, true)) {
            return $this->errorResponse(
                'Action non autorisée.',
                ['role' => $message ?? 'Vous n\'avez pas les autorisations nécessaires pour effectuer cette action.'],
                403
            );
        }

        return null;
    }

    public function authorizeRole(?User $user, string $role, ?string $message = null)
    {
        return $this->authorizeRoles(
            $user,
            [$role],
            $message ?? "Cette action est réservée aux utilisateurs de rôle {$role}."
        );
    }

    public function canManageSalon(?User $user, Salon $salon): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->is($salon->owner);
    }

    public function canViewSalon(?User $user, Salon $salon): bool
    {
        if ($salon->owner && $salon->owner->is_active && $salon->owner->is_approved) {
            return true;
        }

        return $this->canManageSalon($user, $salon);
    }

    public function canManageService(?User $user, Service $service): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $service->professionel_id === $user->id;
    }

    public function canViewService(?User $user, Service $service): bool
    {
        if (
            $service->is_active
            && $service->professionel?->is_active
            && $service->professionel?->is_approved
        ) {
            return true;
        }

        return $this->canManageService($user, $service);
    }

    public function canManageServicePrice(?User $user, ServicePrice $servicePrice): bool
    {
        return $servicePrice->service !== null && $this->canManageService($user, $servicePrice->service);
    }

    public function canViewServicePrice(?User $user, ServicePrice $servicePrice): bool
    {
        if ($servicePrice->service && $servicePrice->is_approved && $this->canViewService(null, $servicePrice->service)) {
            return true;
        }

        return $this->canManageServicePrice($user, $servicePrice);
    }

    public function canManageProPortfolio(?User $user, ProPortfolio $portfolio): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $portfolio->professionel_id === $user->id;
    }

    public function canViewProPortfolio(?User $user, ProPortfolio $portfolio): bool
    {
        if ($portfolio->service_id !== null && $portfolio->service === null) {
            return $this->canManageProPortfolio($user, $portfolio);
        }

        if (
            $portfolio->is_active
            && $portfolio->professionel?->is_active
            && $portfolio->professionel?->is_approved
            && ($portfolio->service === null || $this->canViewService(null, $portfolio->service))
        ) {
            return true;
        }

        return $this->canManageProPortfolio($user, $portfolio);
    }

    public function canManageBookingAsProfessionel(?User $user, Booking $booking): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $booking->professionel_id === $user->id;
    }

    public function canManageBookingAsClient(?User $user, Booking $booking): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $booking->client_id === $user->id;
    }

    public function canViewBooking(?User $user, Booking $booking): bool
    {
        return $this->canManageBookingAsProfessionel($user, $booking)
            || $this->canManageBookingAsClient($user, $booking);
    }

    public function canManageBookingReviewAsClient(?User $user, BookinReview $bookingReview): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $bookingReview->client_id === $user->id;
    }

    public function canSwitchBookingReviewVisibility(?User $user, BookinReview $bookingReview): bool
    {
        return $user !== null
            && $user->role === 'professionel'
            && $bookingReview->professionel_id === $user->id;
    }

    public function canViewBookingReview(?User $user, BookinReview $bookingReview): bool
    {
        if ($bookingReview->is_visible) {
            return true;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->canManageBookingReviewAsClient($user, $bookingReview)
            || $this->canSwitchBookingReviewVisibility($user, $bookingReview);
    }

    public function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', config('constants.pagination.per_page', 15));
        $maxPerPage = (int) config('constants.pagination.max_per_page', 100);

        return max(1, min($perPage, $maxPerPage));
    }
}
