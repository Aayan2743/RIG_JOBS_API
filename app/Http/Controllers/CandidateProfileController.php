<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CandidateProfileController extends Controller
{
     public function indexsssss(Request $request)
    {
        $perPage = $request->get('per_page', 10);



        $candidate = Candidate::with([
            'skills',
            'experiences',
            'educations',
            'certifications',
            'resumes'
        ])->where('user_id', auth()->id())->first();

        return response()->json([
            'success' => true,
            'data' => $candidate
        ]);
    }


    public function index(Request $request)
{
    $candidate = Candidate::with([
        'skills',
        'experiences',
        'educations',
        'certifications',
        'resumes'
    ])->where('user_id', auth()->id())->first();

    // 🔥 TRANSFORM EXPERIENCES (IMPORTANT)
    if ($candidate && $candidate->experiences) {
        $candidate->experiences->transform(function ($exp) {

            // ✅ start_date → start_year & start_month
            if ($exp->start_date) {
                $exp->start_year = date('Y', strtotime($exp->start_date));
                $exp->start_month = date('n', strtotime($exp->start_date));
            } else {
                $exp->start_year = null;
                $exp->start_month = null;
            }

            // ✅ end_date → end_year & end_month
            if ($exp->end_date) {
                $exp->end_year = date('Y', strtotime($exp->end_date));
                $exp->end_month = date('n', strtotime($exp->end_date));
            } else {
                $exp->end_year = null;
                $exp->end_month = null;
            }

            // ✅ is_current → current (yes/no)
            $exp->current = $exp->is_current ? "yes" : "no";

            return $exp;
        });
    }

    return response()->json([
        'success' => true,
        'data' => $candidate
    ]);
}

    // ✅ ABOUT (CREATE/UPDATE)
    public function saveAbout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'about' => 'required|string',
            'phone' => 'nullable|string',
            'location' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $candidate = Candidate::updateOrCreate(
            ['user_id' => auth()->id()],
            $request->only(['about', 'phone', 'location'])
        );

        return response()->json([
            'success' => true,
            'data' => $candidate
        ]);
    }

    // ✅ SKILLS (REPLACE ALL)
    public function saveSkills(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'skills' => 'required|array'
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

        $candidate->skills()->delete();

        foreach ($request->skills as $skill) {
            $candidate->skills()->create(['skill' => $skill]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Skills saved'
        ]);
    }

    // ✅ EXPERIENCE LIST + SEARCH + PAGINATION
    public function experiences(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search = $request->get('search');

        $candidate = Candidate::where('user_id', auth()->id())->firstOrFail();

        $query = $candidate->experiences();

        if ($search) {
            $query->where('company', 'like', "%$search%");
        }

        $data = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // ➕ STORE EXPERIENCE





public function storeExperience(Request $request)
{
    $validator = Validator::make($request->all(), [

        'company' => 'required|string|max:255',
        'title'   => 'required|string|max:255',

        'location' => 'nullable|string|max:255',
        'description' => 'required|string',

        'current' => 'required|in:yes,no',
        'employment_type' => 'required|in:full-time,internship',

        'start_year'  => 'required|digits:4|integer|min:1950|max:' . date('Y'),
        'start_month' => 'required|integer|min:1|max:12',

        'end_year'  => 'required_if:current,no|nullable|digits:4|integer|min:1950|max:' . date('Y'),
        'end_month' => 'required_if:current,no|nullable|integer|min:1|max:12',

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


    // 🔥 Convert start_date
$startDate = $request->start_year . '-' .
    str_pad($request->start_month, 2, '0', STR_PAD_LEFT) . '-01';

// 🔥 Convert end_date
$endDate = null;
if ($request->current === 'no') {
    $endDate = $request->end_year . '-' .
        str_pad($request->end_month, 2, '0', STR_PAD_LEFT) . '-01';
}

$exp = $candidate->experiences()->create([
    'company' => $request->company,
    'title'   => $request->title,
    // 'location'=> $request->location,
    'description' => $request->description,

    'job_type' => $request->employment_type,

    // ✅ STORE IN DB
    'start_date' => $startDate,
    'end_date'   => $endDate,

    'is_current' => $request->current === 'yes' ? 1 : 0,
]);

    // $exp = $candidate->experiences()->create([
    //     'company' => $request->company,
    //     'title'   => $request->title,
    //     'location'=> $request->location,
    //     'description' => $request->description,

    //     // ✅ EXACT SAME FIELDS
    //     'current' => $request->current,
    //     'employment_type' => $request->employment_type,

    //     'start_year'  => $request->start_year,
    //     'start_month' => $request->start_month,

    //     'end_year'  => $request->current === 'no' ? $request->end_year : null,
    //     'end_month' => $request->current === 'no' ? $request->end_month : null,
    // ]);

    return response()->json([
        'success' => true,
        'data' => $exp
    ]);
}

    // ✏️ UPDATE EXPERIENCE


    public function updateExperience(Request $request, $id)
{
    $validator = Validator::make($request->all(), [

        'company' => 'required|string|max:255',
        'title'   => 'required|string|max:255',

        'location' => 'nullable|string|max:255',
        'description' => 'required|string',

        'current' => 'required|in:yes,no',
        'employment_type' => 'required|in:full-time,internship',

        'start_year'  => 'required|digits:4|integer|min:1950|max:' . date('Y'),
        'start_month' => 'required|integer|min:1|max:12',

        'end_year'  => 'required_if:current,no|nullable|digits:4|integer|min:1950|max:' . date('Y'),
        'end_month' => 'required_if:current,no|nullable|integer|min:1|max:12',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    $candidate = Candidate::where('user_id', auth()->id())->firstOrFail();

    $exp = $candidate->experiences()->find($id);

    if (!$exp) {
        return response()->json([
            'success' => false,
            'message' => 'Experience not found'
        ], 404);
    }

    // 🔥 Convert start_date
    $startDate = $request->start_year . '-' .
        str_pad($request->start_month, 2, '0', STR_PAD_LEFT) . '-01';

    // 🔥 Convert end_date
    $endDate = null;
    if ($request->current === 'no') {
        $endDate = $request->end_year . '-' .
            str_pad($request->end_month, 2, '0', STR_PAD_LEFT) . '-01';
    }

    // 🔥 UPDATE
    $exp->update([
        'company' => $request->company,
        'title'   => $request->title,
        // 'location'=> $request->location,
        'description' => $request->description,

        'job_type' => $request->employment_type,

        'start_date' => $startDate,
        'end_date'   => $endDate,

        'is_current' => $request->current === 'yes' ? 1 : 0,
    ]);

    return response()->json([
        'success' => true,
        'data' => $exp
    ]);
}

    // ❌ DELETE EXPERIENCE
    public function deleteExperience($id)
    {
        $candidate = Candidate::where('user_id', auth()->id())->firstOrFail();

        $exp = $candidate->experiences()->findOrFail($id);
        $exp->delete();

        return response()->json([
            'success' => true
        ]);
    }

    // ✅ CERTIFICATIONS CRUD
    public function storeCertification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $candidate = Candidate::firstOrCreate([
            'user_id' => auth()->id()
        ]);

        $cert = $candidate->certifications()->create([
            'name' => $request->name
        ]);

        return response()->json(['success' => true, 'data' => $cert]);
    }

    public function deleteCertification($id)
    {
        $candidate = Candidate::where('user_id', auth()->id())->firstOrFail();

        $cert = $candidate->certifications()->findOrFail($id);
        $cert->delete();

        return response()->json(['success' => true]);
    }
}
