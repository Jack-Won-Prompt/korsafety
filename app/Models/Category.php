<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['parent_id', 'slug', 'name', 'sort', 'is_active'];
    public $timestamps = true;

    protected $casts = ['is_active' => 'boolean', 'sort' => 'integer'];

    /** 계층 최대 깊이 — 대(0) · 중(1) · 소(2) */
    public const MAX_DEPTH = 3;

    public const DEPTH_LABELS = ['대분류', '중분류', '소분류'];

    /** 자기 깊이 (0=대, 1=중, 2=소) */
    public function getDepthAttribute(): int
    {
        $depth = 0;
        $node = $this;
        while ($node->parent_id && $depth < self::MAX_DEPTH) {
            $node = $node->parent()->first();
            if (! $node) break;
            $depth++;
        }

        return $depth;
    }

    public function getDepthLabelAttribute(): string
    {
        return self::DEPTH_LABELS[$this->depth] ?? '분류';
    }

    /**
     * 전체 트리를 화면 표시 순서대로 편 목록.
     * 각 항목에 depth(0~2)와 path('대 > 중 > 소')를 붙여 셀렉트·표에 그대로 쓴다.
     */
    public static function flatTree(bool $onlyActive = false): \Illuminate\Support\Collection
    {
        $all = static::query()
            ->when($onlyActive, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort')->orderBy('name')->get();

        $byParent = $all->groupBy(fn ($c) => $c->parent_id ?: 0);

        $out = collect();
        $walk = function ($parentId, $depth, $prefix) use (&$walk, $byParent, $out) {
            foreach ($byParent->get($parentId ?: 0, collect()) as $node) {
                $node->setAttribute('tree_depth', $depth);
                $node->setAttribute('tree_path', $prefix === '' ? $node->name : $prefix.' > '.$node->name);
                $out->push($node);
                if ($depth + 1 < self::MAX_DEPTH) {
                    $walk($node->id, $depth + 1, $node->tree_path);
                }
            }
        };
        $walk(0, 0, '');

        return $out;
    }

    /** 자기 자신과 모든 하위 카테고리 id (쇼핑몰에서 상위 분류를 볼 때 사용) */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $level = [$this->id];
        for ($i = 1; $i < self::MAX_DEPTH; $i++) {
            $level = static::whereIn('parent_id', $level)->pluck('id')->all();
            if (! $level) break;
            $ids = array_merge($ids, $level);
        }

        return $ids;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category');
    }

    /** 카테고리를 대표 분류로 지정한 상품 (products.category_id) */
    public function primaryProducts(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }

    public function scopeActive($query)
    {
        return $query->where('categories.is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
