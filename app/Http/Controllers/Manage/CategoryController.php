<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** 상품 카테고리 관리 — 대분류 > 소분류 2단계, 노출 토글, 진열 순서 (본사 전용) */
class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $roots = Category::roots()->with('children')->orderBy('sort')->orderBy('name')->get();

        // 카테고리별 상품 수 (다대다 pivot 기준) — 트리 표시에 함께 씀
        $counts = Category::withCount('products')->pluck('products_count', 'id');

        $editing = null;
        if ($id = $request->query('edit')) {
            $editing = Category::find($id);
        }

        return view('manage.categories.index', [
            'roots' => $roots,
            'counts' => $counts,
            'editing' => $editing,
            'parents' => Category::roots()->orderBy('sort')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Category::create($data);

        return redirect()->route('manage.categories.index')->with('status', '카테고리가 추가되었습니다.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category);

        // 자기 자신 / 자기 하위를 부모로 지정하는 순환 방지
        if (($data['parent_id'] ?? null) == $category->id) {
            return back()->withErrors(['parent_id' => '자기 자신을 상위 카테고리로 지정할 수 없습니다.'])->withInput();
        }
        if (($data['parent_id'] ?? null) && $category->children()->exists()) {
            return back()->withErrors(['parent_id' => '하위 카테고리가 있는 분류는 다른 분류의 하위로 옮길 수 없습니다.'])->withInput();
        }

        $category->update($data);

        return redirect()->route('manage.categories.index')->with('status', '카테고리가 수정되었습니다.');
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
