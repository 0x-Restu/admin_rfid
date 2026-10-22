<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Student extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'parent_id',
        'nisn',
        'full_name',
        'gender',
        'class_name',
        'school_name',
        'photo_url',
        'status',
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

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }

    public function rfidCard()
    {
        return $this->hasOne(RfidCard::class, 'student_id');
    }

    public function canteenTransactions()
    {
        return $this->hasMany(CanteenTransaction::class, 'student_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function sppBills()
    {
        return $this->hasMany(SppBill::class, 'student_id');
    }

    public function savings()
    {
        return $this->hasMany(StudentSaving::class, 'student_id');
    }

    public function getPhotoAttribute(): string
    {
        return $this->photo_url ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80';
    }
}
