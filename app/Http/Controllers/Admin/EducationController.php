<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Education;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EducationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $search  = $request->get('search');

        $query = Education::query();

        if ($search) {
            $query->where('name', 'like', "%$search%");
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

        $query = Education::query();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        $data = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // STORE
    public function store(Request $request)
    {



          $validator = Validator::make($request->all(), [
            'name' => 'required|unique:educations,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $data = Education::create([
            'name' => $request->name
        ]);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $edu = Education::findOrFail($id);



          $validator = Validator::make($request->all(), [
           'name' => 'required|unique:educations,name,' . $id
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()->first(),
            ], 422);
        }

        $edu->update([
            'name' => $request->name
        ]);

        return response()->json(['success' => true]);
    }

    // DELETE
    public function destroy($id)
    {
        Education::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
