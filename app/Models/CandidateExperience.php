<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateExperience extends Model
{
     protected $fillable = [
        'candidate_id',
        'company',
        'title',
        'location',
        'description',
        'start_date',
        'end_date',
        'is_current',
        'job_type',
    ];
}
