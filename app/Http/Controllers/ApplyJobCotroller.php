<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\JobApplication;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;

class ApplyJobCotroller extends Controller
{
    public function applyJob(Request $request)
{
    $user = auth()->user();

    // 🔥 CHECK PAYMENT FIRST
    if (!Payment::where('user_id', $user->id)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Please pay application fee first'
        ], 403);
    }

    // 🔥 PREVENT DUPLICATE APPLY
    $exists = JobApplication::where('user_id', $user->id)
        ->where('job_id', $request->job_id)
        ->exists();

    if ($exists) {
        return response()->json([
            'success' => false,
            'message' => 'Already applied for this job'
        ]);
    }

    // ✅ SAVE APPLICATION
    JobApplication::create([
        'user_id' => $user->id,
        'job_id' => $request->job_id,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Applied successfully'
    ]);
}


public function checkApplied(Request $request)
{
    $user = auth()->user();

    // 🔥 validate input
    if (!$request->job_id) {
        return response()->json([
            'success' => false,
            'message' => 'Job ID required'
        ], 400);
    }

    $exists = JobApplication::where('user_id', $user->id)
        ->where('job_id', $request->job_id)
        ->exists();

    return response()->json([
        'success' => true,
        'already_applied' => $exists
    ]);
}


public function myApplications()
{
    $data = JobApplication::where('user_id', auth()->id())
        ->select('job_id')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}




public function getApplicantss($jobId)
{
    $data = JobApplication::with('user')
        ->where('job_id', $jobId)
        ->latest()
        ->get()
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->user->name,
                'email' => $item->user->email,
                'phone' => $item->user->phone ?? '',
                'location' => $item->user->location ?? '',
                'applied' => $item->created_at->format('Y-m-d'),
                'experience' => $item->user->experience ?? 'N/A',
                'status' => $item->status ?? 'New',
                'rating' => 4, // optional
                'avatar' => strtoupper(substr($item->user->name, 0, 2)),
                'resume' => true, // if you store resume path
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}





