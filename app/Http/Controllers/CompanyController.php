<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
     public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name'   => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'password'       => 'required|min:6',
            'phone'          => 'required|digits:10|max:20|unique:companies,phone',
            'website'        => 'required|url|max:255',
            'industry_id' => 'required|exists:industries,id',
            'message'        => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();

        try {

            // 🏢 Create Company
            $company = Company::create([
                'company_name'   => $request->company_name,
                'contact_person' => $request->contact_person,
                'phone'          => $request->phone,
                'website'        => $request->website,
                'industry_id'    => $request->industry_id,
                'message'        => $request->message,
            ]);

            // 👤 Create User
            $user = User::create([
                'name'       => $request->contact_person,
                'email'      => $request->email,

                'password'   => Hash::make($request->password),
                'company_id' => $company->id,
                'role'       => 'employeer',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company registered successfully',
                'data' => [
                    'company' => $company,
                    'user' => $user
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Get All Companies
     */
    public function index()
    {
        $companies = Company::with('users')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $companies
        ]);
    }


    public function companyRequests(Request $request)
{
    $query = Company::with(['industry', 'users']);

    // ✅ ONLY APPROVED
    $query->where('status', 'approved');

    // ✅ SEARCH
    if ($request->search) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('company_name', 'like', "%$search%")
              ->orWhere('contact_person', 'like', "%$search%")
              ->orWhereHas('users', function ($uq) use ($search) {
                  $uq->where('email', 'like', "%$search%");
              });
        });
    }

    // ✅ PAGINATION
    $companies = $query->latest()->paginate(10);

    return response()->json([
        'success' => true,
        'data' => $companies->items(),
        'pagination' => [
            'current_page' => $companies->currentPage(),
            'last_page' => $companies->lastPage(),
            'per_page' => $companies->perPage(),
            'total' => $companies->total(),
        ]
    ]);
}

    public function requests(Request $request)
{
    $status  = $request->get('status'); // pending / approved / rejected
    $search  = $request->get('search');
    $perPage = $request->get('per_page', 10);

    $query = Company::with(['industry', 'users']);

    // 🔍 Filter by status
    if (!empty($status) && $status !== 'all') {
        $query->where('status', $status);
    }

    // 🔍 Search (company or email)
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('company_name', 'like', "%$search%")
              ->orWhereHas('users', function ($q2) use ($search) {
                  $q2->where('email', 'like', "%$search%");
              });
        });
    }

    $companies = $query->latest()->paginate($perPage);

    // 📊 Counts
    $counts = [
        'total'    => Company::count(),
        'pending'  => Company::where('status', 'pending')->count(),
        'approved' => Company::where('status', 'approved')->count(),
        'rejected' => Company::where('status', 'rejected')->count(),
    ];

    return response()->json([
        'success' => true,
        'counts'  => $counts,
        'data'    => $companies->items(),
        'pagination' => [
            'current_page' => $companies->currentPage(),
            'last_page'    => $companies->lastPage(),
            'per_page'     => $companies->perPage(),
            'total'        => $companies->total(),
        ]
    ]);
}


public function approve($id)
{
    $company = Company::find($id);

    if (!$company) {
        return response()->json([
            'success' => false,
            'message' => 'Company not found'
        ], 404);
    }

    $company->update(['status' => 'approved']);

    return response()->json([
        'success' => true,
        'message' => 'Company approved successfully'
    ]);
}



public function reject(Request $request, $id)
{


     $validator = Validator::make($request->all(), [
            'remark' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }

    $company = Company::find($id);

    if (!$company) {
        return response()->json([
            'success' => false,
            'message' => 'Company not found'
        ], 404);
    }

    $company->update([
        'status' => 'rejected',
        'remark' => $request->remark, // ✅ SAVE REMARK
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Company rejected successfully',
        'data' => $company
    ]);
}



public function pending($id)
{
    $company = Company::find($id);

    if (!$company) {
        return response()->json([
            'success' => false,
            'message' => 'Company not found'
        ], 404);
    }

    $company->update(['status' => 'pending']);

    return response()->json([
        'success' => true,
        'message' => 'Company set to pending successfully'
    ]);
}



    /**
     * ✅ Get Single Company
     */
    public function show($id)
    {
        $company = Company::with('users')->find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $company
        ]);
    }



    // mycompany

     public function mycompany()
    {
        $company = Company::with('users')->find(auth()->user()->company_id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $company
        ]);
    }

    /**
     * ✅ Update Company + User
     */
