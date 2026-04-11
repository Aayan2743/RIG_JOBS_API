<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
     protected $fillable = [
        'name',
        'slug',
        'status',
        'image',
    ];

        public function companies()
{
    return $this->hasMany(Company::class);
}
}
