<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RfidCard extends Model
{
    use HasFactory;

    protected $table = 'rfid_cards';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'student_id',
        'card_uid',
        'pin_hash',
        'status',
        'balance',
        'daily_spending_limit',
        'issued_at',
        'last_tapped_at',
        'blocked_reason',
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
            'balance' => 'decimal:2',
            'daily_spending_limit' => 'decimal:2',
            'issued_at' => 'datetime',
            'last_tapped_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function canteenTransactions()
    {
        return $this->hasMany(CanteenTransaction::class, 'card_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'card_id');
    }

    public function topups()
    {
        return $this->hasMany(CardTopup::class, 'card_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    public function getFormattedBalanceAttribute(): string
    {
        return 'Rp ' . number_format($this->balance, 0, ',', '.');
    }

    public function getFormattedLimitAttribute(): string
    {
        return 'Rp ' . number_format($this->daily_spending_limit, 0, ',', '.');
    }
}
