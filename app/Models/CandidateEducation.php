<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateEducation extends Model
{

 protected $table = 'candidate_educations';
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


    public function education()
{
    return $this->belongsTo(Education::class, 'education_id');
}

public function course()
{
    return $this->belongsTo(Course::class, 'course_id');
}

public function specialization()
{
    return $this->belongsTo(Specialization::class, 'specialization_id');
}
}
