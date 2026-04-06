<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Industry;
use App\Models\rigjob;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IndustryController extends Controller
{
    /**
     * ✅ Create Industry
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:industries,name',
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }

        $industry = Industry::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'status' => $request->status ?? 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Industry created successfully',
            'data' => $industry
        ]);
    }

    /**
     * ✅ Get All Industries
     */
    public function index()
    {
        $industries = Industry::latest()->get();

        return response()->json([
            'success' => true,
            'data' => $industries
        ]);
    }


    public function getIndustry(Request $request)
{
    $perPage = $request->get('per_page', 10); // default 10
    $search  = $request->get('search');

    $query = Industry::query();

    // 🔍 Search by name
    if (!empty($search)) {
        $query->where('name', 'like', '%' . $search . '%');
    }

    $industries = $query->latest()->paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => $industries->items(),
        'pagination' => [
            'current_page' => $industries->currentPage(),
            'last_page' => $industries->lastPage(),
            'per_page' => $industries->perPage(),
            'total' => $industries->total(),
        ]
    ]);
}

    /**
     * ✅ Get Single Industry
     */
    public function show($id)
    {
        $industry = Industry::find($id);

        if (!$industry) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $industry
        ]);
    }

    /**
     * ✅ Update Industry
     */
    public function update(Request $request, $id)
    {
        $industry = Industry::find($id);

        if (!$industry) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:industries,name,' . $id,
            'status' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }

        $industry->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'status' => $request->status ?? $industry->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Industry updated successfully',
            'data' => $industry
        ]);
    }

    /**
     * ✅ Delete Industry
     */
    public function destroy($id)
    {
        $industry = Industry::find($id);

        if (!$industry) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found'
            ], 404);
        }

        $industry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Industry deleted successfully'
        ]);
    }





public function industriesWithJobs()
{
    $industries = Industry::where('status', 1)->get();

    $data = $industries->map(function ($industry) {

        $jobsCount = rigjob::where('status', 'published') // 👈 IMPORTANT (your DB shows published)
            ->whereHas('company', function ($q) use ($industry) {
                $q->where('industry_id', $industry->id);
            })
            ->count();

        return [
            'id' => $industry->id,
            'name' => $industry->name,
            'jobs_count' => $jobsCount,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}
}
