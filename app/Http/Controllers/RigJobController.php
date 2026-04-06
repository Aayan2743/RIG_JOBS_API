<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

use App\Models\rigjob;
use Illuminate\Support\Facades\Validator;

class RigJobController extends Controller
{
     /**
     * ✅ Create Job
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            // 'company_id' => 'required|exists:companies,id',

            'title' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'job_type' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'experience_level' => 'required|string',

            'salary_min' => 'required|integer',
            'salary_max' => 'required|integer',

            'description' => 'required|string',

            'requirements' => 'required|string',
            'responsibilities' => 'required|string',
            // 'requirements.*' => 'string',

            'skills' => 'required|array',
            'skills.*' => 'string',

            'benefits' => 'required|array',
            'benefits.*' => 'string',

             'dead_line' => 'required|date|after_or_equal:today',

            // 'status' => 'required|in:draft,published',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }

        // $job = rigjob::create($request->all());
        $companyId = auth()->user()->company_id; // Get company_id from authenticated user

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'You are not associated with any company'
            ], 403);
        }
            $job = rigjob::create([
    'company_id'       => $companyId,
    'title'            => $request->title,
    'location'         => $request->location,
    'job_type'         => $request->job_type,
    'category_id'      => $request->category_id,
    'experience_level' => $request->experience_level,

    'salary_min'       => $request->salary_min,
    'salary_max'       => $request->salary_max,

    'description'      => $request->description,
     'requirements'     => $request->requirements,
    'responsibilities' => $request->responsibilities,
    // JSON fields

    'skills'           => $request->skills,
    'benefits'         => $request->benefits,
    'dead_line'         => $request->dead_line,

    'status'           => $request->status ?? 'published',
]);

        return response()->json([
            'success' => true,
            'message' => 'Job created successfully',
            'data' => $job
        ]);
    }

    /**
     * ✅ Get All Jobs (Pagination + Search)
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search  = $request->get('search');

        $query = rigjob::with(['company', 'category']);

        // 🔍 Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('location', 'like', "%$search%");
            });
        }

        // 🎯 Filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $jobs = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $jobs->items(),
            'pagination' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ]
        ]);
    }

    /**
     * ✅ Get Single Job
     */
    public function show($id)
    {
        $job = rigjob::with(['company', 'category'])->find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $job
        ]);
    }

    /**
     * ✅ Update Job
     */



    public function update(Request $request, $id)
{
    $job = rigjob::find($id);

    if (!$job) {
        return response()->json([
            'success' => false,
            'message' => 'Job not found'
        ], 404);
    }

    $user = auth()->user();

    // ✅ Check company access
    if (!$user || !$user->company_id || $job->company_id != $user->company_id) {
        return response()->json([
            'success' => false,
            'message' => 'Access denied'
        ], 403);
    }

    $validator = Validator::make($request->all(), [

        'title' => 'required|string|max:255',
        'location' => 'required|string|max:255',
        'job_type' => 'required|string',
        'category_id' => 'required|exists:categories,id',
        'experience_level' => 'required|string',

        'salary_min' => 'required|integer',
        'salary_max' => 'required|integer',

        'description' => 'required|string',

        'requirements' => 'required|string',
        'responsibilities' => 'required|string',

        'skills' => 'required|array',
        'skills.*' => 'string',

        'benefits' => 'required|array',
        'benefits.*' => 'string',

        'dead_line' => 'required|date|after_or_equal:today',

    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    // 🔥 Update manually (safe)
    $job->update([
        'title'            => $request->title,
        'location'         => $request->location,
        'job_type'         => $request->job_type,
        'category_id'      => $request->category_id,
        'experience_level' => $request->experience_level,

        'salary_min'       => $request->salary_min,
        'salary_max'       => $request->salary_max,

        'description'      => $request->description,
        'requirements'     => $request->requirements,
        'responsibilities' => $request->responsibilities,

        'skills'           => $request->skills,
        'benefits'         => $request->benefits,
        'dead_line'        => $request->dead_line,

        'status'           => $request->status ?? $job->status,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Job updated successfully',
        'data' => $job->load(['company', 'category'])
    ]);
}

    /**
     * ✅ Delete Job
     */
    public function destroy($id)
    {
        $job = rigjob::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found'
            ], 404);
        }

        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully'
        ]);
    }


    public function jobDashboard(Request $request)
{

    $user = auth()->user();


    if (!$user || !$user->company_id) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 403);
    }

    $companyId = $user->company_id;

    $perPage = $request->get('per_page', 10);
    $search  = $request->get('search');
    $status  = $request->get('status'); // active, paused, closed

    $query = rigjob::with(['category'])
        ->where('company_id', $companyId);

    // 🔍 Search
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%$search%")
              ->orWhere('location', 'like', "%$search%");
        });
    }

    // 🎯 Status filter
    if (!empty($status) && $status !== 'all') {
        $query->where('status', $status);
    }

    $jobs = $query->latest()->paginate($perPage);

    // 📊 Counts
    $counts = [
        'total_jobs' => rigjob::where('company_id', $companyId)->count(),
        'active'     => rigjob::where('company_id', $companyId)->where('status', 'active')->count(),
        'paused'     => rigjob::where('company_id', $companyId)->where('status', 'paused')->count(),
        'closed'     => rigjob::where('company_id', $companyId)->where('status', 'closed')->count(),
    ];

    return response()->json([
        'success' => true,
        'counts'  => $counts,
        'data'    => $jobs->items(),
        'pagination' => [
            'current_page' => $jobs->currentPage(),
            'last_page'    => $jobs->lastPage(),
            'per_page'     => $jobs->perPage(),
            'total'        => $jobs->total(),
        ]
    ]);
}

