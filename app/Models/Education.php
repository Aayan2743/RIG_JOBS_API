<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{

    public $table = 'educations';
     protected $fillable = ['name', 'status'];

    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
