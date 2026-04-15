<?php

namespace App\Http\Controllers;

use App\Models\SavedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SavedJobController extends Controller
{
public function store(Request $request)
{



     $validator = Validator::make($request->all(), [

              'job_id' => 'required|exists:rigjobs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }


    $userId = auth()->id(); // or $request->user()->id

    $saved = SavedJob::firstOrCreate([
        'user_id' => $userId,
        'job_id' => $request->job_id,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Job saved successfully',
        'data' => $saved
    ]);
}


public function destroy(Request $request)
{

     $validator = Validator::make($request->all(), [

           'job_id' => 'required|exists:rigjobs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }



    SavedJob::where('user_id', auth()->id())
        ->where('job_id', $request->job_id)
        ->delete();

    return response()->json([
        'success' => true,
        'message' => 'Job removed from saved list'
    ]);
}


public function index(Request $request)
{
    $query = SavedJob::with(['job.company'])
        ->where('user_id', auth()->id());

    // 🔍 Search (safe + trimmed)
    if ($request->filled('search')) {
        $search = trim($request->search);

        $query->whereHas('job', function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhereHas('company', function ($q2) use ($search) {
                  $q2->where('company_name', 'like', "%{$search}%");
              });
        });
    }

    // 📄 Pagination
    $perPage = $request->per_page ?? 10;

    $jobs = $query->latest()->paginate($perPage);

    // 🎯 FORMAT RESPONSE
    $data = collect($jobs->items())->map(function ($item) {

        $job = $item->job;

        // ⚠️ Safety check
        if (!$job) return null;

        // ✅ Logo Handling (FULL URL + fallback)
        $logo = null;

        if ($job->company && $job->company->logo) {
            // if stored in storage/app/public
            $logo = asset('storage/' . $job->company->logo);
        } else {
            // default fallback image (put this in public/images/)
            $logo = asset('images/default-company.png');
        }

        return [
            'id' => $job->id,
            'title' => $job->title ?? '',
            'company' => $job->company->company_name ?? '',
            'location' => $job->location ?? 'N/A',
            'salary' => ($job->salary_min && $job->salary_max)
                ? '$' . $job->salary_min . '–$' . $job->salary_max
                : 'Not disclosed',
            'type' => $job->job_type ?? '',
            'logo' => $logo, // ✅ fixed here
            'savedDate' => optional($item->created_at)->diffForHumans(),
        ];

    })->filter()->values();

    return response()->json([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $jobs->currentPage(),
            'last_page' => $jobs->lastPage(),
            'per_page' => $jobs->perPage(),
            'total' => $jobs->total(),
        ]
    ]);
}






public function check($jobId)
{
    $exists = SavedJob::where('user_id', auth()->id())
        ->where('job_id', $jobId)
        ->exists();

    return response()->json([
        'saved' => $exists
    ]);
}



}
