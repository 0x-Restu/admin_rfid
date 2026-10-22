<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SecurityAuditLog extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'action',
        'severity',
        'status',
        'ip_address',
        'user_agent',
        'target_type',
        'target_id',
        'details',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'details' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function record(
        string $action,
        string $status = 'SUCCESS',
        string $severity = 'INFO',
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $details = null,
        ?int $userId = null
    ): self {
        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'severity' => $severity,
            'status' => $status,
            'ip_address' => request()->ip(),
            'user_agent' => substr(request()->userAgent() ?? 'Unknown', 0, 255),
            'target_type' => $targetType,
            'target_id' => $targetId,
            'details' => $details,
        ]);
    }
}
