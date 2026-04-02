<?php

namespace Modules\WhatsAppModule\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\CategoryManagement\Entities\Category;
use Modules\UserManagement\Entities\User;

class WhatsAppMarketingCampaign extends Model
{
    protected $table = 'whatsapp_marketing_campaigns';

    public const AUDIENCE_ALL_CUSTOMERS = 'all_customers';

    public const AUDIENCE_ALL_PROVIDERS = 'all_providers';

    public const AUDIENCE_PROVIDERS_BY_CATEGORY = 'providers_by_category';

    public const AUDIENCE_CSV_IMPORT = 'csv_import';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'name',
        'whatsapp_marketing_template_id',
        'audience_type',
        'category_id',
        'csv_path',
        'variable_mapping',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'variable_mapping' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMarketingTemplate::class, 'whatsapp_marketing_template_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMarketingMessage::class, 'whatsapp_marketing_campaign_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public static function audienceLabel(string $type): string
    {
        return match ($type) {
            self::AUDIENCE_ALL_CUSTOMERS => translate('All_Customers'),
            self::AUDIENCE_ALL_PROVIDERS => translate('All_Providers'),
            self::AUDIENCE_PROVIDERS_BY_CATEGORY => translate('Providers_by_Category'),
            self::AUDIENCE_CSV_IMPORT => translate('Import_Contacts_CSV'),
            default => $type,
        };
    }
}
