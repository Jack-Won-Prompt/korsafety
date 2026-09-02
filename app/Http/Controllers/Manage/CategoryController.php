<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** 상품 카테고리 관리 — 대 > 중 > 소 3단계, 노출 토글, 진열 순서 (본사 전용) */
class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // 대 > 중 > 소 순서로 편 트리 (각 항목에 tree_depth·tree_path 포함)
        $tree = Category::flatTree();

        // 카테고리별 상품 수 (다대다 pivot 기준)
        $counts = Category::withCount('products')->pluck('products_count', 'id');

        $editing = null;
        if ($id = $request->query('edit')) {
            $editing = Category::find($id);
        }

        // 상위로 지정할 수 있는 후보 — 소분류(깊이 2)는 더 아래를 둘 수 없어 제외,
        // 수정 중이라면 자기 자신과 그 하위도 제외(순환 방지)
        $exclude = $editing ? $editing->descendantIds() : [];
        $parents = $tree->filter(fn ($c) => $c->tree_depth < Category::MAX_DEPTH - 1 && ! in_array($c->id, $exclude, true));

        return view('manage.categories.index', [
            'tree' => $tree,
            'counts' => $counts,
            'editing' => $editing,
            'parents' => $parents,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        if (($data['parent_id'] ?? null) && Category::find($data['parent_id'])->depth + 1 > Category::MAX_DEPTH - 1) {
            return back()->withErrors(['parent_id' => '대·중·소 3단계까지만 만들 수 있습니다.'])->withInput();
        }
        Category::create($data);

        return redirect()->route('manage.categories.index')->with('status', '카테고리가 추가되었습니다.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category);
        $parentId = $data['parent_id'] ?? null;

        // 자기 자신이나 자기 하위를 상위로 지정하면 트리가 끊어진다
        if ($parentId && in_array((int) $parentId, $category->descendantIds(), true)) {
            return back()->withErrors(['parent_id' => '자기 자신이나 하위 카테고리를 상위로 지정할 수 없습니다.'])->withInput();
        }

        // 옮긴 뒤 깊이 + 하위 높이가 대·중·소 3단계를 넘지 않아야 한다
        $newDepth = $parentId ? (Category::find($parentId)->depth + 1) : 0;
        if ($newDepth + $this->subtreeHeight($category) > Category::MAX_DEPTH - 1) {
            return back()->withErrors(['parent_id' => '대·중·소 3단계까지만 만들 수 있습니다. 하위 분류가 있는 항목은 더 아래로 옮길 수 없습니다.'])->withInput();
        }

        $category->update($data);

        return redirect()->route('manage.categories.index')->with('status', '카테고리가 수정되었습니다.');
    }

    /** 이 분류 아래로 몇 단계가 더 있는지 (0=하위 없음) */
    private function subtreeHeight(Category $category): int
    {
        $height = 0;
        $level = [$category->id];
        for ($i = 1; $i < Category::MAX_DEPTH; $i++) {
            $level = Category::whereIn('parent_id', $level)->pluck('id')->all();
            if (! $level) break;
            $height++;
        }

        return $height;
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return back()->with('error', '하위 카테고리를 먼저 삭제하거나 옮겨 주세요.');
        }
        $used = $category->products()->count() + $category->primaryProducts()->count();
        if ($used > 0) {
            return back()->with('error', '연결된 상품이 '.$used.'개 있어 삭제할 수 없습니다. 상품을 다른 카테고리로 옮긴 뒤 삭제하세요.');
        }

        $category->delete();

        return back()->with('status', '카테고리가 삭제되었습니다.');
    }

    /** 노출/숨김 토글 */
    public function toggle(Category $category)
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('status', $category->name.' 카테고리를 '.($category->is_active ? '노출' : '숨김').' 처리했습니다.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash',
                Rule::unique('categories', 'slug')->ignore($category?->id)],
            'parent_id' => 'nullable|exists:categories,id',
            'sort' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ], [], ['name' => '카테고리명', 'slug' => 'URL 주소', 'parent_id' => '상위 카테고리', 'sort' => '정렬 순서']);

        $data['slug'] = ($data['slug'] ?? null) ?: $this->uniqueSlug($data['name'], $category?->id);
        $data['sort'] = $data['sort'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');
        $data['parent_id'] = ($data['parent_id'] ?? null) ?: null;

        return $data;
    }

    /** 한글 이름이면 slug()가 빈 문자열이 되므로 대체 키를 만든다 */
    private function uniqueSlug(string $name, ?int $ignoreId): string
    {
        $base = Str::slug($name) ?: 'cat-'.Str::lower(Str::random(5));
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
