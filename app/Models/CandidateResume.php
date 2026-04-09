<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateResume extends Model
{
    protected $fillable = ['candidate_id', 'file_path', 'file_name'];
}
