<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedJob extends Model
{
    protected $fillable = ['user_id', 'job_id'];

    public function job()
    {
        return $this->belongsTo(rigjob::class, 'job_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
