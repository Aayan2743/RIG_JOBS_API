<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateEducation extends Model
{
     protected $fillable = [
        'candidate_id',
        'education_id',
        'course_id',
        'specialization_id',
        'institution',
        'start_year',
        'end_year',
        'course_type',
        'grading_system'
    ];
}
