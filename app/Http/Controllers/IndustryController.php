<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Industry;
use App\Models\rigjob;
use App\Services\WebpService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class IndustryController extends Controller
{
    /**
     * ✅ Create Industry
     */


public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255|unique:industries,name',
        'status' => 'nullable|boolean',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048' // ✅ FIXED
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    $imagePath = null;

    // ✅ FIXED: use image
    if ($request->hasFile('image')) {

        $file = $request->file('image');

        $filename = 'industry_' . time() . '_' . Str::random(8) . '.webp';

        $dest = public_path('storage/industry/' . $filename);

        WebpService::convert(
            $file->getPathname(),
            $dest,
            70,
            200,
            200
        );

        $imagePath = 'industry/' . $filename;
    }

    $industry = Industry::create([
        'name' => $request->name,
        'slug' => Str::slug($request->name),
        'status' => $request->status ?? 1,
        'image' => $imagePath // ✅ correct
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Industry created successfully',
        'data' => [
            'id' => $industry->id,
            'name' => $industry->name,
            'image' => $industry->image
                ? asset('storage/' . $industry->image)
                : null
        ]
    ]);
}

    /**
     * ✅ Get All Industries
     */
 public function index()
{
    $industries = Industry::latest()->get()->map(function ($item) {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'image' => $item->image
                ? asset('storage/' . $item->image)
                : null,
            'status' => $item->status,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $industries
    ]);
}


 public function getIndustry(Request $request)
{
    $perPage = $request->get('per_page', 5);
    $search  = $request->get('search');

    $query = Industry::query();

    // 🔍 Search
    if (!empty($search)) {
        $query->where('name', 'like', '%' . $search . '%');
    }

    $industries = $query->latest()->paginate($perPage);

    // ✅ FORMAT IMAGE URL
    $data = collect($industries->items())->map(function ($item) {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'image' => $item->image
                ? asset('storage/' . $item->image)
                : null,
            'status' => $item->status,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data,
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
        'data' => [
            'id' => $industry->id,
            'name' => $industry->name,
            'image' => $industry->image
                ? asset('storage/' . $industry->image)
                : null,
            'status' => $industry->status,
        ]
    ]);
}

    /**
     * ✅ Update Industry
     */

public function update(Request $request, $id)
{
    $industry = Industry::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255|unique:industries,name,' . $id,
        'status' => 'nullable|boolean',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048' // ✅ FIXED
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    // 🔥 IMAGE UPDATE
    if ($request->hasFile('image')) {

        // delete old image
        if ($industry->image && file_exists(public_path('storage/' . $industry->image))) {
            unlink(public_path('storage/' . $industry->image));
        }

        $file = $request->file('image');

        $filename = 'industry_' . time() . '_' . Str::random(8) . '.webp';

        $dest = public_path('storage/industry/' . $filename);

        WebpService::convert(
            $file->getPathname(),
            $dest,
            70,
            200,
            200
        );

        $industry->image = 'industry/' . $filename; // ✅ FIXED
    }

    $industry->name = $request->name;
    $industry->slug = Str::slug($request->name);
    $industry->status = $request->status ?? $industry->status;
    $industry->save();

    return response()->json([
        'success' => true,
        'message' => 'Updated successfully',
        'data' => [
            'id' => $industry->id,
            'name' => $industry->name,
            'image' => $industry->image
                ? asset('storage/' . $industry->image)
                : null
        ]
    ]);
}
    /**
     * ✅ Delete Industry
     */
public function destroy($id)
{
    $industry = Industry::findOrFail($id);

    // 🔥 delete image
    if ($industry->image && file_exists(public_path('storage/' . $industry->image))) {
        unlink(public_path('storage/' . $industry->image));
    }

    $industry->delete();

    return response()->json([
        'success' => true,
        'message' => 'Deleted successfully'
    ]);
}




public function industriesWithJobs()
{
    $industries = Industry::where('status', 1)->get();

    $data = $industries->map(function ($industry) {

        $jobsCount = rigjob::where('status', 'published')
            ->whereHas('company', function ($q) use ($industry) {
                $q->where('industry_id', $industry->id);
            })
            ->count();

        return [
            'id' => $industry->id,
            'name' => $industry->name,
            'slug' => $industry->slug,
            'jobs_count' => $jobsCount,

            // ✅ ADD THIS
            'image' => $industry->image
                ? asset('storage/' . $industry->image)
                : null,
        ];
    });

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}
}
