<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CandidateProfileController extends Controller
{
     public function index(Request $request)
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
            'company' => 'required',
            'title' => 'required'
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

        $exp = $candidate->experiences()->create($request->all());

        return response()->json([
            'success' => true,
            'data' => $exp
        ]);
    }

    // ✏️ UPDATE EXPERIENCE
    public function updateExperience(Request $request, $id)
    {
        $candidate = Candidate::where('user_id', auth()->id())->firstOrFail();

        $exp = $candidate->experiences()->findOrFail($id);

        $exp->update($request->all());

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
