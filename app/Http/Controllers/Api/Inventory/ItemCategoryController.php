<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all item categories with their parent category
        $categories = ItemCategory::with('parent')->get();
        
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_category_id' => 'nullable|exists:item_categories,category_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $category = ItemCategory::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $category = ItemCategory::with(['parent', 'children', 'items'])->find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Item category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $category = ItemCategory::find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Item category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'parent_category_id' => 'nullable|exists:item_categories,category_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for circular reference in parent-child relationship
        if ($request->parent_category_id == $id) {
            return response()->json([
                'success' => false,
                'message' => 'A category cannot be its own parent'
            ], 422);
        }

        $category->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Item category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = ItemCategory::find($id);
        
        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Item category not found'
            ], 404);
        }

        // Check if the category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with child categories'
            ], 422);
        }

        // Check if the category has items
        if ($category->items()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with associated items'
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item category deleted successfully'
        ]);
    }
    
    /**
     * Get a tree structure of categories.
     *
     * @return \Illuminate\Http\Response
     */
    public function tree()
    {
        // Get all root categories (those without a parent)
        $rootCategories = ItemCategory::whereNull('parent_category_id')
            ->with('children')
            ->get();
        
        // Build the tree recursively
        $buildTree = function ($categories) use (&$buildTree) {
            return $categories->map(function ($category) use (&$buildTree) {
                $node = [
                    'id' => $category->category_id,
                    'name' => $category->name,
                    'description' => $category->description
                ];
                
                if ($category->children->count() > 0) {
                    $node['children'] = $buildTree($category->children);
                }
                
                return $node;
            });
        };
        
        $tree = $buildTree($rootCategories);
        
        return response()->json([
            'success' => true,
            'data' => $tree
        ]);
    }
}