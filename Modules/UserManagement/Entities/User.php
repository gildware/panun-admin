<?php

namespace Modules\UserManagement\Entities;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Passport\HasApiTokens;
use Modules\BookingModule\Entities\Booking;
use Modules\BusinessSettingsModule\Entities\SettingsTutorials;
use Modules\BusinessSettingsModule\Entities\Storage;
use Modules\CartModule\Entities\AddedToCart;
use Modules\ChattingModule\Entities\ChannelConversation;
use Modules\CustomerModule\Entities\SearchedData;
use Modules\ProviderManagement\Entities\Provider;
use Modules\ReviewModule\Entities\Review;
use Modules\ServiceManagement\Entities\VisitedService;
use Modules\TransactionModule\Entities\Account;
use Modules\TransactionModule\Entities\Transaction;
use Modules\ZoneManagement\Entities\Zone;
use Laravel\Passport\Token;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    use HasFactory, HasUuid;

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'is_phone_verified' => 'integer',
        'is_email_verified' => 'integer',
        'is_active' => 'integer',
        'identification_image' => 'array',
        'wallet_balance' => 'float',
        'loyalty_point' => 'float',
        'received_avg_rating' => 'float',
        'received_rating_count' => 'integer',
        'customer_app_access' => 'boolean',
        'last_seen_at' => 'datetime',
        'admin_pinned_nav' => 'array',
    ];

    protected $appends = ['profile_image_full_path', 'identification_image_full_path'];

    private ?string $resolvedProfileImageFullPath = null;

    private bool $hasResolvedProfileImageFullPath = false;

    protected $fillable = [
        'uuid', 'first_name', 'last_name', 'email', 'phone', 'identification_number', 'identification_type', 'identification_image', 'date_of_birth', 'gender',
        'profile_image', 'fcm_token', 'is_phone_verified', 'is_email_verified', 'phone_verified_at', 'email_verified_at', 'password', 'is_active', 'provider_id', 'user_type', 'customer_app_access',
        'ref_code', 'referred_by',
        'staff_presence_status', 'last_seen_at', 'last_visited_page', 'admin_pinned_nav',
    ];

    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'employee_role_sections','employee_id');
    }

    public function bookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Booking::class, 'customer_id', 'id');
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function receivedProviderReviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\ReviewModule\Entities\ProviderCustomerReview::class, 'customer_id');
    }

    public function zones(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Zone::class, 'user_zones');
    }

    public function addresses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    protected function scopeOfType($query, array $type)
    {
        $query->whereIn('user_type', $type);
    }

    /**
     * Users who may authenticate against the customer mobile app or appear in customer-facing flows.
     */
    public function scopeEligibleCustomerAppUsers(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereIn('user_type', CUSTOMER_USER_TYPES)
                ->orWhere(function (Builder $q2) {
                    $q2->where('user_type', 'provider-admin')
                        ->where('customer_app_access', true);
                });
        });
    }

    /**
     * Admin lists, booking customer pickers, coupons, marketing: anyone who acts as a customer in the business.
     */
    public function scopeInCustomerDirectory(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereIn('user_type', CUSTOMER_USER_TYPES)
                ->orWhere(function (Builder $q2) {
                    $q2->where('user_type', 'provider-admin')
                        ->where('customer_app_access', true);
                });
        });
    }

    public function qualifiesForCustomerToProviderUpgrade(): bool
    {
        return $this->user_type === 'customer' && ! $this->provider()->exists();
    }

    /**
     * Same phone matching as {@see findByContactPhone}, but only among given user_type values.
     *
     * @param  list<string>  $userTypes
     */
    public static function findByContactPhoneScoped(string $phone, array $userTypes): ?self
    {
        if ($userTypes === []) {
            return null;
        }

        $trim = trim($phone);
        $digits = preg_replace('/\D+/', '', $trim) ?? '';

        return static::query()
            ->whereIn('user_type', $userTypes)
            ->where(function ($w) use ($trim, $digits) {
                if ($trim !== '') {
                    $w->where('phone', $trim);
                }
                if ($digits !== '' && $digits !== $trim) {
                    $w->orWhere('phone', $digits);
                }
                if ($digits !== '' && DB::connection()->getDriverName() === 'mysql') {
                    $w->orWhereRaw('REGEXP_REPLACE(COALESCE(phone, \'\'), \'[^0-9]\', \'\') = ?', [$digits]);
                }
            })
            ->first();
    }

    /**
     * Phone must be unique only within $userTypes (e.g. customers or provider-side accounts).
     *
     * @param  list<string>  $userTypes
     */
    public static function uniquePhoneAmongUserTypesRule(?string $ignoreUserId, array $userTypes)
    {
        $rule = Rule::unique('users', 'phone')->where(fn ($q) => $q->whereIn('user_type', $userTypes));

        if ($ignoreUserId !== null && $ignoreUserId !== '') {
            $rule = $rule->ignore($ignoreUserId);
        }

        return $rule;
    }

    public static function findByContactPhone(string $phone): ?self
    {
        $trim = trim($phone);
        $digits = preg_replace('/\D+/', '', $trim) ?? '';

        $q = static::query();
        $q->where(function ($w) use ($trim, $digits) {
            if ($trim !== '') {
                $w->where('phone', $trim);
            }
            if ($digits !== '' && $digits !== $trim) {
                $w->orWhere('phone', $digits);
            }
            if ($digits !== '' && DB::connection()->getDriverName() === 'mysql') {
                $w->orWhereRaw('REGEXP_REPLACE(COALESCE(phone, \'\'), \'[^0-9]\', \'\') = ?', [$digits]);
            }
        });

        return $q->first();
    }

    public static function findByContactEmail(string $email): ?self
    {
        $email = Str::lower(trim($email));
        if ($email === '') {
            return null;
        }

        return static::query()->whereRaw('LOWER(email) = ?', [$email])->first();
    }

    /**
     * @return array<string, string> field => message
     */
    public static function normalizeContactPhoneDigits(string $phone): string
    {
        return preg_replace('/\D+/', '', trim($phone)) ?? '';
    }

    /**
     * Uniqueness checks for profile contact update (exclude the signed-in provider owner).
     *
     * @return array<string, string> field => message
     */
    public static function providerContactUpdateErrors(string $phone, string $email, string $ignoreUserId): array
    {
        $errors = [];
        $existingPhone = self::findByContactPhoneScoped($phone, PROVIDER_USER_TYPES);
        if ($existingPhone && (string) $existingPhone->id !== (string) $ignoreUserId) {
            $errors['contact_person_phone'] = translate('The contact person phone has already been taken.');
        }

        $email = Str::lower(trim($email));
        if ($email !== '') {
            $byEmail = self::findByContactEmail($email);
            if ($byEmail
                && (string) $byEmail->id !== (string) $ignoreUserId
                && ! $byEmail->qualifiesForCustomerToProviderUpgrade()) {
                $errors['contact_person_email'] = translate('The contact person email has already been taken.');
            }
        }

        return $errors;
    }

    public static function providerContactRegistrationErrors(string $phone, string $email): array
    {
        $errors = [];

        if (self::findByContactPhoneScoped($phone, PROVIDER_USER_TYPES)) {
            $errors['contact_person_phone'] = translate('The contact person phone has already been taken.');
        }

        $byEmail = self::findByContactEmail($email);
        if ($byEmail && ! $byEmail->qualifiesForCustomerToProviderUpgrade()) {
            $errors['contact_person_email'] = translate('The contact person email has already been taken.');
        }

        $phone = trim($phone);
        $email = Str::lower(trim($email));
        if ($phone !== '' && $email !== '') {
            $byPhone = self::findByContactPhone($phone);
            if ($byPhone && $byEmail && $byPhone->id !== $byEmail->id) {
                $msg = translate('Phone and email must belong to the same user account.');
                $errors['contact_person_phone'] = $msg;
                $errors['contact_person_email'] = $msg;
            }
        }

        return $errors;
    }

    /**
     * Existing customer row to attach a new provider to, or null to create a new owner user.
     */
    public static function resolveCustomerUserForProviderOnboarding(string $phone, string $email): ?self
    {
        $byPhone = self::findByContactPhone($phone);
        $byEmail = self::findByContactEmail($email);
        if (self::providerContactRegistrationErrors($phone, $email) !== []) {
            return null;
        }
        if ($byPhone && $byPhone->qualifiesForCustomerToProviderUpgrade()) {
            return $byPhone;
        }
        if ($byEmail && $byEmail->qualifiesForCustomerToProviderUpgrade()) {
            return $byEmail;
        }

        return null;
    }

    protected function scopeOfStatus($query, $status)
    {
        $query->where('is_active', $status);
    }

    public function account(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Account::class);
    }

    public function referred_by_user()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function provider(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Provider::class);
    }

    public function serviceman(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Serviceman::class);
    }

    public function transactions_for_from_user(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_user_id');
    }

    public function added_to_carts(): HasMany
    {
        return $this->hasMany(AddedToCart::class, 'user_id', 'id');
    }

    public function visited_services(): HasMany
    {
        return $this->hasMany(VisitedService::class, 'user_id', 'id');
    }

    public function searched_data(): HasMany
    {
        return $this->hasMany(SearchedData::class, 'user_id', 'id');
    }

    public function channelConversations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChannelConversation::class, 'user_id', 'id');
    }

    public function module_access(): HasMany
    {
        return $this->hasMany(EmployeeRoleAccess::class, 'employee_id', 'id');
    }

    public function storage()
    {
        return $this->hasOne(Storage::class, 'model_id');
    }

    public function getProfileImageFullPathAttribute()
    {
        if ($this->hasResolvedProfileImageFullPath) {
            return $this->resolvedProfileImageFullPath;
        }

        $image = $this->profile_image;
        $defaultPath = match ($this->user_type) {
            'customer' => asset('assets/admin-module/img/customer.png'),
            'admin-employee', 'super-admin' => asset('assets/admin-module/img/customer.png'),
            default => asset('assets/provider-module/img/user2x.png'),
        };

        if (!$image) {
            if (request()->is('api/*')) {
                $defaultPath = null;
            }

            $this->hasResolvedProfileImageFullPath = true;

            return $this->resolvedProfileImageFullPath = $defaultPath;
        }

        $s3Storage = $this->storage;
        $path = '';

        if($this->user_type == 'admin-employee'){
            $path = 'employee/profile/';
        }else if($this->user_type == 'customer' || $this->user_type == 'super-admin'){
            $path = 'user/profile_image/';
        }else if($this->user_type == 'provider-serviceman'){
            $path = 'serviceman/profile/';
        }

        $imagePath = resolve_stored_media_key($image, $path);

        $this->hasResolvedProfileImageFullPath = true;

        return $this->resolvedProfileImageFullPath = getSingleImageFullPath(imagePath: $imagePath, s3Storage: $s3Storage, defaultPath: $defaultPath);
    }

    public function getIdentificationImageFullPathAttribute()
    {
        $path = match ($this->user_type) {
            'admin-employee' => 'employee/identity/',
            'provider-admin' => 'provider/identity/',
            'provider-serviceman' => 'serviceman/identity/',
            default => 'provider/identity/',
        };

        return getIdentityImageFullPath(
            identityImages: $this->identification_image ?? [],
            path: $path,
            defaultPath: request()->is('api/*') ? null : asset('assets/admin-module/img/media/provider-id.png')
        );
    }

    public function tutorials()
    {
        return $this->hasMany(SettingsTutorials::class);
    }

    public function getTutorialByPlatform($platform)
    {
        return $this->tutorials()->where('platform', $platform)->first();
    }


    public static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $model->ref_code = generate_referer_code();
        });

        self::created(function ($model) {
            $account = new Account();
            $account->user_id = $model->id;
            $account->save();
        });

        self::updating(function ($model) {
            if ($model->isDirty('is_active')) {
                if ($model->is_active == 0){
                    $model->fcm_token = '';
                }
            }
        });

        self::updated(function ($model) {
            if ($model->isDirty('is_active')) {

                if ($model->is_active == 0){

                    $title = translate('Your account has been deactivated! Please contact with admin');
                    if ($model->fcm_token && $title) {
                        device_notification($model->fcm_token, $title, null, null, null, 'logout', null, $model->id);
                    }

                    $model->tokens->each(function ($token, $key) {
                        $token->revoke();
                    });
                }
            }
        });

        self::deleting(function ($model) {
            // ... code here
        });

        self::deleted(function ($model) {
            // ... code here
        });

        static::saved(function ($model) {
            $storageType = getDisk();
            if($model->isDirty('profile_image') && $storageType != 'public'){
                saveSingleImageDataToStorage(model: $model, modelColumn : 'profile_image', storageType : $storageType);
            }
        });
    }
}
