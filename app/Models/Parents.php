<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Parents extends Model
{
    use HasFactory;

    protected $table = 'parents';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'full_name',
        'phone_number',
        'email',
        'address',
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

    public function students()
    {
        return $this->hasMany(Student::class, 'parent_id');
    }
}
