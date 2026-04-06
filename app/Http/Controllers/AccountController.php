<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;


class AccountController extends Controller
{
    public function getProfile()
{
    $user = auth()->user();

    return response()->json([
        'success' => true,
        'data' => [
            'name'  => $user->name,
            'email' => $user->email
        ]
    ]);
}

public function updateProfile(Request $request)
{
    $user = auth()->user();

    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    // ✅ Only update name (email is locked)
    $user->update([
        'name' => $request->name
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => [
            'name'  => $user->name,
            'email' => $user->email
        ]
    ]);
}

public function changePassword(Request $request)
{


    $validator = Validator::make($request->all(), [

        'current_password' => 'required',
        'new_password' => 'required|min:6',
        'confirm_password' => 'required|same:new_password',

    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }
 $user = auth()->user();

    // dd($user); // --- IGNORE ---


    // ✅ Check current password
    if (!Hash::check($request->current_password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Current password is incorrect'
        ], 400);
    }

    // 🔐 Update password
    $user->update([
        'password' => Hash::make($request->new_password)
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Password updated successfully'
    ]);
}
}
