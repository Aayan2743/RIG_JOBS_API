<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    protected $fillable = ['course_id', 'name', 'status'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
