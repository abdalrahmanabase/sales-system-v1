<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'full_path',
    ];

    // Relationships
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

    // Scopes
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeHasChildren(Builder $query): Builder
    {
        return $query->whereHas('children');
    }

    public function scopeHasProducts(Builder $query): Builder
    {
        return $query->whereHas('products');
    }

    public function scopeNoProducts(Builder $query): Builder
    {
        return $query->whereDoesntHave('products');
    }

    // Helper Methods
    public function isParent(): bool
    {
        return is_null($this->parent_id);
    }

    public function isSubcategory(): bool
    {
        return !is_null($this->parent_id);
    }

    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    public function hasProducts(): bool
    {
        return $this->products()->count() > 0;
    }

    public function getTotalProductsCountAttribute(): int
    {
        return $this->products()->count() + $this->subcategoryProducts()->count();
    }

    // Get the full category path (parent > child)
    public function getFullPathAttribute(): string
    {
        if ($this->isSubcategory()) {
            return $this->parent->name . ' > ' . $this->name;
        }
        return $this->name;
    }

    // Get all descendant categories (recursive)
    public function getAllDescendants(): \Illuminate\Support\Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }
        
        return $descendants;
    }

    // Get all ancestor categories (recursive)
    public function getAllAncestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        
        if ($this->parent) {
            $ancestors->push($this->parent);
            $ancestors = $ancestors->merge($this->parent->getAllAncestors());
        }
        
        return $ancestors;
    }

    // Check if category can be deleted
    public function canBeDeleted(): bool
    {
        return $this->products()->count() === 0 && $this->children()->count() === 0;
    }

    // Get deletion error message if any
    public function getDeletionErrorMessage(): ?string
    {
        if ($this->products()->count() > 0) {
            return "Cannot delete category '{$this->name}' because it has products. Please remove or reassign the products first.";
        }
        
        if ($this->children()->count() > 0) {
            return "Cannot delete category '{$this->name}' because it has subcategories. Please remove or reassign the subcategories first.";
        }
        
        return null;
    }
}