public function update(Request $request, $id)
{
    $company = Company::with('user')->find($id);

    if (!$company) {
        return response()->json([
            'success' => false,
            'message' => 'Company not found'
        ], 404);
    }

    $validator = Validator::make($request->all(), [

        'company_name'   => 'required|string|max:255',
        'contact_person' => 'required|string|max:255',

        'email' => 'required|email|unique:users,email,' . optional($company->user)->id,

        'password' => 'nullable|min:6',
        'founded' => 'nullable|string|max:255',

        'phone' => 'nullable|digits:10|unique:companies,phone,' . $id,

        'website' => 'nullable|url|max:255',

        'industry_id' => 'nullable|exists:industries,id',

        'message' => 'nullable|string',

        // ✅ New Fields
        'tagline' => 'nullable|string|max:255',
        'company_size' => 'nullable|string|max:100',
        'headquarters' => 'nullable|string|max:255',
        'company_email' => 'nullable|email|max:255',

        // ✅ JSON Fields
        'culture_values' => 'nullable|array',
        'culture_values.*' => 'string|max:255',

        'benefits_perks' => 'nullable|array',
        'benefits_perks.*' => 'string|max:255',

        'social_links' => 'nullable|array',
        'social_links.*' => 'nullable|url',

    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    DB::beginTransaction();

    try {

        // 🏢 Update Company
        $company->update([
            'company_name'   => $request->company_name,
            'contact_person' => $request->contact_person,
            'phone'          => $request->phone,
            'website'        => $request->website,
            'industry_id'    => $request->industry_id,
            'message'        => $request->message,
            'founded'        => $request->founded,

            'tagline'        => $request->tagline,
            'company_size'   => $request->company_size,
            'headquarters'   => $request->headquarters,
            'company_email'  => $request->company_email,

            'culture_values' => $request->culture_values,
            'benefits_perks' => $request->benefits_perks,
            'social_links'   => $request->social_links,
        ]);

        // 👤 Update User (company admin)
        if ($company->user) {

            $updateUserData = [
                'name'  => $request->contact_person,
                'email' => $request->email,
            ];

            if ($request->password) {
                $updateUserData['password'] = Hash::make($request->password);
            }

            $company->user->update($updateUserData);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully',
            'data' => $company->load(['industry', 'user'])
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}



public function update_company(Request $request)
{
    $company = Company::with('user')->find(auth()->user()->company_id);

    if (!$company) {
        return response()->json([
            'success' => false,
            'message' => 'Company not found'
        ], 404);
    }

    // ✅ VALIDATION
    $validator = Validator::make($request->all(), [

        'company_name'   => 'required|string|max:255',
        'contact_person' => 'required|string|max:255',

        'email' => 'required|email|unique:users,email,' . optional($company->user)->id,

        'password' => 'nullable|min:6',
        'founded'  => 'required|string|max:255',

        // ✅ FIXED (use company id)
        'phone' => 'required|digits:10|unique:companies,phone,' . $company->id,

        'website'     => 'required|url|max:255',
        'industry_id' => 'required|exists:industries,id',
        'message'     => 'required|string',

        'tagline'       => 'required|string|max:255',
        'company_size'  => 'required|string|max:100',
        'headquarters'  => 'required|string|max:255',
        'company_email' => 'required|email|max:255',

        // ✅ ARRAY FIELDS
        'culture_values'   => 'required|array',
        'culture_values.*' => 'string|max:255',

        'benefits_perks'   => 'required|array',
        'benefits_perks.*' => 'string|max:255',

        'compliance_certifications' => 'required|string|max:255',

        'social_links'   => 'required|array',
        'social_links.*' => 'required|url',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()->first() // ✅ full errors
        ], 422);
    }

    // ✅ PREVENT NULL ARRAYS
    $request->merge([
        'culture_values' => $request->culture_values ?? [],
        'benefits_perks' => $request->benefits_perks ?? [],
        'social_links'   => $request->social_links ?? [],
    ]);

    DB::beginTransaction();

    try {

        // 🏢 UPDATE COMPANY
        $company->update([
            'company_name'   => $request->company_name,
            'contact_person' => $request->contact_person,
            'phone'          => $request->phone,
            'website'        => $request->website,
            'industry_id'    => $request->industry_id,
            'message'        => $request->message,
            'founded'        => $request->founded,

            'tagline'        => $request->tagline,
            'company_size'   => $request->company_size,
            'headquarters'   => $request->headquarters,
            'company_email'  => $request->company_email,

            'culture_values' => $request->culture_values,
            'benefits_perks' => $request->benefits_perks,
            'social_links'   => $request->social_links,
            'compliance_certifications'   => $request->compliance_certifications,
        ]);

        // 👤 UPDATE USER
        if ($company->user) {

            $userData = [
                'name'  => $request->contact_person,
                'email' => $request->email,
            ];

            if ($request->password) {
                $userData['password'] = Hash::make($request->password);
            }

            $company->user->update($userData);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Company updated successfully',
            'data'    => $company->load(['industry', 'user'])
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * ✅ Delete Company + User
     */
    public function destroy($id)
    {
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found'
            ], 404);
        }

        DB::beginTransaction();

        try {

            // Delete users first
            User::where('company_id', $company->id)->delete();

            // Delete company
            $company->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
