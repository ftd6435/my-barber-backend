<?php

namespace App\Http\Controllers;

use App\Http\Requests\finance\StoreBookingCommissionSettingRequest;
use App\Http\Resources\BookingCommissionSettingResource;
use App\Models\BookingCommissionSetting;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingCommissionSettingController extends Controller
{
    use ApiResponses;

    public function __construct(
        private PermissionService $permissionService,
    ) {}

    public function index(Request $request)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $settings = BookingCommissionSetting::query()
            ->with('updatedBy')
            ->latest()
            ->paginate($this->permissionService->perPage($request));

        return $this->successResponse([
            'booking_commission_settings' => BookingCommissionSettingResource::collection($settings->getCollection()),
            'pagination' => [
                'current_page' => $settings->currentPage(),
                'last_page' => $settings->lastPage(),
                'per_page' => $settings->perPage(),
                'total' => $settings->total(),
            ],
        ], 'Liste des configurations de commission récupérée avec succès.');
    }

    public function show(Request $request, BookingCommissionSetting $bookingCommissionSetting)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $bookingCommissionSetting->loadMissing('updatedBy');

        return $this->successResponse(
            new BookingCommissionSettingResource($bookingCommissionSetting),
            'Configuration de commission récupérée avec succès.'
        );
    }

    public function active(Request $request)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $setting = BookingCommissionSetting::query()
            ->with('updatedBy')
            ->where('is_active', true)
            ->latest()
            ->first();

        if (!$setting) {
            return $this->successResponse(
                null,
                'Aucune configuration de commission active pour le moment.'
            );
        }

        return $this->successResponse(
            new BookingCommissionSettingResource($setting),
            'Configuration de commission active récupérée avec succès.'
        );
    }

    public function store(StoreBookingCommissionSettingRequest $request)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $data = $request->validated();
        $setting = DB::transaction(function () use ($request, $data) {
            if (($data['is_active'] ?? true) === true) {
                BookingCommissionSetting::query()->update(['is_active' => false]);
            }

            return BookingCommissionSetting::query()->create([
                'percentage' => $data['percentage'],
                'is_active' => $data['is_active'] ?? true,
                'updated_by' => $request->user()->id,
            ]);
        });

        $setting->loadMissing('updatedBy');

        return $this->successResponse(
            new BookingCommissionSettingResource($setting),
            'Configuration de commission créée avec succès.',
            201
        );
    }

    public function update(StoreBookingCommissionSettingRequest $request, BookingCommissionSetting $bookingCommissionSetting)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $bookingCommissionSetting) {
            if (($data['is_active'] ?? $bookingCommissionSetting->is_active) === true) {
                BookingCommissionSetting::query()
                    ->where('id', '!=', $bookingCommissionSetting->id)
                    ->update(['is_active' => false]);
            }

            $bookingCommissionSetting->update([
                'percentage' => $data['percentage'],
                'is_active' => $data['is_active'] ?? $bookingCommissionSetting->is_active,
                'updated_by' => $request->user()->id,
            ]);
        });

        $bookingCommissionSetting->refresh()->loadMissing('updatedBy');

        return $this->successResponse(
            new BookingCommissionSettingResource($bookingCommissionSetting),
            'Configuration de commission mise à jour avec succès.'
        );
    }

    public function switchStatus(Request $request, BookingCommissionSetting $bookingCommissionSetting)
    {
        if ($authorization = $this->permissionService->authorizeRoles($request->user(), ['super_admin', 'admin'])) {
            return $authorization;
        }

        DB::transaction(function () use ($request, $bookingCommissionSetting) {
            $isActive = !$bookingCommissionSetting->is_active;

            if ($isActive === true) {
                BookingCommissionSetting::query()
                    ->where('id', '!=', $bookingCommissionSetting->id)
                    ->update(['is_active' => false]);
            }

            $bookingCommissionSetting->update([
                'is_active' => $isActive,
                'updated_by' => $request->user()->id,
            ]);
        });

        $bookingCommissionSetting->refresh()->loadMissing('updatedBy');

        return $this->successResponse(
            new BookingCommissionSettingResource($bookingCommissionSetting),
            $bookingCommissionSetting->is_active
                ? 'Configuration de commission activée avec succès.'
                : 'Configuration de commission désactivée avec succès.'
        );
    }
}
