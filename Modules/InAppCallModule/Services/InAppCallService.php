<?php

namespace Modules\InAppCallModule\Services;

use Illuminate\Support\Str;
use Modules\ChattingModule\Entities\ChannelUser;
use Modules\InAppCallModule\Entities\InAppCall;
use Modules\InAppCallModule\Entities\InAppCallSignal;
use Modules\UserManagement\Entities\Serviceman;
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

        $channel = \Modules\ChattingModule\Entities\ChannelList::query()->find($channelId);

        if ($this->isBlockedCallChannel($channel)) {
            return ['ok' => false, 'message' => translate('Call_is_not_allowed_for_this_conversation')];
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
            'data' => $this->serializeCall($call->fresh(['caller.provider', 'callee.provider']), $caller),
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

        if ((string) $call->callee_user_id !== (string) $user->id) {
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

        if ((string) $call->callee_user_id !== (string) $user->id) {
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

        if ((string) $call->caller_user_id !== (string) $user->id) {
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

        $otherUser = (string) $call->caller_user_id === (string) $user->id ? $call->callee : $call->caller;
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
    public function listHistory(User $user, int $limit, int $offset, ?string $channelId = null): array
    {
        $query = InAppCall::query()
            ->with(['caller.provider', 'callee.provider'])
            ->where(function ($query) use ($user) {
                $query->where('caller_user_id', $user->id)
                    ->orWhere('callee_user_id', $user->id);
            })
            ->orderByDesc('started_at')
            ->orderByDesc('created_at');

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        $paginator = $query
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

    /**
     * @return array{ok: bool, data: array<string, mixed>|null}
     */
    public function pendingIncoming(User $user): array
    {
        $ringTimeout = max(30, (int) config('inappcallmodule.ring_timeout_seconds', 60));
        $since = now()->subSeconds($ringTimeout + 15);
        $userId = (string) $user->id;
        $calleeIds = $this->ringingCalleeUserIdsFor($user);

        $channelIds = ChannelUser::query()
            ->where('user_id', $userId)
            ->pluck('channel_id');

        $call = InAppCall::query()
            ->with(['caller.provider', 'callee.provider'])
            ->where('status', InAppCall::STATUS_RINGING)
            ->where('started_at', '>=', $since)
            ->where(function ($query) use ($userId, $calleeIds, $channelIds) {
                $query->whereIn('callee_user_id', $calleeIds);

                if ($channelIds->isNotEmpty()) {
                    $query->orWhere(function ($nested) use ($userId, $channelIds) {
                        $nested->whereIn('channel_id', $channelIds)
                            ->where('caller_user_id', '!=', $userId);
                    });
                }
            })
            ->latest('started_at')
            ->first();

        return [
            'ok' => true,
            'data' => $call
                ? $this->safeSerializeCall($call, $user)
                : null,
        ];
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
            'data' => $this->serializeCall($call->loadMissing(['caller.provider', 'callee.provider']), $user),
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

        $viewer = $call->caller ?? $call->callee;
        if (! $viewer instanceof User) {
            return ['ok' => true, 'data' => ['call_id' => (string) $call->id, 'status' => (string) $call->status]];
        }

        return ['ok' => true, 'data' => $this->safeSerializeCall($call->fresh(['caller.provider', 'callee.provider']), $viewer)];
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

    protected function findParticipantCall(User $user, string $callId): ?InAppCall
    {
        $userId = (string) $user->id;

        return InAppCall::query()
            ->where('id', $callId)
            ->where(function ($query) use ($userId) {
                $query->where('caller_user_id', $userId)
                    ->orWhere('callee_user_id', $userId);
            })
            ->first();
    }

    /**
     * @return list<string>
     */
    protected function ringingCalleeUserIdsFor(User $user): array
    {
        $ids = [(string) $user->id];

        if ($user->user_type === 'provider-admin') {
            $user->loadMissing('provider');
            if ($user->provider) {
                $servicemanIds = Serviceman::query()
                    ->where('provider_id', $user->provider->id)
                    ->pluck('user_id')
                    ->map(static fn ($id) => (string) $id)
                    ->all();
                $ids = array_merge($ids, $servicemanIds);
            }
        }

        return array_values(array_unique($ids));
    }

    protected function isBlockedCallChannel(?\Modules\ChattingModule\Entities\ChannelList $channel): bool
    {
        if ($channel === null) {
            return false;
        }

        $blockedReferenceTypes = ['support', 'staff_group'];

        return in_array((string) $channel->reference_type, $blockedReferenceTypes, true);
    }

    protected function isAllowedParticipantPair(User $a, User $b): bool
    {
        if ($a->id === $b->id) {
            return false;
        }

        if ($this->isAdminChatUser($a) || $this->isAdminChatUser($b)) {
            return false;
        }

        $aCustomerParty = $this->isCustomerParty($a);
        $bCustomerParty = $this->isCustomerParty($b);
        $aProviderParty = $this->isProviderParty($a);
        $bProviderParty = $this->isProviderParty($b);

        return ($aCustomerParty && $bProviderParty) || ($bCustomerParty && $aProviderParty);
    }

    protected function isAdminChatUser(User $user): bool
    {
        return in_array($user->user_type, ADMIN_USER_TYPES, true);
    }

    protected function isCustomerParty(User $user): bool
    {
        if (in_array($user->user_type, CUSTOMER_USER_TYPES, true)) {
            return true;
        }

        return function_exists('user_can_use_customer_app')
            ? user_can_use_customer_app($user)
            : false;
    }

    protected function isProviderParty(User $user): bool
    {
        return in_array($user->user_type, PROVIDER_USER_TYPES, true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeCall(InAppCall $call, User $viewer): array
    {
        $caller = $call->caller;
        $callee = $call->callee;
        $peer = (string) $call->caller_user_id === (string) $viewer->id ? $callee : $caller;

        return [
            'call_id' => $call->id,
            'channel_id' => $call->channel_id,
            'status' => $call->status,
            'is_caller' => (string) $call->caller_user_id === (string) $viewer->id,
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
                'name' => $this->resolveUserDisplayName($peer),
                'image' => $this->resolveUserImagePath($peer),
                'phone' => $peer->phone,
                'user_type' => $peer->user_type,
            ] : null,
            'caller' => $caller ? [
                'id' => $caller->id,
                'name' => $this->resolveUserDisplayName($caller),
                'image' => $this->resolveUserImagePath($caller),
                'user_type' => $caller->user_type,
            ] : null,
            'callee' => $callee ? [
                'id' => $callee->id,
                'name' => $this->resolveUserDisplayName($callee),
                'image' => $this->resolveUserImagePath($callee),
                'user_type' => $callee->user_type,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function serializeHistoryItem(InAppCall $call, User $viewer): array
    {
        $isOutbound = (string) $call->caller_user_id === (string) $viewer->id;
        $peer = $isOutbound ? $call->callee : $call->caller;

        return [
            'call_id' => $call->id,
            'channel_id' => $call->channel_id,
            'status' => $call->status,
            'direction' => $isOutbound ? 'outbound' : 'inbound',
            'is_caller' => $isOutbound,
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
        try {
            $user->loadMissing('provider');

            if ($user->user_type === 'provider-admin' && ! empty($user->provider?->company_name)) {
                return (string) $user->provider->company_name;
            }

            $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

            return $name !== '' ? $name : translate('Someone_is_calling_you');
        } catch (\Throwable) {
            return translate('Someone_is_calling_you');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function safeSerializeCall(InAppCall $call, User $viewer): array
    {
        try {
            return $this->serializeCall($call, $viewer);
        } catch (\Throwable $e) {
            report($e);

            $peerUser = (string) $call->caller_user_id === (string) $viewer->id
                ? $call->callee
                : $call->caller;

            return [
                'call_id' => (string) $call->id,
                'channel_id' => (string) $call->channel_id,
                'status' => (string) $call->status,
                'is_caller' => (string) $call->caller_user_id === (string) $viewer->id,
                'ice_servers' => config('inappcallmodule.ice_servers', []),
                'reference_id' => $call->reference_id,
                'reference_type' => $call->reference_type,
                'started_at' => optional($call->started_at)?->toIso8601String(),
                'peer' => $peerUser instanceof User ? [
                    'id' => (string) $peerUser->id,
                    'name' => $this->resolveUserDisplayName($peerUser),
                    'image' => $this->resolveUserImagePath($peerUser),
                    'phone' => $peerUser->phone,
                    'user_type' => $peerUser->user_type,
                ] : null,
            ];
        }
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
