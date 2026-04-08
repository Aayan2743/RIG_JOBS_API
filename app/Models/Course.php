<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    public $table = 'courses';
protected $fillable = ['education_id', 'name', 'status'];



    public function education()
{
    return $this->belongsTo(Education::class)->withDefault();
}

    public function specializations()
    {
        return $this->hasMany(Specialization::class);
    }
}
