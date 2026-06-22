<?php

namespace App\Http\Controllers\auth;

use App\Events\SendMessageEvent;
use App\Events\SendMessageToManyEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\acteur\UpsertClientProfileRequest;
use App\Http\Requests\acteur\UpsertProfessionelProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\Acteurs\Client;
use App\Models\Acteurs\Professionel;
use App\Models\User;
use App\Services\PermissionService;
use App\Traits\ApiResponses;
use App\Traits\CloudflareUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActeurController extends Controller
{
    use ApiResponses, CloudflareUpload;

    public function __construct(
        private PermissionService $permissionService,
    ) {
    }

    /** This method is used to get the professionel profile of an authenticated user of role professionel */
    public function getProfessionelProfile(Request $request)
    {
        $user = $request->user();

        if ($authorization = $this->permissionService->authorizeRole($user, 'professionel')) {
            return $authorization;
        }

        $user->loadMissing(['professionel', 'client']);

        if (!$user->professionel) {
            return $this->errorResponse(
                'Profil professionnel introuvable.',
                ['professionel' => 'Aucun profil professionnel n\'a encore été créé pour cet utilisateur.'],
                404
            );
        }

        return $this->successResponse(
            new UserResource($user),
            'Profil professionnel récupéré avec succès.'
        );
    }

    /** This method is used to get the client profile of an authenticated user of role client */
    public function getClientProfile(Request $request)
    {
        $user = $request->user();

        if ($authorization = $this->permissionService->authorizeRole($user, 'client')) {
            return $authorization;
        }

        $user->loadMissing(['professionel', 'client']);

        if (!$user->client) {
            return $this->errorResponse(
                'Profil client introuvable.',
                ['client' => 'Aucun profil client n\'a encore été créé pour cet utilisateur.'],
                404
            );
        }

        return $this->successResponse(
            new UserResource($user),
            'Profil client récupéré avec succès.'
        );
    }

    /** This method is used to let an authenticated user of role professionel to createOrUpdate his professionel profile */
    public function userProfessionel(UpsertProfessionelProfileRequest $request)
    {
        $user = $request->user();

        if ($authorization = $this->permissionService->authorizeRole($user, 'professionel')) {
            return $authorization;
        }

        $data = $request->validated();
        $newDocument = null;
        $oldDocument = null;
        $documentChanged = false;

        try {
            $professionel = DB::transaction(function () use ($user, $data, &$newDocument, &$oldDocument, &$documentChanged) {
                $professionel = $user->professionel()->firstOrNew();

                if (!empty($data['document'])) {
                    $newDocument = $this->uploadFile($data['document'], 'professionel-documents');
                    $oldDocument = $professionel->document;
                    $data['document'] = $newDocument;
                    $documentChanged = true;
                } else {
                    unset($data['document']);
                }

                $professionel->fill($data);
                $professionel->user_id = $user->id;
                $professionel->save();

                if ($documentChanged) {
                    $user->forceFill([
                        'is_approved' => false,
                    ])->save();
                }

                return $professionel;
            });

            if ($documentChanged && $oldDocument) {
                $this->deleteFile($oldDocument, 'professionel-documents');
            }

            if ($documentChanged) {
                $this->notifyProfessionelPendingApproval($user);
            }

            $user->loadMissing(['professionel', 'client']);

            return $this->successResponse(
                new UserResource($user),
                $professionel->wasRecentlyCreated
                    ? 'Profil professionnel créé avec succès.'
                    : 'Profil professionnel mis à jour avec succès.'
            );
        } catch (\Throwable $e) {
            if ($newDocument) {
                $this->deleteFile($newDocument, 'professionel-documents');
            }

            return $this->errorResponse(
                'Une erreur s\'est produite lors de l\'enregistrement du profil professionnel.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /** This method is used to let an authenticated user of role client to createOrUpdate his client profile */
    public function userClient(UpsertClientProfileRequest $request)
    {
        $user = $request->user();

        if ($authorization = $this->permissionService->authorizeRole($user, 'client')) {
            return $authorization;
        }

        try {
            $client = DB::transaction(function () use ($user, $request) {
                $client = $user->client()->firstOrNew();
                $client->fill($request->validated());
                $client->user_id = $user->id;
                $client->save();

                if (!$user->is_approved) {
                    $user->forceFill([
                        'is_approved' => true,
                    ])->save();
                }

                return $client;
            });

            $user->loadMissing(['professionel', 'client']);

            return $this->successResponse(
                new UserResource($user),
                $client->wasRecentlyCreated
                    ? 'Profil client créé avec succès.'
                    : 'Profil client mis à jour avec succès.'
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                'Une erreur s\'est produite lors de l\'enregistrement du profil client.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    private function notifyProfessionelPendingApproval(User $user): void
    {
        $admins = User::query()
            ->whereIn('role', ['super_admin', 'admin'])
            ->where('is_active', true)
            ->pluck('telephone')
            ->filter()
            ->values()
            ->all();

        if ($admins !== []) {
            $adminMessage = "Le professionnel {$user->first_name} {$user->last_name} a mis à jour son document. Son profil est de nouveau en attente de validation.";
            SendMessageToManyEvent::dispatch($admins, $adminMessage);
        }

        $userMessage = "Bonjour {$user->first_name}, votre document professionnel a été mis à jour. Votre profil est maintenant en attente d'approbation par l'administration.";
        SendMessageEvent::dispatch($user->telephone, $userMessage);
    }
}
