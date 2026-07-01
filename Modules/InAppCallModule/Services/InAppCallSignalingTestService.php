<?php

namespace Modules\InAppCallModule\Services;

use Modules\ChattingModule\Entities\ChannelUser;
use Modules\InAppCallModule\Entities\InAppCall;
use Modules\InAppCallModule\Entities\InAppCallSignal;
use Modules\UserManagement\Entities\User;

class InAppCallSignalingTestService
{
    public function __construct(
        private readonly InAppCallService $inAppCallService,
    ) {}

    /**
     * Simulates the full HTTP-polling signaling path (no Soketi / no mobile apps).
     *
     * @return array{
     *     ok: bool,
     *     passed: int,
     *     failed: int,
     *     duration_ms: int,
     *     call_id: string|null,
     *     channel_id: string|null,
     *     customer_label: string|null,
     *     provider_label: string|null,
     *     steps: list<array{id: string, label: string, status: string, detail: string|null}>
     * }
     */
    public function run(): array
    {
        $started = microtime(true);
        $steps = [];
        $passed = 0;
        $failed = 0;
        $callId = null;
        $channelId = null;
        $customerLabel = null;
        $providerLabel = null;

        $record = function (string $id, string $label, bool $ok, ?string $detail = null) use (&$steps, &$passed, &$failed): void {
            $steps[] = [
                'id' => $id,
                'label' => $label,
                'status' => $ok ? 'pass' : 'fail',
                'detail' => $detail,
            ];
            if ($ok) {
                $passed++;
            } else {
                $failed++;
            }
        };

        if (! $this->inAppCallService->isEnabled()) {
            $record('feature', translate('In_app_calling_feature'), false, translate('IN_APP_CALL_ENABLED_is_false'));

            return $this->result($steps, $passed, $failed, $started, $callId, $channelId, $customerLabel, $providerLabel);
        }

        $record('feature', translate('In_app_calling_feature'), true, translate('In_app_calling_is_enabled'));

        $pair = $this->findCustomerProviderChannel();
        if ($pair === null) {
            $record('channel', translate('Signaling_test_channel'), false, translate('Signaling_test_no_channel'));

            return $this->result($steps, $passed, $failed, $started, $callId, $channelId, $customerLabel, $providerLabel);
        }

        /** @var User $customer */
        $customer = $pair['customer'];
        /** @var User $provider */
        $provider = $pair['provider'];
        $channelId = $pair['channel_id'];
        $customerLabel = trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));
        $providerLabel = $this->resolveUserLabel($provider);

        $record(
            'channel',
            translate('Signaling_test_channel'),
            true,
            $customerLabel.' ↔ '.$providerLabel,
        );

        $config = $this->inAppCallService->publicConfig();
        $wsEnabled = (bool) ($config['websocket']['enabled'] ?? false);
        $record(
            'websocket_mode',
            translate('Signaling_test_http_mode'),
            ! $wsEnabled,
            $wsEnabled
                ? translate('Signaling_test_websocket_still_enabled')
                : translate('Signaling_test_http_polling_mode'),
        );

        $iceServers = $config['ice_servers'] ?? [];
        $hasStun = $this->iceConfigHas($iceServers, 'stun:');
        $hasTurn = $this->iceConfigHas($iceServers, 'turn:');
        $record('stun', translate('STUN_server'), $hasStun, $hasStun ? translate('STUN_configured') : translate('No_STUN_server_configured'));
        $record('turn', translate('TURN_server'), $hasTurn, $hasTurn ? translate('TURN_credentials_configured') : translate('TURN_not_configured'));

        $init = $this->inAppCallService->initiate($customer, $channelId);
        if (! ($init['ok'] ?? false) || empty($init['data']['call_id'])) {
            $record('initiate', translate('Signaling_test_initiate'), false, $init['message'] ?? translate('Failed_to_start_call'));

            return $this->result($steps, $passed, $failed, $started, $callId, $channelId, $customerLabel, $providerLabel);
        }

        $callId = (string) $init['data']['call_id'];
        $record('initiate', translate('Signaling_test_initiate'), true, $callId);

        $pending = $this->inAppCallService->pendingIncoming($provider);
        $pendingId = $pending['data']['call_id'] ?? null;
        $record(
            'pending',
            translate('Signaling_test_pending'),
            $pendingId === $callId,
            $pendingId === $callId ? translate('Signaling_test_pending_ok') : translate('Signaling_test_pending_mismatch'),
        );

        $accept = $this->inAppCallService->accept($provider, $callId);
        $accepted = ($accept['ok'] ?? false) && (($accept['data']['status'] ?? '') === InAppCall::STATUS_ACCEPTED);
        $record(
            'accept',
            translate('Signaling_test_accept'),
            $accepted,
            $accepted ? translate('Signaling_test_accept_ok') : ($accept['message'] ?? translate('Failed_to_accept_call')),
        );

        if (! $accepted) {
            $this->safeEnd($customer, $callId);

            return $this->result($steps, $passed, $failed, $started, $callId, $channelId, $customerLabel, $providerLabel);
        }

        $show = $this->inAppCallService->show($customer, $callId);
        $customerSeesAccepted = ($show['data']['status'] ?? '') === InAppCall::STATUS_ACCEPTED;
        $record(
            'caller_status',
            translate('Signaling_test_caller_status'),
            $customerSeesAccepted,
            $customerSeesAccepted ? translate('Signaling_test_caller_accepted') : translate('Signaling_test_caller_not_accepted'),
        );

        $fakeOfferSdp = "v=0\r\no=- 0 0 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\nm=audio 9 UDP/TLS/RTP/SAVPF 111";
        $offerPost = $this->inAppCallService->postSignal($customer, $callId, InAppCallSignal::TYPE_OFFER, [
            'type' => 'offer',
            'sdp' => $fakeOfferSdp,
        ]);
        $record(
            'offer_post',
            translate('Signaling_test_offer_post'),
            $offerPost['ok'] ?? false,
            ($offerPost['ok'] ?? false) ? null : ($offerPost['message'] ?? translate('Failed_to_send_signal')),
        );

        $offerSignals = $this->inAppCallService->listSignals($provider, $callId);
        $offerFound = $this->signalsContainType($offerSignals['data'] ?? [], InAppCallSignal::TYPE_OFFER);
        $record(
            'offer_poll',
            translate('Signaling_test_offer_poll'),
            $offerFound,
            $offerFound ? translate('Signaling_test_offer_received') : translate('Signaling_test_offer_missing'),
        );

        $fakeAnswerSdp = "v=0\r\no=- 1 1 IN IP4 127.0.0.1\r\ns=-\r\nt=0 0\r\nm=audio 9 UDP/TLS/RTP/SAVPF 111";
        $answerPost = $this->inAppCallService->postSignal($provider, $callId, InAppCallSignal::TYPE_ANSWER, [
            'type' => 'answer',
            'sdp' => $fakeAnswerSdp,
        ]);
        $record(
            'answer_post',
            translate('Signaling_test_answer_post'),
            $answerPost['ok'] ?? false,
            ($answerPost['ok'] ?? false) ? null : ($answerPost['message'] ?? translate('Failed_to_send_signal')),
        );

        $answerSignals = $this->inAppCallService->listSignals($customer, $callId);
        $answerFound = $this->signalsContainType($answerSignals['data'] ?? [], InAppCallSignal::TYPE_ANSWER);
        $record(
            'answer_poll',
            translate('Signaling_test_answer_poll'),
            $answerFound,
            $answerFound ? translate('Signaling_test_answer_received') : translate('Signaling_test_answer_missing'),
        );

        $icePost = $this->inAppCallService->postSignal($customer, $callId, InAppCallSignal::TYPE_ICE, [
            'candidates' => [
                [
                    'candidate' => 'candidate:1 1 udp 2122260223 192.168.1.10 54321 typ host',
                    'sdpMid' => '0',
                    'sdpMLineIndex' => 0,
                ],
                [
                    'candidate' => 'candidate:2 1 udp 1686052607 203.0.113.5 54321 typ srflx raddr 192.168.1.10 rport 54321',
                    'sdpMid' => '0',
                    'sdpMLineIndex' => 0,
                ],
            ],
        ]);
        $record(
            'ice_post',
            translate('Signaling_test_ice_post'),
            $icePost['ok'] ?? false,
            ($icePost['ok'] ?? false) ? null : ($icePost['message'] ?? translate('Failed_to_send_signal')),
        );

        $iceSignals = $this->inAppCallService->listSignals($provider, $callId);
        $iceCount = $this->countIceSignals($iceSignals['data'] ?? []);
        $dbIceCount = InAppCallSignal::query()
            ->where('call_id', $callId)
            ->where('signal_type', InAppCallSignal::TYPE_ICE)
            ->count();

        $record(
            'ice_poll',
            translate('Signaling_test_ice_poll'),
            $iceCount >= 2,
            translate('Signaling_test_ice_count', ['count' => $iceCount]),
        );
        $record(
            'ice_db',
            translate('Signaling_test_ice_db'),
            $dbIceCount >= 2,
            translate('Signaling_test_ice_db_count', ['count' => $dbIceCount]),
        );

        $end = $this->inAppCallService->end($customer, $callId);
        $endedCall = InAppCall::query()->find($callId);
        $endedOk = ($end['ok'] ?? false) && $endedCall && $endedCall->status === InAppCall::STATUS_ENDED;
        $record(
            'cleanup',
            translate('Signaling_test_cleanup'),
            $endedOk,
            $endedOk ? translate('Signaling_test_cleanup_ok') : translate('Signaling_test_cleanup_failed'),
        );

        return $this->result($steps, $passed, $failed, $started, $callId, $channelId, $customerLabel, $providerLabel);
    }

    /**
     * @param  list<array{id: string, label: string, status: string, detail: string|null}>  $steps
     * @return array{
     *     ok: bool,
     *     passed: int,
     *     failed: int,
     *     duration_ms: int,
     *     call_id: string|null,
     *     channel_id: string|null,
     *     customer_label: string|null,
     *     provider_label: string|null,
     *     steps: list<array{id: string, label: string, status: string, detail: string|null}>
     * }
     */
    private function result(
        array $steps,
        int $passed,
        int $failed,
        float $started,
        ?string $callId,
        ?string $channelId,
        ?string $customerLabel,
        ?string $providerLabel,
    ): array {
        return [
            'ok' => $failed === 0,
            'passed' => $passed,
            'failed' => $failed,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'call_id' => $callId,
            'channel_id' => $channelId,
            'customer_label' => $customerLabel,
            'provider_label' => $providerLabel,
            'steps' => $steps,
        ];
    }

    /**
     * @return array{channel_id: string, customer: User, provider: User}|null
     */
    private function findCustomerProviderChannel(): ?array
    {
        $channelIds = ChannelUser::query()
            ->select('channel_id')
            ->groupBy('channel_id')
            ->havingRaw('COUNT(*) = 2')
            ->pluck('channel_id');

        foreach ($channelIds as $channelId) {
            $users = ChannelUser::query()
                ->with('user')
                ->where('channel_id', $channelId)
                ->get()
                ->map(fn ($row) => $row->user)
                ->filter();

            if ($users->count() !== 2) {
                continue;
            }

            $customer = $users->first(fn (User $user) => $user->user_type === 'customer');
            $provider = $users->first(fn (User $user) => $user->user_type === 'provider-admin');

            if ($customer instanceof User && $provider instanceof User) {
                return [
                    'channel_id' => (string) $channelId,
                    'customer' => $customer,
                    'provider' => $provider,
                ];
            }
        }

        return null;
    }

    private function resolveUserLabel(User $user): string
    {
        $user->loadMissing('provider');
        if ($user->user_type === 'provider-admin' && ! empty($user->provider?->company_name)) {
            return (string) $user->provider->company_name;
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name : (string) $user->id;
    }

    /**
     * @param  array<int, array<string, mixed>>  $iceServers
     */
    private function iceConfigHas(array $iceServers, string $needle): bool
    {
        foreach ($iceServers as $server) {
            $urls = $server['urls'] ?? '';
            $urlStr = is_array($urls) ? implode(',', $urls) : (string) $urls;
            if (str_contains($urlStr, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $signals
     */
    private function signalsContainType(array $signals, string $type): bool
    {
        foreach ($signals as $signal) {
            if (($signal['signal_type'] ?? '') === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $signals
     */
    private function countIceSignals(array $signals): int
    {
        $count = 0;
        foreach ($signals as $signal) {
            if (($signal['signal_type'] ?? '') === InAppCallSignal::TYPE_ICE
                && ! empty($signal['payload']['candidate'])) {
                $count++;
            }
        }

        return $count;
    }

    private function safeEnd(User $user, string $callId): void
    {
        try {
            $this->inAppCallService->end($user, $callId);
        } catch (\Throwable) {
            try {
                $this->inAppCallService->cancel($user, $callId);
            } catch (\Throwable) {
            }
        }
    }
}
