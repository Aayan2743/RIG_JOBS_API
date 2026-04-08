<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateSkill extends Model
{
      protected $fillable = ['candidate_id', 'skill'];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
