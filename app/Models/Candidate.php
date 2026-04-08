<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    protected $fillable = ['user_id', 'about', 'phone', 'location'];

    public function skills()
    {
        return $this->hasMany(CandidateSkill::class);
    }

    public function experiences()
    {
        return $this->hasMany(CandidateExperience::class);
    }

    public function educations()
    {
        return $this->hasMany(CandidateEducation::class);
    }

    public function certifications()
    {
        return $this->hasMany(CandidateCertification::class);
    }

    public function resumes()
    {
        return $this->hasMany(CandidateResume::class);
    }
}
