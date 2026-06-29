<?php

namespace Modules\InAppCallModule\Services;

use Illuminate\Support\Str;
use Modules\ChattingModule\Entities\ChannelUser;
use Modules\InAppCallModule\Entities\InAppCall;
use Modules\InAppCallModule\Entities\InAppCallSignal;
use Modules\UserManagement\Entities\User;
use Ramsey\Uuid\Uuid;

class InAppCallService
{
    public function isEnabled(): bool
    {
        return (bool) config('inappcallmodule.enabled', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function publicConfig(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'ice_servers' => config('inappcallmodule.ice_servers', []),
            'ring_timeout_seconds' => (int) config('inappcallmodule.ring_timeout_seconds', 60),
        ];
    }

    /**
     * @return array{ok: bool, message?: string, data?: array<string, mixed>}
     */
    public function initiate(User $caller, string $channelId): array
    {
        if (! $this->isEnabled()) {
            return ['ok' => false, 'message' => translate('In_app_calling_is_not_configured')];
        }

        $membership = ChannelUser::query()
            ->where('channel_id', $channelId)
            ->where('user_id', $caller->id)
            ->exists();

        if (! $membership) {
            return ['ok' => false, 'message' => translate('Invalid_channel')];
        }

        $participants = ChannelUser::query()
            ->with('user')
            ->where('channel_id', $channelId)
            ->where('user_id', '!=', $caller->id)
            ->get();

        if ($participants->count() !== 1) {
            return ['ok' => false, 'message' => translate('Call_is_only_available_for_direct_conversations')];
        }

        $callee = $participants->first()?->user;
        if (! $callee instanceof User) {
            return ['ok' => false, 'message' => translate('Callee_not_found')];
        }

        if (! $this->isAllowedParticipantPair($caller, $callee)) {
            return ['ok' => false, 'message' => translate('Call_is_not_allowed_for_this_conversation')];
        }

        $activeCall = InAppCall::query()
            ->where('channel_id', $channelId)
            ->whereIn('status', [InAppCall::STATUS_RINGING, InAppCall::STATUS_ACCEPTED])
            ->latest()
            ->first();

        if ($activeCall) {
            return ['ok' => false, 'message' => translate('A_call_is_already_in_progress')];
        }

        $callId = (string) Uuid::uuid4();
        $channel = \Modules\ChattingModule\Entities\ChannelList::query()->find($channelId);

        $call = InAppCall::query()->create([
            'id' => $callId,
            'channel_id' => $channelId,
            'caller_user_id' => $caller->id,
            'callee_user_id' => $callee->id,
            'agora_channel_name' => 'pk_call_'.Str::replace('-', '', $callId),
            'status' => InAppCall::STATUS_RINGING,
            'reference_id' => $channel?->reference_id,
            'reference_type' => $channel?->reference_type,
            'started_at' => now(),
        ]);

        if (function_exists('send_in_app_call_push_notification')) {
            send_in_app_call_push_notification($callee, $call, $caller);
        }

        return [
            'ok' => true,
            'data' => $this->serializeCall($call->fresh(['caller', 'callee']), $caller),
        ];
    }

    /**
     * @return array{ok: bool, message?: string, data?: array<string, mixed>}
     */
    public function accept(User $user, string $callId): array
    {
        $call = $this->findParticipantCall($user, $callId);
        if ($call === null) {
            return ['ok' => false, 'message' => translate('Call_not_found')];
        }

        if ($call->callee_user_id !== $user->id) {
            return ['ok' => false, 'message' => translate('Only_the_callee_can_accept_this_call')];
        }

        if ($call->status !== InAppCall::STATUS_RINGING) {
            return ['ok' => false, 'message' => translate('Call_is_no_longer_ringing')];
        }

        $call->update([
            'status' => InAppCall::STATUS_ACCEPTED,
            'answered_at' => now(),
        ]);

        if (function_exists('send_in_app_call_status_push_notification')) {
            send_in_app_call_status_push_notification($call->caller, $call, 'call_accepted');
        }

        return [
            'ok' => true,
            'data' => $this->serializeCall($call->fresh(['caller', 'callee']), $user),
        ];
    }

    /**
     * @return array{ok: bool, message?: string, data?: array<string, mixed>}
     */
    public function decline(User $user, string $callId): array
    {
        $call = $this->findParticipantCall($user, $callId);
        if ($call === null) {
            return ['ok' => false, 'message' => translate('Call_not_found')];
        }

        if ($call->callee_user_id !== $user->id) {
            return ['ok' => false, 'message' => translate('Only_the_callee_can_decline_this_call')];
        }

        if ($call->isTerminal()) {
            return ['ok' => true, 'data' => $this->serializeCall($call, $user)];
        }

        $call->update([
            'status' => InAppCall::STATUS_DECLINED,
            'ended_at' => now(),
            'end_reason' => 'declined',
        ]);

        if (function_exists('send_in_app_call_status_push_notification')) {
            send_in_app_call_status_push_notification($call->caller, $call->fresh(), 'call_declined');
        }

        return [
            'ok' => true,
            'data' => $this->serializeCall($call->fresh(['caller', 'callee']), $user),
        ];
    }

    /**
     * @return array{ok: bool, message?: string, data?: array<string, mixed>}
     */
    public function cancel(User $user, string $callId): array
    {
        $call = $this->findParticipantCall($user, $callId);
        if ($call === null) {
            return ['ok' => false, 'message' => translate('Call_not_found')];
        }

        if ($call->caller_user_id !== $user->id) {
            return ['ok' => false, 'message' => translate('Only_the_caller_can_cancel_this_call')];
        }

        if ($call->isTerminal()) {
            return ['ok' => true, 'data' => $this->serializeCall($call, $user)];
        }

        $status = $call->status === InAppCall::STATUS_ACCEPTED
            ? InAppCall::STATUS_ENDED
            : InAppCall::STATUS_CANCELLED;

        $call->update([
            'status' => $status,
            'ended_at' => now(),
            'end_reason' => 'cancelled',
        ]);

        if ($call->callee && function_exists('send_in_app_call_status_push_notification')) {
            send_in_app_call_status_push_notification(
                $call->callee,
                $call->fresh(),
                $status === InAppCall::STATUS_ENDED ? 'call_ended' : 'call_cancelled',
            );
        }

        return [
            'ok' => true,
            'data' => $this->serializeCall($call->fresh(['caller', 'callee']), $user),
        ];
    }

    /**
     * @return array{ok: bool, message?: string, data?: array<string, mixed>}
     */
    public function end(User $user, string $callId): array
    {
        $call = $this->findParticipantCall($user, $callId);
        if ($call === null) {
            return ['ok' => false, 'message' => translate('Call_not_found')];
        }

        if ($call->isTerminal()) {
            return ['ok' => true, 'data' => $this->serializeCall($call, $user)];
        }

        $answeredAt = $call->answered_at;
        $duration = $answeredAt ? max(0, now()->diffInSeconds($answeredAt)) : 0;

        $call->update([
            'status' => InAppCall::STATUS_ENDED,
            'ended_at' => now(),
            'duration_seconds' => $duration,
            'end_reason' => 'ended',
        ]);

        $otherUser = $call->caller_user_id === $user->id ? $call->callee : $call->caller;
        if ($otherUser && function_exists('send_in_app_call_status_push_notification')) {
            send_in_app_call_status_push_notification($otherUser, $call->fresh(), 'call_ended');
        }

        return [
            'ok' => true,
            'data' => $this->serializeCall($call->fresh(['caller', 'callee']), $user),
        ];
    }

    /**
     * @return array{ok: bool, data: array<string, mixed>}
     */
    public function history(User $user, int $limit, int $offset, ?string $channelId = null): array
    {
        $query = InAppCall::query()
            ->where(function ($query) use ($user) {
                $query->where('caller_user_id', $user->id)
                    ->orWhere('callee_user_id', $user->id);
            })
            ->with(['caller', 'callee'])
            ->orderByDesc('created_at');

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        $paginator = $query->paginate($limit, ['*'], 'offset', $offset)->withPath('');

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn (InAppCall $call) => $this->serializeCall($call, $user),
            ),
        );

