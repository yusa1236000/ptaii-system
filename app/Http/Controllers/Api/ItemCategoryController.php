<?php
// app/Http/Controllers/Api/ItemCategoryController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemCategoryController extends Controller
{
    public function index()
    {
        $categories = ItemCategory::with('parentCategory')->get();
        return response()->json(['data' => $categories], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_category_id' => 'nullable|exists:item_categories,category_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $category = ItemCategory::create($request->all());
        return response()->json(['data' => $category, 'message' => 'Category created successfully'], 201);
    }

    public function show($id)
    {
        $category = ItemCategory::with(['parentCategory', 'childCategories'])->find($id);
        
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        
        return response()->json(['data' => $category], 200);
    }

    public function update(Request $request, $id)
    {
        $category = ItemCategory::find($id);
        
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_category_id' => 'nullable|exists:item_categories,category_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $category->update($request->all());
        return response()->json(['data' => $category, 'message' => 'Category updated successfully'], 200);
    }

    public function destroy($id)
    {
        $category = ItemCategory::find($id);
        
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }
        
        // Check if category has child categories or items
        if ($category->childCategories()->count() > 0 || $category->items()->count() > 0) {
            return response()->json(['message' => 'Cannot delete category with child categories or items'], 422);
        }
        
        $category->delete();
        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}