public function getApplicants_w($jobId)
{
    $applications = \App\Models\JobApplication::with(['user.candidate'])
        ->where('job_id', $jobId)
        ->latest()
        ->get();

    $data = $applications->map(function ($app) {

        $user = $app->user;


        $candidate = $user->candidate; // 🔥 from candidate table



        // ✅ EXPERIENCE
        $experiences = \DB::table('candidate_experiences')
            ->where('candidate_id', $user->id)
            ->get();

        $totalMonths = 0;

        foreach ($experiences as $exp) {
            if ($exp->start_date) {
                $start = Carbon::parse($exp->start_date);
                $end = $exp->is_current
                    ? now()
                    : ($exp->end_date ? Carbon::parse($exp->end_date) : now());

                $totalMonths += $start->diffInMonths($end);
            }
        }

        $years = floor($totalMonths / 12);
        $months = $totalMonths % 12;


        $resume = null;

if ($candidate) {
    $resume = DB::table('candidate_resumes')
        ->where('candidate_id', $candidate->id)
        ->latest()
        ->first();
}

$resumeUrl = $resume
    ? asset('storage/' . $resume->file_path)
    : null;





        // ✅ PROFILE PIC FROM candidate table
        $profilePic = $candidate && $candidate->profile_pic
            ? asset('storage/' . $candidate->profile_pic)
            : null;

        return [
            'id' => $app->id,
            'name' => $user->name,
            'email' => $user->email,

            // 🔥 FROM candidate table
            'phone' => $candidate->phone ?? '',
            'location' => $candidate->location ?? '',

            'experience' => $years . ' yrs ' . $months . ' months',


            'status' => $app->status ?? 'New',
            'applied' => $app->created_at->format('Y-m-d'),

            'profile_pic' => $profilePic,
            'avatar' => strtoupper(substr($user->name, 0, 2)),

            'resume' => $resumeUrl ? true : false,
            'resume_link' => $resumeUrl,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}

public function getApplicants(Request $request, $jobId)
{
    $perPage = $request->get('per_page', 10);
    $search  = $request->get('search');

    // =========================
    // ✅ BASE QUERY
    // =========================
    $query = \App\Models\JobApplication::with(['user.candidate'])
        ->where('job_id', $jobId);

    // =========================
    // 🔍 SEARCH
    // =========================
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->whereHas('user', function ($q2) use ($search) {
                $q2->where('name', 'like', "%$search%")
                   ->orWhere('email', 'like', "%$search%");
            })
            ->orWhereHas('user.candidate', function ($q3) use ($search) {
                $q3->where('phone', 'like', "%$search%")
                   ->orWhere('location', 'like', "%$search%");
            });
        });
    }

    // =========================
    // 📄 PAGINATION
    // =========================
    $applications = $query->latest()->paginate($perPage);

    // =========================
    // 🔥 GET CANDIDATE IDS
    // =========================
    $candidateIds = collect($applications->items())->map(function ($app) {
        return optional($app->user->candidate)->id;
    })->filter()->toArray();

    // =========================
    // ✅ EXPERIENCES
    // =========================
    $experiences = DB::table('candidate_experiences')
        ->whereIn('candidate_id', $candidateIds)
        ->get()
        ->groupBy('candidate_id');

    // =========================
    // ✅ RESUMES
    // =========================
    $resumes = DB::table('candidate_resumes')
        ->whereIn('candidate_id', $candidateIds)
        ->latest()
        ->get()
        ->groupBy('candidate_id');

    // =========================
    // 🎯 FORMAT DATA
    // =========================
    $data = collect($applications->items())->map(function ($app) use ($experiences, $resumes) {

        $user = $app->user;
        $candidate = $user->candidate;

        // 🔥 EXPERIENCE CALCULATION
        $totalMonths = 0;

        if ($candidate && isset($experiences[$candidate->id])) {
            foreach ($experiences[$candidate->id] as $exp) {

                if ($exp->start_date) {
                    $start = Carbon::parse($exp->start_date);

                    $end = $exp->is_current
                        ? now()
                        : ($exp->end_date ? Carbon::parse($exp->end_date) : now());

                    $totalMonths += $start->diffInMonths($end);
                }
            }
        }

        $years = floor($totalMonths / 12);
        $months = $totalMonths % 12;

        $experienceText = $totalMonths > 0
            ? "{$years} yrs" . ($months ? " {$months} months" : "")
            : "Fresher";

        // 🔥 RESUME
        $resume = null;

        if ($candidate && isset($resumes[$candidate->id])) {
            $resume = $resumes[$candidate->id][0] ?? null;
        }

        $resumeUrl = $resume
            ? asset('storage/' . $resume->file_path)
            : null;

        // 🔥 PROFILE PIC
        $profilePic = $candidate && $candidate->profile_pic
            ? asset('storage/' . $candidate->profile_pic)
            : null;

        return [
            'id' => $app->id,
            'name' => $user->name,
            'email' => $user->email,

            'phone' => $candidate->phone ?? '',
            'location' => $candidate->location ?? '',

            'experience' => $experienceText,

            'status' => $app->status ?? 'New',
            'applied' => optional($app->created_at)->format('Y-m-d'),

            'profile_pic' => $profilePic,
            'avatar' => strtoupper(substr($user->name ?? 'U', 0, 2)),

            'resume' => $resumeUrl ? true : false,
            'resume_link' => $resumeUrl,
        ];
    });

    // =========================
    // ✅ FINAL RESPONSE
    // =========================
    return response()->json([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $applications->currentPage(),
            'last_page' => $applications->lastPage(),
            'per_page' => $applications->perPage(),
            'total' => $applications->total(),
        ]
    ]);
}

public function updateStatus(Request $request)
{


      $validator = Validator::make($request->all(), [
        'application_id' => 'required|exists:job_applications,id',
        'status' => 'required|in:New,Under Review,Shortlisted,Rejected',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }




    $application = JobApplication::findOrFail($request->application_id);

    $application->status = $request->status;
    $application->save();

    return response()->json([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
}





}