        return ['ok' => true, 'data' => $paginator];
    }

    /**
     * @return array{ok: bool, message?: string, data?: array<string, mixed>}
     */
    public function show(User $user, string $callId): array
    {
        $call = $this->findParticipantCall($user, $callId);
        if ($call === null) {
            return ['ok' => false, 'message' => translate('Call_not_found')];
        }

        return [
            'ok' => true,
            'data' => $this->serializeCall($call->loadMissing(['caller', 'callee']), $user),
        ];
    }

    /**
     * @return array{ok: bool, data: array<string, mixed>}
     */
    public function markMissedIfRinging(string $callId): array
    {
        $call = InAppCall::query()->find($callId);
        if (! $call || $call->status !== InAppCall::STATUS_RINGING) {
            return ['ok' => false, 'data' => []];
        }

        $call->update([
            'status' => InAppCall::STATUS_MISSED,
            'ended_at' => now(),
            'end_reason' => 'timeout',
        ]);

        if (function_exists('send_in_app_call_status_push_notification')) {
            send_in_app_call_status_push_notification($call->caller, $call->fresh(), 'call_missed');
        }

        return ['ok' => true, 'data' => $this->serializeCall($call->fresh(['caller', 'callee']), $call->caller)];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, message?: string, data?: array<string, mixed>}
     */
    public function postSignal(User $user, string $callId, string $signalType, array $payload): array
    {
        $call = $this->findParticipantCall($user, $callId);
        if ($call === null) {
            return ['ok' => false, 'message' => translate('Call_not_found')];
        }

        if ($call->isTerminal()) {
            return ['ok' => false, 'message' => translate('Call_has_ended')];
        }

        if (! in_array($signalType, [InAppCallSignal::TYPE_OFFER, InAppCallSignal::TYPE_ANSWER, InAppCallSignal::TYPE_ICE], true)) {
            return ['ok' => false, 'message' => translate('Invalid_signal_type')];
        }

        InAppCallSignal::query()->create([
            'id' => (string) Uuid::uuid4(),
            'call_id' => $call->id,
            'sender_user_id' => $user->id,
            'signal_type' => $signalType,
            'payload' => $payload,
        ]);

        return ['ok' => true, 'data' => ['stored' => true]];
    }

    /**
     * @return array{ok: bool, message?: string, data?: array<int, array<string, mixed>>}
     */
    public function listSignals(User $user, string $callId, ?string $after = null): array
    {
        $call = $this->findParticipantCall($user, $callId);
        if ($call === null) {
            return ['ok' => false, 'message' => translate('Call_not_found')];
        }

        $query = InAppCallSignal::query()
            ->where('call_id', $call->id)
            ->where('sender_user_id', '!=', $user->id)
            ->orderBy('created_at');

        if ($after) {
            $query->where('created_at', '>', $after);
        }

        $signals = $query->get()->map(fn (InAppCallSignal $signal) => [
            'id' => $signal->id,
            'signal_type' => $signal->signal_type,
            'payload' => $signal->payload,
            'sender_user_id' => $signal->sender_user_id,
            'created_at' => $signal->created_at?->toIso8601String(),
        ])->values()->all();

        return ['ok' => true, 'data' => $signals];
    }

    /**
     * @return array{ok: bool, data: array<string, mixed>}
     */
    public function listHistory(User $user, int $limit, int $offset): array
    {
        $paginator = InAppCall::query()
            ->with(['caller.provider', 'callee.provider'])
            ->where(function ($query) use ($user) {
                $query->where('caller_user_id', $user->id)
                    ->orWhere('callee_user_id', $user->id);
            })
            ->orderByDesc('started_at')
            ->orderByDesc('created_at')
            ->paginate($limit, ['*'], 'offset', $offset)
            ->withPath('');

        $items = collect($paginator->items())
            ->map(fn (InAppCall $call) => $this->serializeHistoryItem($call, $user))
            ->values()
            ->all();

        return [
            'ok' => true,
            'data' => [
                'data' => $items,
                'total_size' => $paginator->total(),
                'limit' => $paginator->perPage(),
                'offset' => $paginator->currentPage(),
            ],
        ];
    }

    protected function findParticipantCall(User $user, string $callId): ?InAppCall
    {
        return InAppCall::query()
            ->where('id', $callId)
            ->where(function ($query) use ($user) {
                $query->where('caller_user_id', $user->id)
                    ->orWhere('callee_user_id', $user->id);
            })
            ->first();
    }

    protected function isAllowedParticipantPair(User $a, User $b): bool
    {
        if ($a->id === $b->id) {
            return false;
        }

        $types = [$a->user_type, $b->user_type];
        $hasCustomer = in_array('customer', $types, true);
        $hasProvider = count(array_intersect($types, PROVIDER_USER_TYPES)) > 0;

        return $hasCustomer && $hasProvider;
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeCall(InAppCall $call, User $viewer): array
    {
        $caller = $call->caller;
        $callee = $call->callee;
        $peer = $call->caller_user_id === $viewer->id ? $callee : $caller;

        return [
            'call_id' => $call->id,
            'channel_id' => $call->channel_id,
            'status' => $call->status,
            'is_caller' => $call->caller_user_id === $viewer->id,
            'ice_servers' => config('inappcallmodule.ice_servers', []),
            'reference_id' => $call->reference_id,
            'reference_type' => $call->reference_type,
            'started_at' => optional($call->started_at)?->toIso8601String(),
            'answered_at' => optional($call->answered_at)?->toIso8601String(),
            'ended_at' => optional($call->ended_at)?->toIso8601String(),
            'duration_seconds' => $call->duration_seconds,
            'end_reason' => $call->end_reason,
            'peer' => $peer ? [
                'id' => $peer->id,
                'name' => trim(($peer->first_name ?? '').' '.($peer->last_name ?? '')),
                'image' => $peer->profile_image,
                'phone' => $peer->phone,
                'user_type' => $peer->user_type,
            ] : null,
            'caller' => $caller ? [
                'id' => $caller->id,
                'name' => trim(($caller->first_name ?? '').' '.($caller->last_name ?? '')),
                'image' => $caller->profile_image,
                'user_type' => $caller->user_type,
            ] : null,
            'callee' => $callee ? [
                'id' => $callee->id,
                'name' => trim(($callee->first_name ?? '').' '.($callee->last_name ?? '')),
                'image' => $callee->profile_image,
                'user_type' => $callee->user_type,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeHistoryItem(InAppCall $call, User $viewer): array
    {
        $isOutbound = $call->caller_user_id === $viewer->id;
        $peer = $isOutbound ? $call->callee : $call->caller;

        return [
            'call_id' => $call->id,
            'channel_id' => $call->channel_id,
            'status' => $call->status,
            'direction' => $isOutbound ? 'outbound' : 'inbound',
            'duration_seconds' => $call->duration_seconds,
            'started_at' => optional($call->started_at)?->toIso8601String(),
            'answered_at' => optional($call->answered_at)?->toIso8601String(),
            'ended_at' => optional($call->ended_at)?->toIso8601String(),
            'end_reason' => $call->end_reason,
            'peer' => $peer ? [
                'id' => $peer->id,
                'name' => $this->resolveUserDisplayName($peer),
                'image' => $this->resolveUserImagePath($peer),
                'phone' => $peer->phone,
                'user_type' => $peer->user_type,
            ] : null,
        ];
    }

    protected function resolveUserDisplayName(User $user): string
    {
        $user->loadMissing('provider');

        if ($user->user_type === 'provider-admin' && ! empty($user->provider?->company_name)) {
            return (string) $user->provider->company_name;
        }

        return trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
    }

    protected function resolveUserImagePath(User $user): ?string
    {
        $user->loadMissing('provider');

        if ($user->user_type === 'provider-admin' && ! empty($user->provider?->logo)) {
            return asset('storage/app/public/provider/logo').'/'.$user->provider->logo;
        }

        if (! empty($user->profile_image)) {
            return asset('storage/app/public/user/profile_image').'/'.$user->profile_image;
        }

        return null;
    }
}
