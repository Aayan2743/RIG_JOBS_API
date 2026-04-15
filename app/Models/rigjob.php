<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class rigjob extends Model
{
    protected $guarded = [

    ];

    public function category()
{
    return $this->belongsTo(Category::class);
}

public function company()
{
    return $this->belongsTo(Company::class);
}


public function applications()
{
    return $this->hasMany(\App\Models\JobApplication::class, 'job_id');
}


protected $casts = [
    // 'requirements' => 'array',
    'skills' => 'array',
    'benefits' => 'array',
     'dead_line' => 'datetime',
];




}
