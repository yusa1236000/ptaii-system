<?php
// app/Models/ItemCategory.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    use HasFactory;

    protected $table = 'ItemCategory';
    protected $primaryKey = 'category_id';
    protected $fillable = ['name', 'description', 'parent_category_id'];

    public function parentCategory()
    {
        return $this->belongsTo(ItemCategory::class, 'parent_category_id', 'category_id');
    }

    public function childCategories()
    {
        return $this->hasMany(ItemCategory::class, 'parent_category_id', 'category_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'category_id', 'category_id');
    }
}