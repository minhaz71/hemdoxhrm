<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invitation\StoreInvitationRequest;
use App\Models\RegistrationInvitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;

class InvitationController extends Controller
{
    public function __construct(private readonly InvitationService $invitationService) {}

    public function index()
    {
        $this->authorize('viewAny', RegistrationInvitation::class);

        $invitations = $this->invitationService->paginate();

        return view('invitations.index', compact('invitations'));
    }

    public function store(StoreInvitationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $invitation = $this->invitationService->create(
            $request->user(),
            (int) $data['hours'],
            isset($data['max_uses']) ? (int) $data['max_uses'] : null,
            $data['email'] ?? null,
            $data['note']  ?? null,
        );

        return response()->json([
            'success'    => true,
            'message'    => 'Invitation created.',
            'invitation' => $this->format($invitation->load('creator')),
        ], 201);
    }

    public function destroy(RegistrationInvitation $invitation): JsonResponse
    {
        $this->authorize('delete', $invitation);

        try {
            $this->invitationService->revoke($invitation);
            return response()->json(['success' => true, 'message' => 'Invitation revoked.']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => $e->errors()['invitation'][0]], 422);
        }
    }

    private function format(RegistrationInvitation $inv): array
    {
        $maxUses = $inv->max_uses;

        return [
            'id'          => $inv->id,
            'token'       => $inv->token,
            'email'       => $inv->email,
            'note'        => $inv->note,
            'status'      => $inv->status,
            'url'         => $inv->registration_url,
            'max_uses'    => $maxUses,
            'uses_count'  => $inv->uses_count,
            'uses_label'  => $inv->uses_label,
            'expires_at'  => $inv->expires_at->format('d M Y H:i'),
            'expires_in'  => $inv->expires_at->diffForHumans(),
            'used_at'     => $inv->used_at?->format('d M Y H:i'),
            'created_by'  => $inv->creator?->name ?? '—',
            'created_at'  => $inv->created_at->format('d M Y H:i'),
        ];
    }
}
