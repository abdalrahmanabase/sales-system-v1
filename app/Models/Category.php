<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Products that have this category as their main category
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    // Products that have this category as their subcategory
    public function subcategoryProducts()
    {
        return $this->hasMany(Product::class, 'subcategory_id');
    }

    // All products (both main category and subcategory)
    public function allProducts()
    {
        return Product::where('category_id', $this->id)
            ->orWhere('subcategory_id', $this->id);
    }

    // Check if this is a parent category
    public function isParent()
    {
        return is_null($this->parent_id);
    }

    // Check if this is a subcategory
    public function isSubcategory()
    {
        return !is_null($this->parent_id);
    }

    // Get the full category path (parent > child)
    public function getFullPathAttribute()
    {
        if ($this->isSubcategory()) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }
}