public function changeStatus(Request $request, $id)
{
    $job = rigjob::find($id);

    if (!$job) {
        return response()->json([
            'success' => false,
            'message' => 'Job not found'
        ], 404);
    }

    $user = auth()->user();

    // ✅ Check ownership
    if (!$user || !$user->company_id || $job->company_id != $user->company_id) {
        return response()->json([
            'success' => false,
            'message' => 'Access denied'
        ], 403);
    }

    // ✅ Validate status
    $validator = Validator::make($request->all(), [
        'status' => 'required|in:draft,published,closed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    // 🔥 Update status
    $job->status = $request->status;
    $job->save();

    return response()->json([
        'success' => true,
        'message' => 'Job status updated successfully',
        'data' => [
            'id' => $job->id,
            'status' => $job->status
        ]
    ]);
}

public function toggleFeatured($id)
{
    $job = rigjob::find($id);

    if (!$job) {
        return response()->json([
            'success' => false,
            'message' => 'Job not found'
        ], 404);
    }

    // 🔥 Toggle feature
    $job->is_featured = !$job->is_featured;
    $job->save();

    return response()->json([
        'success' => true,
        'message' => $job->is_featured
            ? 'Job marked as featured'
            : 'Job removed from featured',
        'data' => [
            'id' => $job->id,
            'is_featured' => $job->is_featured
        ]
    ]);
}

public function toggleStatus($id)
{
    $job = rigjob::find($id);

    if (!$job) return response()->json(['message' => 'Not found'], 404);

    $job->status = $job->status === 'draft' ? 'published' : 'draft';
    $job->save();

    return response()->json([
        'success' => true,
        'status' => $job->status
    ]);
}


public function featuredJobs()
{
    $jobs = rigjob::with(['company', 'category'])
        ->where('is_featured', 1)   // ✅ only featured
        ->where('status', 'published') // ✅ only active jobs
        ->latest()
        ->take(4) // ✅ limit to 4
        ->get();

    $data = $jobs->map(function ($job) {
        return [
            'id' => $job->id,
            'title' => $job->title,

            // Company
            'company_name' => $job->company->company_name ?? null,

            // Job info
            'location' => $job->location,
            'job_type' => $job->job_type,

            // Salary
            'salary' => $job->salary_min && $job->salary_max
                ? '$' . $job->salary_min . ' - $' . $job->salary_max
                : null,

            // Category
            'category' => $job->category->name ?? null,

            // Skills (tags)
            'skills' => $job->skills ?? [],

            // Time
            'posted' => $job->created_at->diffForHumans(),

            // Apply
            'apply_url' => url('/jobs/' . $job->id),
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}


public function topCompanies()
{
    $companies = Company::where('status','approved')
        ->withCount(['jobs as jobs_count' => function ($q) {
            $q->where('status', 'published'); // 👈 match your DB
        }])
        ->orderByDesc('jobs_count') // 🔥 highest jobs first
        ->take(6) // UI limit
        ->get();

    $data = $companies->map(function ($company) {
        return [
            'id' => $company->id,
            'company_name' => $company->company_name,
            'jobs_count' => $company->jobs_count ?? 0,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}

public function jobList(Request $request)
{
    $query = rigjob::with(['company', 'category'])
        ->where('status', 'published');

    // 🔍 Search (title / company)
    if ($request->search) {
        $query->where(function ($q) use ($request) {
            $q->where('title', 'like', '%' . $request->search . '%')
              ->orWhereHas('company', function ($q2) use ($request) {
                  $q2->where('company_name', 'like', '%' . $request->search . '%');
              });
        });
    }

    // 📍 Location
    if ($request->location) {
        $query->where('location', 'like', '%' . $request->location . '%');
    }

    // 🏢 Industry filter (IMPORTANT)
    if ($request->industry_id) {
        $query->whereHas('company', function ($q) use ($request) {
            $q->where('industry_id', $request->industry_id);
        });
    }

    // 🧑‍💼 Job Type
    if ($request->job_type) {
        $query->whereIn('job_type', (array) $request->job_type);
    }

    // 🎯 Experience Level
    if ($request->experience_level) {
        $query->whereIn('experience_level', (array) $request->experience_level);
    }

    // 💰 Salary Range
    if ($request->salary_min && $request->salary_max) {
        $query->whereBetween('salary_min', [$request->salary_min, $request->salary_max]);
    }

    // 📂 Category
    if ($request->category_id) {
        $query->whereIn('category_id', (array) $request->category_id);
    }

    // 🔽 Sorting
    if ($request->sort == 'oldest') {
        $query->oldest();
    } else {
        $query->latest(); // default
    }

    // 📄 Pagination
    $jobs = $query->paginate(10);

    // 🔥 Format for UI
    $data = $jobs->map(function ($job) {
        return [
            'id' => $job->id,
            'title' => $job->title,
            'company_name' => $job->company->company_name ?? '',
            'location' => $job->location,
            'job_type' => $job->job_type,
            'salary' => '$' . $job->salary_min . ' - $' . $job->salary_max,
            'experience' => $job->experience_level,
            'posted' => $job->created_at->diffForHumans(),
            'is_featured' => $job->is_featured,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data,
        'pagination' => [
            'current_page' => $jobs->currentPage(),
            'last_page' => $jobs->lastPage(),
            'total' => $jobs->total(),
        ]
    ]);
}


}
