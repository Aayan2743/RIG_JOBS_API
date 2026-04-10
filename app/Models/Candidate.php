<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{

    public $table = 'candidates';

    protected $fillable = ['user_id', 'about', 'phone', 'job_type','profile_pic','location'];

    public function skills()
    {
        return $this->hasMany(CandidateSkill::class);
    }

    public function experiences()
    {
        return $this->hasMany(CandidateExperience::class,'candidate_id');
    }

    public function educations()
    {
        return $this->hasMany(CandidateEducation::class,'candidate_id');
    }

    public function certifications()
    {
        return $this->hasMany(CandidateCertification::class);
    }



    public function resumes()
    {
        return $this->hasMany(CandidateResume::class);
    }

    public function resume()
{
    return $this->hasOne(CandidateResume::class);
}
}
