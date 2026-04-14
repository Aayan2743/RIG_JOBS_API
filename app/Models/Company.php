<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
      protected $guarded = [

    ];


    public function industry()
{
    return $this->belongsTo(Industry::class);
}



public function users()
{
    return $this->hasOne(User::class);
}


public function user()
{
    return $this->hasOne(User::class);
}
public function jobs()
{
    return $this->hasMany(rigjob::class);
}



protected $casts = [
    'social_links' => 'array',
    'culture_values' => 'array',
    'benefits_perks' => 'array',
];


protected static function boot()
{
    parent::boot();

    static::creating(function ($company) {
        $slug = Str::slug($company->company_name);
        $count = Company::where('slug', 'LIKE', "{$slug}%")->count();
        $company->slug = $count ? "{$slug}-{$count}" : $slug;
    });
}


}
