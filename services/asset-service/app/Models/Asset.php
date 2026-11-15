<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Asset extends Model
{
    use SoftDeletes;

    public const TYPE_IMMOBILIER = 'immobilier';
    public const TYPE_MOBILIER = 'mobilier';
    public const TYPE_VEHICULE = 'vehicule';

    public const TYPES = [
        self::TYPE_IMMOBILIER,
        self::TYPE_MOBILIER,
        self::TYPE_VEHICULE,
    ];

    public const TYPE_REFERENCE_PREFIX = [
        self::TYPE_IMMOBILIER => 'IMM',
        self::TYPE_MOBILIER => 'MOB',
        self::TYPE_VEHICULE => 'VEH',
    ];

    public const STATUS_BROUILLON = 'brouillon';
    public const STATUS_EN_VALIDATION = 'en_validation';
    public const STATUS_ACTIF = 'actif';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_HORS_SERVICE = 'hors_service';
    public const STATUS_REFORME = 'reforme';

    public const STATUSES = [
        self::STATUS_BROUILLON,
        self::STATUS_EN_VALIDATION,
        self::STATUS_ACTIF,
        self::STATUS_MAINTENANCE,
        self::STATUS_HORS_SERVICE,
        self::STATUS_REFORME,
    ];

    /**
     * Statuts modifiables directement via l'API.
     * `en_validation` est réservé au workflow de soumission.
     */
    public const MANUAL_STATUSES = [
        self::STATUS_BROUILLON,
        self::STATUS_ACTIF,
        self::STATUS_MAINTENANCE,
        self::STATUS_HORS_SERVICE,
        self::STATUS_REFORME,
    ];

    protected $fillable = [
        'reference',
        'qr_token',
        'type',
        'designation',
        'description',
        'category',
        'acquisition_date',
        'origin',
        'value',
        'value_type',
        'status',
        'etat',
        'direction_id',
        'service_id',
        'custodian_user_id',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'value' => 'decimal:2',
            'qr_token' => 'string',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Asset $asset) {
            if (empty($asset->qr_token)) {
                $asset->qr_token = (string) Str::uuid();
            }
        });
    }

    /**
     * Génère la référence métier de façon atomique.
     * Doit être appelée dans une transaction.
     */
    public static function generateReference(string $type): string
    {
        $prefix = self::TYPE_REFERENCE_PREFIX[$type] ?? strtoupper(substr($type, 0, 3));
        $number = ReferenceCounter::next($prefix);

        return sprintf('PG-%s-%04d', $prefix, $number);
    }

    public function realEstateDetail(): HasOne
    {
        return $this->hasOne(RealEstateDetail::class);
    }

    public function movableDetail(): HasOne
    {
        return $this->hasOne(MovableDetail::class);
    }

    public function vehicleDetail(): HasOne
    {
        return $this->hasOne(VehicleDetail::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(AssetLocation::class);
    }

    public function currentLocation(): HasOne
    {
        return $this->hasOne(AssetLocation::class)->where('is_current', true)->latestOfMany();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    public function pendingAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)
            ->where('status', AssetAssignment::STATUS_EN_ATTENTE)
            ->latestOfMany();
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(AssetTransfer::class);
    }

    public function pendingTransfer(): HasOne
    {
        return $this->hasOne(AssetTransfer::class)
            ->where('status', AssetTransfer::STATUS_EN_ATTENTE)
            ->latestOfMany();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssetSubmission::class);
    }

    public function pendingSubmission(): HasOne
    {
        return $this->hasOne(AssetSubmission::class)
            ->where('status', AssetSubmission::STATUS_EN_ATTENTE)
            ->latestOfMany();
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(AssetMaintenance::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(AssetIncident::class);
    }

    /**
     * Détail spécifique selon le type du bien.
     */
    public function details(): ?Model
    {
        return match ($this->type) {
            self::TYPE_IMMOBILIER => $this->realEstateDetail,
            self::TYPE_MOBILIER => $this->movableDetail,
            self::TYPE_VEHICULE => $this->vehicleDetail,
            default => null,
        };
    }
}
