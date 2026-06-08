<?php

namespace Modules\LeadManagement\Entities;

use Illuminate\Database\Eloquent\Model;

class OmniDimensionCallTranscriptTransliteration extends Model
{
    protected $table = 'omnidimension_call_transcript_transliterations';

    protected $fillable = [
        'omnidim_call_log_id',
        'transcript_hash',
        'transliterated_transcript',
    ];

    public static function transcriptHash(string $transcript): string
    {
        return hash('sha256', trim($transcript));
    }

    public static function findForCall(int $callLogId, string $transcript): ?self
    {
        if ($callLogId <= 0 || trim($transcript) === '') {
            return null;
        }

        $row = static::query()
            ->where('omnidim_call_log_id', $callLogId)
            ->first();

        if ($row === null) {
            return null;
        }

        if (!hash_equals($row->transcript_hash, static::transcriptHash($transcript))) {
            return null;
        }

        return $row;
    }

    public static function storeForCall(int $callLogId, string $transcript, string $transliterated): void
    {
        if ($callLogId <= 0 || trim($transcript) === '' || trim($transliterated) === '') {
            return;
        }

        static::query()->updateOrCreate(
            ['omnidim_call_log_id' => $callLogId],
            [
                'transcript_hash' => static::transcriptHash($transcript),
                'transliterated_transcript' => trim($transliterated),
            ]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $callLogs
     * @return array<int, array<string, mixed>>
     */
    public static function attachToCallLogs(array $callLogs): array
    {
        $ids = array_values(array_filter(array_map(
            static fn (array $call): int => (int) ($call['id'] ?? 0),
            $callLogs
        )));

        if ($ids === []) {
            return $callLogs;
        }

        $rows = static::query()
            ->whereIn('omnidim_call_log_id', $ids)
            ->get()
            ->keyBy('omnidim_call_log_id');

        foreach ($callLogs as $index => $call) {
            $callLogId = (int) ($call['id'] ?? 0);
            $transcript = trim((string) ($call['transcript'] ?? ''));
            $row = $rows->get($callLogId);

            $callLogs[$index]['transcript_transliterated'] = ($row !== null
                && hash_equals($row->transcript_hash, static::transcriptHash($transcript)))
                ? (string) $row->transliterated_transcript
                : '';
        }

        return $callLogs;
    }
}
