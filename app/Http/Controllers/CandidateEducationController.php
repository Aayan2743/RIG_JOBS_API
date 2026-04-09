<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Validator;
use App\Models\Candidate;
use App\Models\CandidateEducation;



class CandidateEducationController extends Controller
{
      public function index()
    {
        $candidate = Candidate::where('user_id', auth()->id())->first();

        if (!$candidate) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $candidate->educations
        ]);
    }

    // ✅ STORE
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'education_id' => 'required|integer',
            'course_id' => 'required|integer',
            'specialization_id' => 'nullable|integer',

            'institution' => 'required|string|max:255',

            'start_year' => 'required|digits:4|integer|min:1950|max:' . date('Y'),
            'end_year' => 'nullable|digits:4|integer|min:1950|max:' . date('Y'),

            'course_type' => 'required|in:Full time,Part time,Distance',

            'grading_system' => 'nullable|in:cgpa_10,cgpa_4,percentage,grade',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $candidate = Candidate::firstOrCreate([
            'user_id' => auth()->id()
        ]);

        $education = CandidateEducation::create([
            'candidate_id' => $candidate->id,
            'education_id' => $request->education_id,
            'course_id' => $request->course_id,
            'specialization_id' => $request->specialization_id,
            'institution' => $request->institution,
            'start_year' => $request->start_year,
            'end_year' => $request->end_year,
            'course_type' => $request->course_type,
            'grading_system' => $request->grading_system,
        ]);

        return response()->json([
            'success' => true,
            'data' => $education
        ]);
    }

    // ✅ UPDATE
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [

            'education_id' => 'required|integer',
            'course_id' => 'required|integer',
            'specialization_id' => 'nullable|integer',

            'institution' => 'required|string|max:255',

            'start_year' => 'required|digits:4|integer|min:1950|max:' . date('Y'),
            'end_year' => 'nullable|digits:4|integer|min:1950|max:' . date('Y'),

            'course_type' => 'required|in:Full time,Part time,Distance',

            'grading_system' => 'nullable|in:cgpa_10,cgpa_4,percentage,grade',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $candidate = Candidate::where('user_id', auth()->id())->firstOrFail();

        $education = CandidateEducation::where('id', $id)
            ->where('candidate_id', $candidate->id)
            ->first();

        if (!$education) {
            return response()->json([
                'success' => false,
                'message' => 'Education not found'
            ], 404);
        }

        $education->update([
            'education_id' => $request->education_id,
            'course_id' => $request->course_id,
            'specialization_id' => $request->specialization_id,
            'institution' => $request->institution,
            'start_year' => $request->start_year,
            'end_year' => $request->end_year,
            'course_type' => $request->course_type,
            'grading_system' => $request->grading_system,
        ]);

        return response()->json([
            'success' => true,
            'data' => $education
        ]);
    }

    // ✅ DELETE
    public function destroy($id)
    {
        $candidate = Candidate::where('user_id', auth()->id())->firstOrFail();

        $education = CandidateEducation::where('id', $id)
            ->where('candidate_id', $candidate->id)
            ->first();

        if (!$education) {
            return response()->json([
                'success' => false,
                'message' => 'Education not found'
            ], 404);
        }

        $education->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }
}
