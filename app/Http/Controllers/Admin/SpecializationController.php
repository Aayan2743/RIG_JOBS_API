<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Specialization;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class SpecializationController extends Controller
{
     public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search  = $request->get('search');
        $courseId = $request->get('course_id');

        $query = Specialization::with('course');

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        if ($courseId) {
            $query->where('course_id', $courseId);
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
        $courseId = $request->get('course_id');

        $query = Specialization::with('course');

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        if ($courseId) {
            $query->where('course_id', $courseId);
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
          'course_id' => 'required|exists:courses,id',
            'name' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }


        $data = Specialization::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // public function update(Request $request, $id)
    // {
    //     $spec = Specialization::findOrFail($id);

    //     $spec->update($request->all());

    //     return response()->json(['success' => true]);
    // }

    public function update(Request $request, $id)
{
    // ✅ CHECK RECORD
    $spec = Specialization::find($id);

    if (!$spec) {
        return response()->json([
            'success' => false,
            'message' => 'Specialization not found'
        ], 404);
    }

    // ✅ VALIDATION
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'course_id' => 'required|exists:courses,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    // ✅ UPDATE
    $spec->update([
        'name' => $request->name,
        'course_id' => $request->course_id
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Specialization updated successfully',
        'data' => $spec
    ]);
}

    public function destroy($id)
    {
        Specialization::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
