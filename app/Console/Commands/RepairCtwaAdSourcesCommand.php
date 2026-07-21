<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\LeadManagement\Entities\AdSource;
use Modules\LeadManagement\Entities\Lead;
use Modules\LeadManagement\Entities\Source;
use Modules\WhatsAppModule\Entities\WhatsAppUser;
use Modules\WhatsAppModule\Services\WhatsAppCtwaAttributionService;
use Modules\WhatsAppModule\Support\SocialInboxChannel;

/**
 * Rebuild CTWA Ad Source / Source labels from stored Meta referral JSON
 * (fixes bad names like "api.whatsapp.com" and missing creative images).
 */
class RepairCtwaAdSourcesCommand extends Command
{
    protected $signature = 'whatsapp:repair-ctwa-ad-sources {--dry-run : Show changes without saving}';

    protected $description = 'Repair CTWA lead Source/Ad Source from stored WhatsApp referral data';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $fixedUsers = 0;
        $fixedLeads = 0;

        $users = WhatsAppUser::query()
            ->where(function ($q) {
                $q->whereNotNull('referral_json')
                    ->orWhereNotNull('ctwa_clid')
                    ->orWhereNotNull('referral_source_id');
            })
            ->orderBy('id')
            ->get();

        $this->info('CTWA threads: '.$users->count().($dry ? ' (dry-run)' : ''));

        foreach ($users as $waUser) {
            $json = is_array($waUser->referral_json) ? $waUser->referral_json : [];
            if ($json === [] && empty($waUser->referral_source_id) && empty($waUser->ctwa_clid)) {
                continue;
            }

            $imageUrl = AdSource::creativeImageUrlFromReferral($json);
            $platform = WhatsAppCtwaAttributionService::detectPlatform($waUser->referral_source_url, $json);
            $source = Source::ensureCtwaPlatformSource($platform);
            $ad = AdSource::ensureFromCtwaReferral(
                $waUser->referral_source_id,
                $waUser->referral_headline,
                $waUser->referral_source_url,
                $waUser->referral_source_type,
                $waUser->referral_body,
                $imageUrl,
                $json
            );

            if (!$ad) {
                continue;
            }

            $fixedUsers++;
            $this->line(sprintf(
                '  phone=%s platform=%s ad_source=#%d name=%s image=%s',
                $waUser->phone,
                $platform,
                $ad->id,
                $ad->name,
                $ad->image ? 'yes' : 'no'
            ));

            if ($dry) {
                continue;
            }

            $digits = preg_replace('/\D+/', '', (string) $waUser->phone) ?? '';
            $leadPhone = strlen($digits) >= 10 ? substr($digits, -10) : null;
            if (!$leadPhone) {
                continue;
            }

            $leads = Lead::query()
                ->where('phone_number', $leadPhone)
                ->orderByDesc('id')
                ->limit(5)
                ->get();

            foreach ($leads as $lead) {
                $dirty = false;
                $aiId = Source::ensureAiChatSource()->id;
                $ctwaIds = [
                    Source::ensureCtwaPlatformSource('facebook')->id,
                    Source::ensureCtwaPlatformSource('instagram')->id,
                    Source::ensureCtwaPlatformSource('whatsapp')->id,
                ];
                if ($lead->source_id === null
                    || (int) $lead->source_id === (int) $aiId
                    || in_array((int) $lead->source_id, array_map('intval', $ctwaIds), true)
                ) {
                    if ((int) $lead->source_id !== (int) $source->id) {
                        $lead->source_id = $source->id;
                        $dirty = true;
                    }
                }
                $currentAd = $lead->ad_source_id ? AdSource::query()->find($lead->ad_source_id) : null;
                $shouldRetarget = $lead->ad_source_id === null
                    || ($currentAd && AdSource::isBadAdName($currentAd->name))
                    || ($currentAd && $waUser->referral_source_id
                        && (string) ($currentAd->meta_ad_id ?? '') !== (string) $waUser->referral_source_id
                        && !str_contains((string) ($currentAd->description ?? ''), 'meta_source_id='.$waUser->referral_source_id));
                if ($shouldRetarget && (int) ($lead->ad_source_id ?? 0) !== (int) $ad->id) {
                    $lead->ad_source_id = $ad->id;
                    $dirty = true;
                }
                if ($dirty) {
                    $lead->save();
                    $fixedLeads++;
                }
            }
        }

        // Also rename any leftover bad Ad Source rows that still have meta_source_id in description.
        $bad = AdSource::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(TRIM(name)) = ?', ['api.whatsapp.com'])
                    ->orWhere('name', 'like', '%://%')
                    ->orWhereRaw('LOWER(TRIM(name)) = ?', ['whatsapp.com']);
            })
            ->get();

        foreach ($bad as $row) {
            $desc = (string) ($row->description ?? '');
            if (!preg_match('/meta_source_id=([^\s]+)/', $desc, $m)) {
                $this->warn('Bad Ad Source #'.$row->id.' name='.$row->name.' (no meta_source_id to repair)');
                continue;
            }
            if ($dry) {
                $this->line('  would repair adsource #'.$row->id.' from meta_source_id='.$m[1]);
                continue;
            }
            $headline = null;
            $body = null;
            $sourceUrl = null;
            if (preg_match('/headline=(.+)/', $desc, $hm)) {
                $headline = trim($hm[1]);
            }
            if (preg_match('/body=(.+)/', $desc, $bm)) {
                $body = trim($bm[1]);
            }
            if (preg_match('/meta_source_url=(.+)/', $desc, $um)) {
                $sourceUrl = trim($um[1]);
            }
            $imageUrl = null;
            if (preg_match('/image_url=(.+)/', $desc, $im)) {
                $imageUrl = trim($im[1]);
            }
            AdSource::ensureFromCtwaReferral($m[1], $headline, $sourceUrl, 'ad', $body, $imageUrl, null);
        }

        $this->info("Done. threads_touched={$fixedUsers} leads_updated={$fixedLeads}");

        return self::SUCCESS;
    }
}
