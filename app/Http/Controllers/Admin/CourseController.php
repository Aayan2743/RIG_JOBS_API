<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search  = $request->get('search');
        $educationId = $request->get('education_id');

        $query = Course::with('education');

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        if ($educationId) {
            $query->where('education_id', $educationId);
        }

        $data = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }


     public function all(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search  = $request->get('search');
        $educationId = $request->get('education_id');

        $query = Course::with('education');

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        if ($educationId) {
            $query->where('education_id', $educationId);
        }

        $data = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }




    public function store(Request $request)
    {




          $validator = Validator::make($request->all(), [
            'education_id' => 'required|exists:educations,id',
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }


        $data = Course::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

public function update(Request $request, $id)
{
    $course = Course::find($id);

    if (!$course) {
        return response()->json([
            'success' => false,
            'message' => 'Course not found'
        ], 404);
    }

    // ✅ VALIDATION



      $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        'education_id' => 'required|exists:educations,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }


    // ✅ SAFE UPDATE
    $course->update([
        'name' => $request->name,
        'education_id' => $request->education_id
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Course updated successfully',
        'data' => $course
    ]);
}

    public function destroy($id)
    {
        Course::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
