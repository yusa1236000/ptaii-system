<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    use HasFactory;

    protected $table = 'item_categories';
    protected $primaryKey = 'category_id';
    protected $fillable = ['name', 'description', 'parent_category_id'];

    /**
     * Get the parent category
     */
    public function parent()
    {
        return $this->belongsTo(ItemCategory::class, 'parent_category_id', 'category_id');
    }

    /**
     * Get the child categories
     */
    public function children()
    {
        return $this->hasMany(ItemCategory::class, 'parent_category_id', 'category_id');
    }

    /**
     * Get the items in this category
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'category_id', 'category_id');
    }
}