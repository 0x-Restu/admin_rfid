<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Attendance extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'student_id',
        'card_id',
        'date',
        'tap_in_time',
        'tap_out_time',
        'gate_name',
        'status',
        'is_late',
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
            'date' => 'date',
            'tap_in_time' => 'datetime',
            'tap_out_time' => 'datetime',
            'is_late' => 'boolean',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function card()
    {
        return $this->belongsTo(RfidCard::class, 'card_id');
    }
}
