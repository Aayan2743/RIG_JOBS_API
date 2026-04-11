<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Services\WebpService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RigJobCategoryController extends Controller
{
/**
     * ✅ Create Category
     */
 public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255|unique:categories,name',
        'status' => 'nullable|boolean',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    $logoPath = null;

    if ($request->hasFile('logo')) {

        $file = $request->file('logo');

        $filename = 'category_' . time() . '_' . Str::random(8) . '.webp';

        $dest = public_path('storage/category/' . $filename);

        WebpService::convert(
            $file->getPathname(),
            $dest,
            70,
            200,
            200
        );

        $logoPath = 'category/' . $filename;
    }

    $category = Category::create([
        'name' => $request->name,
        'slug' => Str::slug($request->name),
        'status' => $request->status ?? 1,
        'image' => $logoPath
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Category created successfully',
        'data' => [
            'id' => $category->id,
            'name' => $category->name,
            'logo' => $category->logo
                ? asset('storage/' . $category->logo)
                : null
        ]
    ]);
}

    /**
     * ✅ Get All Categories (with pagination + search)
     */
    public function index(Request $request)
    {

        $perPage = $request->get('per_page', 10);
        $search  = $request->get('search');

        $query = Category::query();

        if (!empty($search)) {
            $query->where('name', 'like', "%$search%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $categories = $query->latest()->paginate($perPage);



        return response()->json([
            'success' => true,


             'data' => collect($categories->items())->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'logo' => $item->image
                        ? asset('storage/' . $item->image)
                        : null,
                    'status' => $item->status
                ];
            }),

            'pagination' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'total' => $categories->total(),
            ]
        ]);
    }


   public function all(Request $request)
{
    $search = $request->get('search');

    $query = Category::query();

    if (!empty($search)) {
        $query->where('name', 'like', "%$search%");
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // ✅ GET ALL DATA (NO PAGINATION)
    $categories = $query->latest()->get();

    return response()->json([
        'success' => true,

        'data' => $categories->map(function ($item) {
    return [
        'id' => $item->id,
        'name' => $item->name,
        'logo' => $item->image
            ? asset('storage/' . $item->image)
            : null,
    ];
})
    ]);
}

    /**
     * ✅ Get Single Category
     */
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * ✅ Update Category
     */
   public function update(Request $request, $id)
{
    $category = Category::find($id);

    if (!$category) {
        return response()->json([
            'success' => false,
            'message' => 'Category not found'
        ], 404);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255|unique:categories,name,' . $id,
        'status' => 'nullable|boolean',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()->first()
        ], 422);
    }

    // 🔥 IMAGE UPDATE
    if ($request->hasFile('logo')) {

        // delete old
        if ($category->image && file_exists(public_path('storage/' . $category->image))) {
            unlink(public_path('storage/' . $category->image));
        }

        $file = $request->file('logo');

        $filename = 'category_' . time() . '_' . Str::random(8) . '.webp';

        $dest = public_path('storage/category/' . $filename);

        WebpService::convert(
            $file->getPathname(),
            $dest,
            70,
            200,
            200
        );

        $category->image = 'category/' . $filename;
    }

    $category->name = $request->name;
    $category->slug = Str::slug($request->name);
    $category->status = $request->status ?? $category->status;
    $category->save();

    return response()->json([
        'success' => true,
        'message' => 'Category updated successfully',
        'data' => [
            'id' => $category->id,
            'name' => $category->name,
            'logo' => $category->image
                ? asset('storage/' . $category->image)
                : null
        ]
    ]);
}

    /**
     * ✅ Delete Category
     */

    public function destroy($id)
{
    $category = Category::find($id);

    if (!$category) {
        return response()->json([
            'success' => false,
            'message' => 'Category not found'
        ], 404);
    }

    // 🔥 delete image
    if ($category->image && file_exists(public_path('storage/' . $category->image))) {
        unlink(public_path('storage/' . $category->image));
    }

    $category->delete();

    return response()->json([
        'success' => true,
        'message' => 'Category deleted successfully'
    ]);
}
}
