<?php

use Illuminate\Support\Str;
use Modules\BusinessSettingsModule\Entities\BusinessSettings;
use Modules\BusinessSettingsModule\Entities\DataSetting;
use Modules\BusinessSettingsModule\Entities\LoginSetup;
use Modules\ProviderManagement\Entities\ProviderSetting;
use Modules\UserManagement\Entities\User;

if (!function_exists('business_config')) {
    function business_config($key, $settings_type)
    {
        try {
            $config = BusinessSettings::where('key_name', $key)->where('settings_type', $settings_type)->first();
        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? $config : null;
    }
}

if (!function_exists('login_setup')) {
    function login_setup($key)
    {
        try {
            $config = LoginSetup::where('key', $key)->first();
        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? $config : null;
    }
}

if (!function_exists('data_config')) {
    function data_config($key, $settings_type)
    {
        try {
            $config = DataSetting::where('key', $key)->where('type', $settings_type)->first();
        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? $config : null;
    }
}


if (!function_exists('provider_config')) {
    function provider_config($key, $settings_type, $provider_id)
    {
        try {
            $config = ProviderSetting::where('key_name', $key)->where('settings_type', $settings_type)->where('provider_id', $provider_id)->first();
        } catch (Exception $exception) {
            return null;
        }

        return (isset($config)) ? $config : null;
    }
}


if (!function_exists('pagination_limit')) {
    function pagination_limit()
    {
        try {
            if (!session()->has('pagination_limit')) {
                $limit = BusinessSettings::where('key_name', 'pagination_limit')->where('settings_type', 'business_information')->first()->live_values;
                session()->put('pagination_limit', $limit);
            } else {
                $limit = session('pagination_limit');
            }
        } catch (Exception $exception) {
            return 10;
        }

        return $limit;
    }
}

if (!function_exists('currency_code')) {
    function currency_code(): string
    {
        $code = business_config('currency_code', 'business_information')['live_values'];
        return $code ?? 'USD';
    }
}

if (!function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        $code = business_config('currency_code', 'business_information')['live_values'];
        $symbol = '$';
        foreach (CURRENCIES as $currency) {
            if ($currency['code'] == $code) {
                $symbol = $currency['symbol'];
            }
        }

        return $symbol;
    }
}

if (!function_exists('with_currency_symbol')) {
    function with_currency_symbol($value): string
    {
        $position = business_config('currency_symbol_position', 'business_information')['live_values']??'right';
        $decimal_point = business_config('currency_decimal_point', 'business_information')['live_values']??2;
        $code = business_config('currency_code', 'business_information')['live_values'];
        $symbol = '$';
        foreach (CURRENCIES as $currency) {
            if ($currency['code'] == $code) {
                $symbol = $currency['symbol'];
            }
        }

        if($position == 'left') {
            return $symbol . number_format($value, $decimal_point, '.', ',');
        } else {
            return number_format($value, $decimal_point, '.', ',') . $symbol;
        }

    }
}

if (!function_exists('with_decimal_point')) {
    function with_decimal_point($value): float
    {
        $decimal_point = business_config('currency_decimal_point', 'business_information')['live_values']??2;
        return (float)(number_format($value, $decimal_point, '.', ''));
    }
}

if (!function_exists('generate_referer_code')) {
    function generate_referer_code() {
        $ref_code = strtoupper(Str::random(10));

        if (User::where('ref_code', '=', $ref_code)->exists()) {
            return generate_referer_code();
        }

        return $ref_code;
    }
}

if (!function_exists('getLanguageCode')) {
    function getLanguageCode(string $country_code): string
    {
        $locales = array(
            'en-English(default)',
            'af-Afrikaans',
            'sq-Albanian - shqip',
            'am-Amharic - አማርኛ',
            'ar-Arabic - العربية',
            'an-Aragonese - aragonés',
            'hy-Armenian - հայերեն',
            'ast-Asturian - asturianu',
            'az-Azerbaijani - azərbaycan dili',
            'eu-Basque - euskara',
            'be-Belarusian - беларуская',
            'bn-Bengali - বাংলা',
            'bs-Bosnian - bosanski',
            'br-Breton - brezhoneg',
            'bg-Bulgarian - български',
            'ca-Catalan - català',
            'ckb-Central Kurdish - کوردی (دەستنوسی عەرەبی)',
            'zh-Chinese - 中文',
            'zh-HK-Chinese (Hong Kong) - 中文（香港）',
            'zh-CN-Chinese (Simplified) - 中文（简体）',
            'zh-TW-Chinese (Traditional) - 中文（繁體）',
            'co-Corsican',
            'hr-Croatian - hrvatski',
            'cs-Czech - čeština',
            'da-Danish - dansk',
            'nl-Dutch - Nederlands',
            'en-AU-English (Australia)',
            'en-CA-English (Canada)',
            'en-IN-English (India)',
            'en-NZ-English (New Zealand)',
            'en-ZA-English (South Africa)',
            'en-GB-English (United Kingdom)',
            'en-US-English (United States)',
            'eo-Esperanto - esperanto',
            'et-Estonian - eesti',
            'fo-Faroese - føroyskt',
            'fil-Filipino',
            'fi-Finnish - suomi',
            'fr-French - français',
            'fr-CA-French (Canada) - français (Canada)',
            'fr-FR-French (France) - français (France)',
            'fr-CH-French (Switzerland) - français (Suisse)',
            'gl-Galician - galego',
            'ka-Georgian - ქართული',
            'de-German - Deutsch',
            'de-AT-German (Austria) - Deutsch (Österreich)',
            'de-DE-German (Germany) - Deutsch (Deutschland)',
            'de-LI-German (Liechtenstein) - Deutsch (Liechtenstein)
            ',
            'de-CH-German (Switzerland) - Deutsch (Schweiz)',
            'el-Greek - Ελληνικά',
            'gn-Guarani',
            'gu-Gujarati - ગુજરાતી',
            'ha-Hausa',
            'haw-Hawaiian - ʻŌlelo Hawaiʻi',
            'he-Hebrew - עברית',
            'hi-Hindi - हिन्दी',
            'hu-Hungarian - magyar',
            'is-Icelandic - íslenska',
            'id-Indonesian - Indonesia',
            'ia-Interlingua',
            'ga-Irish - Gaeilge',
            'it-Italian - italiano',
            'it-IT-Italian (Italy) - italiano (Italia)',
            'it-CH-Italian (Switzerland) - italiano (Svizzera)',
            'ja-Japanese - 日本語',
            'kn-Kannada - ಕನ್ನಡ',
            'kk-Kazakh - қазақ тілі',
            'km-Khmer - ខ្មែរ',
            'ko-Korean - 한국어',
            'ku-Kurdish - Kurdî',
            'ky-Kyrgyz - кыргызча',
            'lo-Lao - ລາວ',
            'la-Latin',
            'lv-Latvian - latviešu',
            'ln-Lingala - lingála',
            'lt-Lithuanian - lietuvių',
            'mk-Macedonian - македонски',
            'ms-Malay - Bahasa Melayu',
            'ml-Malayalam - മലയാളം',
            'mt-Maltese - Malti',
            'mr-Marathi - मराठी',
            'mn-Mongolian - монгол',
            'ne-Nepali - नेपाली',
            'no-Norwegian - norsk',
            'nb-Norwegian Bokmål - norsk bokmål',
            'nn-Norwegian Nynorsk - nynorsk',
            'oc-Occitan',
            'or-Oriya - ଓଡ଼ିଆ',
            'om-Oromo - Oromoo',
            'ps-Pashto - پښتو',
            'fa-Persian - فارسی',
            'pl-Polish - polski',
            'pt-Portuguese - português',
            'pt-BR-Portuguese (Brazil) - português (Brasil)',
            'pt-PT-Portuguese (Portugal) - português (Portugal)',
            'pa-Punjabi - ਪੰਜਾਬੀ',
            'qu-Quechua',
            'ro-Romanian - română',
            'mo-Romanian (Moldova) - română (Moldova)',
            'rm-Romansh - rumantsch',
            'ru-Russian - русский',
            'gd-Scottish Gaelic',
            'sr-Serbian - српски',
            'sh-Serbo-Croatian - Srpskohrvatski',
            'sn-Shona - chiShona',
            'sd-Sindhi',
            'si-Sinhala - සිංහල',
            'sk-Slovak - slovenčina',
            'sl-Slovenian - slovenščina',
            'so-Somali - Soomaali',
            'st-Southern Sotho',
            'es-Spanish - español',
            'es-AR-Spanish (Argentina) - español (Argentina)',
            'es-419-Spanish (Latin America) - español (Latinoamérica)
            ',
            'es-MX-Spanish (Mexico) - español (México)',
            'es-ES-Spanish (Spain) - español (España)',
            'es-US-Spanish (United States) - español (Estados Unidos)
            ',
            'su-Sundanese',
            'sw-Swahili - Kiswahili',
            'sv-Swedish - svenska',
            'tg-Tajik - тоҷикӣ',
            'ta-Tamil - தமிழ்',
            'tt-Tatar',
            'te-Telugu - తెలుగు',
            'th-Thai - ไทย',
            'ti-Tigrinya - ትግርኛ',
            'to-Tongan - lea fakatonga',
            'tr-Turkish - Türkçe',
            'tk-Turkmen',
            'tw-Twi',
            'uk-Ukrainian - українська',
            'ur-Urdu - اردو',
            'ug-Uyghur',
            'uz-Uzbek - o‘zbek',
            'vi-Vietnamese - Tiếng Việt',
            'wa-Walloon - wa',
            'cy-Welsh - Cymraeg',
            'fy-Western Frisian',
            'xh-Xhosa',
            'yi-Yiddish',
            'yo-Yoruba - Èdè Yorùbá',
            'zu-Zulu - isiZulu',
        );

        foreach ($locales as $locale) {
            $locale_region = explode('-',$locale);
            if ($country_code == $locale_region[0]) {
                return $locale_region[0];
            }
        }

        return "en";
    }
}

if (!function_exists('auto_translator')) {
    function auto_translator($q, $sl, $tl): array|string
    {
        $res = file_get_contents("https://translate.googleapis.com/translate_a/single?client=gtx&ie=UTF-8&oe=UTF-8&dt=bd&dt=ex&dt=ld&dt=md&dt=qca&dt=rw&dt=rm&dt=ss&dt=t&dt=at&sl=" . $sl . "&tl=" . $tl . "&hl=hl&q=" . urlencode($q), $_SERVER['DOCUMENT_ROOT'] . "/transes.html");
        $res = json_decode($res);
        return str_replace('_',' ',$res[0][0][0]);
    }
}

if (!function_exists('language_load')) {
    function language_load()
    {
        if (\session()->has('language_settings')) {
            $language = \session('language_settings');
        } else {
            $language = BusinessSettings::where('key_name', 'system_language')->first();
            \session()->put('language_settings', $language);
        }
        return $language;
    }
}

if (!function_exists('provider_language_load')) {
    function provider_language_load()
    {
        if (\session()->has('provider_language_settings')) {
            $language = \session('provider_language_settings');
        } else {
            $language = BusinessSettings::where('key_name', 'system_language')->first();
            \session()->put('provider_language_settings', $language);
        }
        return $language;
    }
}

if (!function_exists('landing_language_load')) {
    function landing_language_load()
    {
        if (\session()->has('landing_language_settings')) {
            $language = \session('landing_language_settings');
        } else {
            $language = BusinessSettings::where('key_name', 'system_language')->first();
            \session()->put('landing_language_settings', $language);
        }
        return $language;
    }
}

if (!function_exists('get_language_name')) {
    function get_language_name($key)
    {
        $languages = array(
            "af" => "Afrikaans",
            "sq" => "Albanian - shqip",
            "am" => "Amharic - አማርኛ",
            "ar" => "Arabic - العربية",
            "an" => "Aragonese - aragonés",
            "hy" => "Armenian - հայերեն",
            "ast" => "Asturian - asturianu",
            "az" => "Azerbaijani - azərbaycan dili",
            "eu" => "Basque - euskara",
            "be" => "Belarusian - беларуская",
            "bn" => "Bengali - বাংলা",
            "bs" => "Bosnian - bosanski",
            "br" => "Breton - brezhoneg",
            "bg" => "Bulgarian - български",
            "ca" => "Catalan - català",
            "ckb" => "Central Kurdish - کوردی (دەستنوسی عەرەبی)",
            "zh" => "Chinese - 中文",
            "zh-HK" => "Chinese (Hong Kong) - 中文（香港）",
            "zh-CN" => "Chinese (Simplified) - 中文（简体）",
            "zh-TW" => "Chinese (Traditional) - 中文（繁體）",
            "co" => "Corsican",
            "hr" => "Croatian - hrvatski",
            "cs" => "Czech - čeština",
            "da" => "Danish - dansk",
            "nl" => "Dutch - Nederlands",
            "en" => "English",
            "en-AU" => "English (Australia)",
            "en-CA" => "English (Canada)",
            "en-IN" => "English (India)",
            "en-NZ" => "English (New Zealand)",
            "en-ZA" => "English (South Africa)",
            "en-GB" => "English (United Kingdom)",
            "en-US" => "English (United States)",
            "eo" => "Esperanto - esperanto",
            "et" => "Estonian - eesti",
            "fo" => "Faroese - føroyskt",
            "fil" => "Filipino",
            "fi" => "Finnish - suomi",
            "fr" => "French - français",
            "fr-CA" => "French (Canada) - français (Canada)",
            "fr-FR" => "French (France) - français (France)",
            "fr-CH" => "French (Switzerland) - français (Suisse)",
            "gl" => "Galician - galego",
            "ka" => "Georgian - ქართული",
            "de" => "German - Deutsch",
            "de-AT" => "German (Austria) - Deutsch (Österreich)",
            "de-DE" => "German (Germany) - Deutsch (Deutschland)",
            "de-LI" => "German (Liechtenstein) - Deutsch (Liechtenstein)",
            "de-CH" => "German (Switzerland) - Deutsch (Schweiz)",
            "el" => "Greek - Ελληνικά",
            "gn" => "Guarani",
            "gu" => "Gujarati - ગુજરાતી",
            "ha" => "Hausa",
            "haw" => "Hawaiian - ʻŌlelo Hawaiʻi",
            "he" => "Hebrew - עברית",
            "hi" => "Hindi - हिन्दी",
            "hu" => "Hungarian - magyar",
            "is" => "Icelandic - íslenska",
            "id" => "Indonesian - Indonesia",
            "ia" => "Interlingua",
            "ga" => "Irish - Gaeilge",
            "it" => "Italian - italiano",
            "it-IT" => "Italian (Italy) - italiano (Italia)",
            "it-CH" => "Italian (Switzerland) - italiano (Svizzera)",
            "ja" => "Japanese - 日本語",
            "kn" => "Kannada - ಕನ್ನಡ",
            "kk" => "Kazakh - қазақ тілі",
            "km" => "Khmer - ខ្មែរ",
            "ko" => "Korean - 한국어",
            "ku" => "Kurdish - Kurdî",
            "ky" => "Kyrgyz - кыргызча",
            "lo" => "Lao - ລາວ",
            "la" => "Latin",
            "lv" => "Latvian - latviešu",
            "ln" => "Lingala - lingála",
            "lt" => "Lithuanian - lietuvių",
            "mk" => "Macedonian - македонски",
            "ms" => "Malay - Bahasa Melayu",
            "ml" => "Malayalam - മലയാളം",
            "mt" => "Maltese - Malti",
            "mr" => "Marathi - मराठी",
            "mn" => "Mongolian - монгол",
            "ne" => "Nepali - नेपाली",
            "no" => "Norwegian - norsk",
            "nb" => "Norwegian Bokmål - norsk bokmål",
            "nn" => "Norwegian Nynorsk - nynorsk",
            "oc" => "Occitan",
            "or" => "Oriya - ଓଡ଼ିଆ",
            "om" => "Oromo - Oromoo",
            "ps" => "Pashto - پښتو",
            "fa" => "Persian - فارسی",
            "pl" => "Polish - polski",
            "pt" => "Portuguese - português",
            "pt-BR" => "Portuguese (Brazil) - português (Brasil)",
            "pt-PT" => "Portuguese (Portugal) - português (Portugal)",
            "pa" => "Punjabi - ਪੰਜਾਬੀ",
            "qu" => "Quechua",
            "ro" => "Romanian - română",
            "mo" => "Romanian (Moldova) - română (Moldova)",
            "rm" => "Romansh - rumantsch",
            "ru" => "Russian - русский",
            "gd" => "Scottish Gaelic",
            "sr" => "Serbian - српски",
            "sh" => "Serbo-Croatian - Srpskohrvatski",
            "sn" => "Shona - chiShona",
            "sd" => "Sindhi",
            "si" => "Sinhala - සිංහල",
            "sk" => "Slovak - slovenčina",
            "sl" => "Slovenian - slovenščina",
            "so" => "Somali - Soomaali",
            "st" => "Southern Sotho",
            "es" => "Spanish - español",
            "es-AR" => "Spanish (Argentina) - español (Argentina)",
            "es-419" => "Spanish (Latin America) - español (Latinoamérica)",
            "es-MX" => "Spanish (Mexico) - español (México)",
            "es-ES" => "Spanish (Spain) - español (España)",
            "es-US" => "Spanish (United States) - español (Estados Unidos)",
            "su" => "Sundanese",
            "sw" => "Swahili - Kiswahili",
            "sv" => "Swedish - svenska",
            "tg" => "Tajik - тоҷикӣ",
            "ta" => "Tamil - தமிழ்",
            "tt" => "Tatar",
            "te" => "Telugu - తెలుగు",
            "th" => "Thai - ไทย",
            "ti" => "Tigrinya - ትግርኛ",
            "to" => "Tongan - lea fakatonga",
            "tr" => "Turkish - Türkçe",
            "tk" => "Turkmen",
            "tw" => "Twi",
            "uk" => "Ukrainian - українська",
            "ur" => "Urdu - اردو",
            "ug" => "Uyghur",
            "uz" => "Uzbek - o‘zbek",
            "vi" => "Vietnamese - Tiếng Việt",
            "wa" => "Walloon - wa",
            "cy" => "Welsh - Cymraeg",
            "fy" => "Western Frisian",
            "xh" => "Xhosa",
            "yi" => "Yiddish",
            "yo" => "Yoruba - Èdè Yorùbá",
            "zu" => "Zulu - isiZulu",
        );
        return array_key_exists($key, $languages) ? $languages[$key] : $key;
    }
}

if (!function_exists('get_push_notification_message')) {
    function get_push_notification_message($key, $settings_type, $lang='en')
    {
        try {
            $config = BusinessSettings::where('key_name', $key)->where('settings_type', $settings_type)->
            with(['translations'=>function($query)use($lang){
                $query->where('locale', $lang);
            }])->first();
        } catch (Exception $exception) {
            return null;
        }

        if($config){
            if ($config->live_values[$key.'_status'] == 0) {
                return 0;
            }
            $message = $key.'_'.'message';
            return count($config->translations) > 0 ? $config->translations[0]['value'] : $config->live_values[$message];
        }else{
            return false;
        }
    }
}

if (!function_exists('normalize_commission_tier_group_for_ui')) {
    /**
     * Normalize stored commission group for admin UI and helpers (supports legacy threshold shape).
     *
     * @param  array<string, mixed>|null  $stored
     * @return array{mode: string, fixed_amount: float, tiers: list<array{from: float, to: float|null, amount_type: string, amount: float}>}
     */
    function normalize_commission_tier_group_for_ui(?array $stored, float $fallbackPercentage): array
    {
        $stored = is_array($stored) ? $stored : [];
        if (isset($stored['mode']) && in_array($stored['mode'], ['fixed', 'tiered'], true)) {
            $tiers = [];
            foreach ($stored['tiers'] ?? [] as $t) {
                if (! is_array($t)) {
                    continue;
                }
                $toRaw = $t['to'] ?? null;
                if ($toRaw !== null && $toRaw !== '') {
                    $to = (float) $toRaw;
                } else {
                    $to = null;
                }
                $tiers[] = [
                    'from' => (float) ($t['from'] ?? 0),
                    'to' => $to,
                    'amount_type' => in_array($t['amount_type'] ?? '', ['fixed', 'percentage'], true) ? $t['amount_type'] : 'percentage',
                    'amount' => (float) ($t['amount'] ?? 0),
                ];
            }
            $mode = $stored['mode'];
            if ($mode === 'tiered' && count($tiers) === 0) {
                $tiers[] = [
                    'from' => 0.0,
                    'to' => null,
                    'amount_type' => 'percentage',
                    'amount' => $fallbackPercentage,
                ];
            }

            return [
                'mode' => $mode,
                'fixed_amount' => (float) ($stored['fixed_amount'] ?? 0),
                'tiers' => $tiers,
            ];
        }

        $th = (float) ($stored['threshold'] ?? 0);
        $fx = (float) ($stored['fixed_below_threshold'] ?? 0);
        $pct = (float) ($stored['percentage_above_threshold'] ?? $fallbackPercentage);

        if ($th > 0) {
            return [
                'mode' => 'tiered',
                'fixed_amount' => 0.0,
                'tiers' => [
                    ['from' => 0.0, 'to' => $th, 'amount_type' => 'fixed', 'amount' => $fx],
                    ['from' => $th, 'to' => null, 'amount_type' => 'percentage', 'amount' => $pct],
                ],
            ];
        }

        if ($fx > 0) {
            return [
                'mode' => 'tiered',
                'fixed_amount' => 0.0,
                'tiers' => [
                    ['from' => 0.0, 'to' => null, 'amount_type' => 'fixed', 'amount' => $fx],
                ],
            ];
        }

        return [
            'mode' => 'tiered',
            'fixed_amount' => 0.0,
            'tiers' => [
                ['from' => 0.0, 'to' => null, 'amount_type' => 'percentage', 'amount' => $pct],
            ],
        ];
    }
}

if (!function_exists('commission_tier_legacy_slice')) {
    /**
     * Derive legacy threshold fields from normalized group for backward compatibility.
     *
     * @param  array{mode: string, fixed_amount: float, tiers: list<array<string, mixed>>}  $group
     * @return array{threshold: float, fixed_below_threshold: float, percentage_above_threshold: float}
     */
    function commission_tier_legacy_slice(array $group): array
    {
        if (($group['mode'] ?? '') === 'fixed') {
            return [
                'threshold' => 0.0,
                'fixed_below_threshold' => (float) ($group['fixed_amount'] ?? 0),
                'percentage_above_threshold' => 0.0,
            ];
        }

        $tiers = $group['tiers'] ?? [];
        $threshold = 0.0;
        $fixedBelow = 0.0;
        $pctAbove = 0.0;

        foreach ($tiers as $t) {
            if (! is_array($t)) {
                continue;
            }
            $to = $t['to'] ?? null;
            if ($to !== null && $to !== '') {
                $threshold = (float) $to;
            }
            if (($t['amount_type'] ?? '') === 'fixed') {
                $fixedBelow = (float) ($t['amount'] ?? 0);
            }
            if (($t['amount_type'] ?? '') === 'percentage') {
                $pctAbove = (float) ($t['amount'] ?? 0);
            }
        }

        if ($pctAbove <= 0 && count($tiers) === 1 && ($tiers[0]['amount_type'] ?? '') === 'percentage') {
            $pctAbove = (float) ($tiers[0]['amount'] ?? 0);
        }

        return [
            'threshold' => $threshold,
            'fixed_below_threshold' => $fixedBelow,
            'percentage_above_threshold' => $pctAbove,
        ];
    }
}

if (!function_exists('commission_calc_line_preview')) {
    /**
     * Preview admin commission and provider remainder for one booking line (service or spare subtotal).
     *
     * @param  array{mode: string, fixed_amount: float, tiers: list<array<string, mixed>>}  $group
     * @return array{admin_commission: float, provider_earning: float, rule_short: string, band_note: string}
     */
    function commission_calc_line_preview(float $lineAmount, array $group): array
    {
        $lineAmount = max(0.0, $lineAmount);
        $empty = [
            'admin_commission' => 0.0,
            'provider_earning' => round($lineAmount, 2),
            'rule_short' => translate('No_rule'),
            'band_note' => '—',
        ];

        if (($group['mode'] ?? '') === 'fixed') {
            $admin = min((float) ($group['fixed_amount'] ?? 0), $lineAmount);
            $provider = max(0.0, $lineAmount - $admin);

            return [
                'admin_commission' => round($admin, 2),
                'provider_earning' => round($provider, 2),
                'rule_short' => translate('Fixed_fee_each_line'),
                'band_note' => str_replace(':amount', number_format($admin, 2), translate('Flat_X_per_line')),
            ];
        }

        $tiers = $group['tiers'] ?? [];
        if (count($tiers) === 0) {
            return $empty;
        }

        $normalized = [];
        foreach ($tiers as $t) {
            if (! is_array($t)) {
                continue;
            }
            $toRaw = $t['to'] ?? null;
            $to = ($toRaw === null || $toRaw === '') ? null : (float) $toRaw;
            $normalized[] = [
                'from' => (float) ($t['from'] ?? 0),
                'to' => $to,
                'amount_type' => ($t['amount_type'] ?? '') === 'fixed' ? 'fixed' : 'percentage',
                'amount' => (float) ($t['amount'] ?? 0),
            ];
        }

        usort($normalized, fn ($a, $b) => $a['from'] <=> $b['from']);

        $matched = null;
        foreach ($normalized as $t) {
            if ($lineAmount < $t['from']) {
                continue;
            }
            if ($t['to'] !== null && $lineAmount > $t['to']) {
                continue;
            }
            $matched = $t;
            break;
        }

        if ($matched === null) {
            $empty['rule_short'] = translate('No_matching_tier_for_amount');
            $empty['band_note'] = translate('Check_tier_ranges');

            return $empty;
        }

        $admin = 0.0;
        if ($matched['amount_type'] === 'percentage') {
            $admin = $lineAmount * ($matched['amount'] / 100.0);
        } else {
            $admin = min($matched['amount'], $lineAmount);
        }

        $provider = max(0.0, $lineAmount - $admin);

        $band = number_format($matched['from'], 2).' – ';
        $band .= $matched['to'] === null ? translate('preview_unlimited_upper') : number_format((float) $matched['to'], 2);

        if ($matched['amount_type'] === 'percentage') {
            $amtStr = rtrim(rtrim(number_format($matched['amount'], 2, '.', ''), '0'), '.');
            $rule = $amtStr.translate('percent_of_line_total');
        } else {
            $rule = str_replace(':amount', number_format($matched['amount'], 2), translate('Fixed_X_from_line'));
        }

        return [
            'admin_commission' => round($admin, 2),
            'provider_earning' => round($provider, 2),
            'rule_short' => $rule,
            'band_note' => $band,
        ];
    }
}

if (!function_exists('commission_plain_english_block')) {
    /**
     * Short plain-language explanation for admin UI (service vs spare).
     *
     * @param  array{mode: string, fixed_amount: float, tiers: list<array<string, mixed>>}  $group
     */
    function commission_plain_english_block(array $group, string $lineKind): string
    {
        $isSpare = $lineKind === 'spare';
        if (($group['mode'] ?? '') === 'fixed') {
            return $isSpare
                ? translate('commission_plain_spare_fixed')
                : translate('commission_plain_service_fixed');
        }

        return $isSpare
            ? translate('commission_plain_spare_tiered')
            : translate('commission_plain_service_tiered');
    }
}

if (!function_exists('derive_default_commission_percentage_from_service_group')) {
    /**
     * Sync legacy `default_commission` setting for APIs and provider UI that still read a single %.
     * Uses the first percentage-type tier (by range order), else 0 for fixed-only, else 10.
     */
    function derive_default_commission_percentage_from_service_group(array $serviceGroup): string
    {
        if (($serviceGroup['mode'] ?? '') === 'fixed') {
            return '0';
        }
        $tiers = $serviceGroup['tiers'] ?? [];
        if (! is_array($tiers) || count($tiers) === 0) {
            return '10';
        }
        $sorted = $tiers;
        usort($sorted, fn ($a, $b) => ((float) ($a['from'] ?? 0)) <=> ((float) ($b['from'] ?? 0)));
        foreach ($sorted as $t) {
            if (! is_array($t)) {
                continue;
            }
            if (($t['amount_type'] ?? '') === 'percentage') {
                return (string) round((float) ($t['amount'] ?? 0), 4);
            }
        }

        return '0';
    }
}

if (!function_exists('commission_tier_setup')) {
    /**
     * Commission setup from Business Model Setup: service vs spare parts (fixed or tiered ranges).
     * Each group includes mode, fixed_amount, tiers, plus legacy threshold fields for older readers.
     *
     * @return array{service: array, spare_parts: array}
     */
    function commission_tier_setup(): array
    {
        $fallbackPct = 10.0;
        $dc = optional(business_config('default_commission', 'business_information'))->live_values;
        if (is_numeric($dc)) {
            $fallbackPct = (float) $dc;
        }

        $config = business_config('commission_tier_setup', 'business_information');
        $v = ($config && is_array($config->live_values)) ? $config->live_values : [];

        $service = normalize_commission_tier_group_for_ui($v['service'] ?? null, $fallbackPct);
        $spare = normalize_commission_tier_group_for_ui($v['spare_parts'] ?? null, 0.0);

        $legacyS = commission_tier_legacy_slice($service);
        $legacyP = commission_tier_legacy_slice($spare);

        return [
            'service' => array_merge($service, $legacyS),
            'spare_parts' => array_merge($spare, $legacyP),
        ];
    }
}

if (!function_exists('normalize_stored_commission_tier_setup_array')) {
    /**
     * Build the same shape as commission_tier_setup() from raw stored service/spare arrays.
     *
     * @param  array<string, mixed>  $v  ['service' => ..., 'spare_parts' => ...]
     * @return array{service: array, spare_parts: array}
     */
    function normalize_stored_commission_tier_setup_array(array $v): array
    {
        $fallbackPct = 10.0;
        $dc = optional(business_config('default_commission', 'business_information'))->live_values;
        if (is_numeric($dc)) {
            $fallbackPct = (float) $dc;
        }

        $service = normalize_commission_tier_group_for_ui($v['service'] ?? null, $fallbackPct);
        $spare = normalize_commission_tier_group_for_ui($v['spare_parts'] ?? null, 0.0);
        $legacyS = commission_tier_legacy_slice($service);
        $legacyP = commission_tier_legacy_slice($spare);

        return [
            'service' => array_merge($service, $legacyS),
            'spare_parts' => array_merge($spare, $legacyP),
        ];
    }
}

if (!function_exists('entity_commission_custom_applies')) {
    /**
     * Category, subcategory (same model), or service with stored custom tiers.
     */
    function entity_commission_custom_applies(?object $model): bool
    {
        if ($model === null) {
            return false;
        }

        return (int) ($model->commission_custom ?? 0) === 1
            && is_array($model->commission_tier_setup ?? null)
            && ($model->commission_tier_setup ?? []) !== [];
    }
}

if (!function_exists('commission_context_service_for_booking')) {
    /**
     * First booking line’s service (with category chain), for commission resolution.
     *
     * @param  \Modules\BookingModule\Entities\Booking|\Modules\BookingModule\Entities\BookingRepeat  $booking
     */
    function commission_context_service_for_booking($booking): ?\Modules\ServiceManagement\Entities\Service
    {
        if ($booking instanceof \Modules\BookingModule\Entities\BookingRepeat) {
            $line = $booking->detail()->orderBy('id')->with([
                'service.subCategory',
                'service.category',
            ])->first();
        } else {
            $line = $booking->detail()->orderBy('id')->with([
                'service.subCategory',
                'service.category',
            ])->first();
        }

        return $line?->service;
    }
}

if (!function_exists('commission_tier_setup_from_provider_custom_only')) {
    /**
     * @return array{service: array, spare_parts: array}|null
     */
    function commission_tier_setup_from_provider_custom_only($booking, int|string|null $providerId = null): ?array
    {
        $pid = $providerId ?? $booking->provider_id ?? null;
        if (! $pid) {
            return null;
        }

        $provider = \Modules\ProviderManagement\Entities\Provider::query()
            ->where('id', $pid)
            ->first(['id', 'commission_status', 'commission_tier_setup', 'commission_percentage']);

        if (! $provider || (int) $provider->commission_status !== 1) {
            return null;
        }

        $stored = $provider->commission_tier_setup;
        if (! is_array($stored) || $stored === []) {
            $pct = max(0.0, min(100.0, (float) ($provider->commission_percentage ?? 0)));
            $legacyLine = [
                'mode' => 'tiered',
                'fixed_amount' => 0.0,
                'tiers' => [
                    ['from' => 0.0, 'to' => null, 'amount_type' => 'percentage', 'amount' => $pct],
                ],
            ];

            return normalize_stored_commission_tier_setup_array([
                'service' => $legacyLine,
                'spare_parts' => $legacyLine,
            ]);
        }

        return normalize_stored_commission_tier_setup_array($stored);
    }
}

if (!function_exists('resolve_commission_tier_setup_for_booking')) {
    /**
     * Priority: provider custom → service → subcategory → category → company.
     * Multi–service bookings: first detail line’s service drives category chain.
     *
     * @param  \Modules\BookingModule\Entities\Booking|\Modules\BookingModule\Entities\BookingRepeat  $booking
     * @return array{service: array, spare_parts: array}
     */
    function resolve_commission_tier_setup_for_booking($booking, int|string|null $providerId = null): array
    {
        $fromProvider = commission_tier_setup_from_provider_custom_only($booking, $providerId);
        if ($fromProvider !== null) {
            return $fromProvider;
        }

        $service = commission_context_service_for_booking($booking);
        if ($service) {
            if (entity_commission_custom_applies($service)) {
                return normalize_stored_commission_tier_setup_array($service->commission_tier_setup);
            }
            $sub = $service->subCategory;
            if (entity_commission_custom_applies($sub)) {
                return normalize_stored_commission_tier_setup_array($sub->commission_tier_setup);
            }
            $cat = $service->category;
            if (entity_commission_custom_applies($cat)) {
                return normalize_stored_commission_tier_setup_array($cat->commission_tier_setup);
            }
        } else {
            $subCategoryId = $booking->sub_category_id ?? null;
            $categoryId = $booking->category_id ?? null;
            if ($subCategoryId) {
                $sub = \Modules\CategoryManagement\Entities\Category::query()->find($subCategoryId);
                if (entity_commission_custom_applies($sub)) {
                    return normalize_stored_commission_tier_setup_array($sub->commission_tier_setup);
                }
            }
            if ($categoryId) {
                $cat = \Modules\CategoryManagement\Entities\Category::query()->find($categoryId);
                if (entity_commission_custom_applies($cat)) {
                    return normalize_stored_commission_tier_setup_array($cat->commission_tier_setup);
                }
            }
        }

        return commission_tier_setup();
    }
}

if (!function_exists('commission_tier_setup_for_provider_booking')) {
    /**
     * @deprecated Use resolve_commission_tier_setup_for_booking()
     */
    function commission_tier_setup_for_provider_booking($booking, int|string|null $providerId = null): array
    {
        return resolve_commission_tier_setup_for_booking($booking, $providerId);
    }
}


