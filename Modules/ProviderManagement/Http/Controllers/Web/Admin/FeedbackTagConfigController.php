<?php

namespace Modules\ProviderManagement\Http\Controllers\Web\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Modules\ProviderManagement\Entities\FeedbackTagConfig;

class FeedbackTagConfigController extends Controller
{
    public function index(): Renderable
    {
        $configs = FeedbackTagConfig::query()
            ->orderBy('entity_type')
            ->orderBy('feedback_type')
            ->orderBy('label')
            ->get()
            ->groupBy(['entity_type', 'feedback_type']);

        return view('providermanagement::admin.feedback-tag-config.index', compact('configs'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rows' => ['nullable', 'array'],
            'rows.*.id' => ['nullable', 'integer', Rule::exists('feedback_tag_configs', 'id')],
            'rows.*.entity_type' => ['required', Rule::in(['provider', 'customer'])],
            'rows.*.feedback_type' => ['required', Rule::in(['complaint', 'positive_feedback', 'non_complaint'])],
            'rows.*.tag_key' => ['nullable', 'string', 'max:64'],
            'rows.*.label' => ['required', 'string', 'max:120'],
            'rows.*.score' => ['required', 'integer', 'min:-100', 'max:100'],
            'rows.*.is_active' => ['nullable', Rule::in(['0', '1'])],
            'deleted_ids' => ['nullable', 'array'],
            'deleted_ids.*' => ['integer', Rule::exists('feedback_tag_configs', 'id')],
        ]);

        if (!empty($validated['deleted_ids'])) {
            FeedbackTagConfig::query()
                ->whereIn('id', $validated['deleted_ids'])
                ->where('is_system', false)
                ->delete();
        }

        foreach ($validated['rows'] ?? [] as $row) {
            $tagKey = $this->normalizeFeedbackTagKey($row['tag_key'] ?? null, (string) $row['label']);
            $payload = [
                'entity_type' => $row['entity_type'],
                'feedback_type' => $row['feedback_type'],
                'tag_key' => $tagKey,
                'label' => trim((string) $row['label']),
                'score' => (int) $row['score'],
                'is_active' => (int) ($row['is_active'] ?? 0),
            ];

            if (!empty($row['id'])) {
                FeedbackTagConfig::query()
                    ->where('id', $row['id'])
                    ->update([
                        'label' => $payload['label'],
                        'score' => $payload['score'],
                        'is_active' => $payload['is_active'],
                    ]);
            } else {
                $model = FeedbackTagConfig::query()->firstOrNew([
                    'entity_type' => $payload['entity_type'],
                    'feedback_type' => $payload['feedback_type'],
                    'tag_key' => $payload['tag_key'],
                ]);
                $model->fill([
                    'label' => $payload['label'],
                    'score' => $payload['score'],
                    'is_active' => $payload['is_active'],
                ]);
                if (!$model->exists) {
                    $model->is_system = false;
                }
                $model->save();
            }
        }

        return back()->with('success', translate('Feedback tag scores updated successfully'));
    }

    /**
     * Stable machine id for feedback/incidents (see admin UI explainer). If empty, derived from label.
     */
    private function normalizeFeedbackTagKey(?string $tagKey, string $label): string
    {
        $label = trim($label);
        $raw = trim((string) $tagKey);
        $source = $raw !== '' ? $raw : $label;
        $key = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]+/', '_', $source), '_'));
        if ($key === '') {
            $key = 'tag_' . str_replace('.', '', uniqid('', true));
        }
        if (strlen($key) > 64) {
            $key = substr($key, 0, 64);
        }

        return $key;
    }
}

