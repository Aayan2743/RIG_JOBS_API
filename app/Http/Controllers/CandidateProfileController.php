<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\CandidateResume;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

use Symfony\Component\HttpFoundation\BinaryFileResponse;


class CandidateProfileController extends Controller
{






public function index(Request $request)
{
    $candidate = Candidate::with([
        'skills',
        'experiences',
        'educations.education',
        'educations.course',
        'educations.specialization',
        'certifications',
        'resumes'
    ])->where('user_id', auth()->id())->first();

    // 🔥 EXPERIENCE TRANSFORM (same as yours)
    if ($candidate && $candidate->experiences) {
        $candidate->experiences->transform(function ($exp) {

            if ($exp->start_date) {
                $exp->start_year = date('Y', strtotime($exp->start_date));
                $exp->start_month = date('n', strtotime($exp->start_date));
            }

            if ($exp->end_date) {
                $exp->end_year = date('Y', strtotime($exp->end_date));
                $exp->end_month = date('n', strtotime($exp->end_date));
            }

            $exp->current = $exp->is_current ? "yes" : "no";

            return $exp;
        });
    }

    // 🔥 EDUCATION TRANSFORM (NEW)
    if ($candidate && $candidate->educations) {
        $candidate->educations->transform(function ($edu) {

            $edu->education_name = $edu->education->name ?? null;
            $edu->course_name = $edu->course->name ?? null;
            $edu->specialization_name = $edu->specialization->name ?? null;

            return $edu;
        });
    }




if ($candidate && $candidate->profile_pic) {
    $candidate->profile_pic_url = asset('storage/' . $candidate->profile_pic);
    //  dd( $candidate->profile_pic_url = asset('storage/' . $candidate->profile_pic));

}


$completion = 0;
$total = 100;

// 🔹 Profile Pic
if (!empty($candidate->profile_pic)) {
    $completion += 10;
}

// 🔹 Location
if (!empty($candidate->location)) {
    $completion += 10;
}

// 🔹 Phone
if (!empty($candidate->phone)) {
    $completion += 10;
}

// 🔹 Skills
if ($candidate->skills && $candidate->skills->count() > 0) {
    $completion += 20;
}

// 🔹 Education
if ($candidate->educations && $candidate->educations->count() > 0) {
    $completion += 20;
}

// 🔹 Experience
if ($candidate->experiences && $candidate->experiences->count() > 0) {
    $completion += 20;
}

// 🔹 Resume
if ($candidate->resumes && $candidate->resumes->count() > 0) {
    $completion += 10;
}

// ✅ Add to response
$candidate->profile_completion = $completion;

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


    public function updateCertification(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    $candidate = Candidate::where('user_id', auth()->id())->firstOrFail();

    $cert = $candidate->certifications()->find($id);

    if (!$cert) {
        return response()->json([
            'success' => false,
            'message' => 'Certification not found'
        ], 404);
    }

    $cert->update([
        'name' => $request->name
    ]);

    return response()->json([
        'success' => true,
        'data' => $cert
    ]);
    }





public function uploadResume(Request $request)
{
    $validator = Validator::make($request->all(), [
        'resume' => 'required|file|mimes:pdf,doc,docx|max:2048'
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

    $file = $request->file('resume');

    // 🔥 Check existing resume
    $existingResume = $candidate->resumes()->first();

    if ($existingResume) {
        // Delete old file
        if (!empty($existingResume->file_path) && Storage::disk('public')->exists($existingResume->file_path)) {
            Storage::disk('public')->delete($existingResume->file_path);
        }

        // Delete old record (or you can update instead)
        $existingResume->delete();
    }

    // 📂 Upload new file
    $path = $file->store('resumes', 'public');

    // 💾 Save new record
    $resume = $candidate->resumes()->create([
        'file_path' => $path,
        'file_name' => $file->getClientOriginalName()
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Resume uploaded successfully',
        'data' => $resume
    ]);
}






    public function deleteResume($id)
{
    $candidate = Candidate::where('user_id', auth()->id())->firstOrFail();

    $resume = $candidate->resumes()->find($id);

    if (!$resume) {
        return response()->json([
            'success' => false,
            'message' => 'Resume not found'
        ], 404);
    }

    Storage::disk('public')->delete($resume->file_path);

    $resume->delete();

    return response()->json([
        'success' => true
    ]);
    }






public function downloadResume($id)
{
    $candidate = Candidate::where('user_id', auth()->id())->firstOrFail();

    $resume = $candidate->resumes()->find($id);

    if (!$resume) {
        return response()->json([
            'success' => false,
            'message' => 'Resume not found'
        ], 404);
    }

    // $path = storage_path('app/public/' . $resume->file_path);

   $path= asset('storage/' . $resume->file_path);


    // dd($path);
    if (!file_exists($path)) {
        return response()->json([
            'success' => false,
            'message' => 'File not found'
        ], 404);
    }

    // 🔥 FORCE RAW DOWNLOAD (THIS WILL FIX 100%)
    return response()->streamDownload(function () use ($path) {
        echo file_get_contents($path);
    }, $resume->file_name, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="'.$resume->file_name.'"',
    ]);
}



public function uploadImage(Request $request)
{


     $validator = Validator::make($request->all(), [
           'profile_image' => 'required|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

    $candidate = Candidate::where('user_id', auth()->id())->first();

    if (!$candidate) {
        return response()->json([
            'success' => false,
            'message' => 'Candidate not found'
        ], 404);
    }

    // 🔥 Delete old image
    if ($candidate->profile_image && Storage::disk('public')->exists($candidate->profile_image)) {
        Storage::disk('public')->delete($candidate->profile_image);
    }

    // 📂 Upload new image
    $path = $request->file('profile_image')->store('profile_images', 'public');

    $candidate->update([
        'profile_pic' => $path
    ]);

    return response()->json([
        'success' => true,
        'data' => $candidate
    ]);
}




public function viewResume($id)
{
    $resume = \App\Models\CandidateResume::find($id);

    if (!$resume) {
        return response()->json([
            'success' => false,
            'message' => 'Resume not found'
        ], 404);
    }

    $path = storage_path('app/public/' . $resume->file_path);

    return response()->file($path, [
        'Content-Type' => mime_content_type($path),
        'Content-Disposition' => 'inline; filename="'.$resume->file_name.'"',
    ]);
}



public function updateProfile(Request $request)
{
    $candidate = Candidate::where('user_id', auth()->id())->first();

    if (!$candidate) {
        return response()->json([
            'success' => false,
            'message' => 'Candidate not found'
        ], 404);
    }

    $validator = Validator::make($request->all(), [
        'location' => 'nullable|string|max:255',

        // ✅ phone editable with validation
        'phone' => 'nullable|digits:10',

        // ❌ email NOT allowed
        'email' => 'prohibited',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first()
        ], 422);
    }

    // 🔥 update only allowed fields
    $candidate->update($request->only(['location', 'phone']));

    return response()->json([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => $candidate
    ]);
}

}














