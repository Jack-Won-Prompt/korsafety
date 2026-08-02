@extends('manage.layout')
@section('title', '상품 카테고리')
@section('page', '상품 카테고리')
@section('crumb', '쇼핑몰 분류 · 노출 · 진열 순서 관리')
@section('actions')
    <a href="{{ route('manage.products.index') }}" class="btn btn-sm">상품 관리 →</a>
@endsection

@section('content')
@php
    $isEdit = (bool) $editing;
    $val = fn($field, $default = null) => old($field, $editing->{$field} ?? $default);
@endphp
<div class="grid-2">
    <div>
        <div class="panel">
            <div class="panel-h">
                <div><h2>카테고리 목록</h2><div class="sub">대분류 아래 소분류를 둘 수 있습니다 (2단계)</div></div>
            </div>
            <table class="table">
                <thead><tr>
                    <th>카테고리</th>
                    <th style="width:150px">URL 주소</th>
                    <th style="width:70px">상품</th>
                    <th style="width:60px">순서</th>
                    <th style="width:70px">노출</th>
                    <th style="width:150px">관리</th>
                </tr></thead>
                <tbody>
                @forelse($roots as $root)
                    @php $rows = collect([[$root, 0]])->concat($root->children->map(fn($c) => [$c, 1])); @endphp
                    @foreach($rows as [$c, $depth])
                        <tr>
                            <td>
                                <span class="t-name" style="padding-left:{{ $depth * 18 }}px">{{ $depth ? '└ ' : '' }}{{ $c->name }}</span>
                            </td>
                            <td class="t-sub">{{ $c->slug }}</td>
                            <td class="t-sub">{{ number_format($counts[$c->id] ?? 0) }}개</td>
                            <td class="t-sub">{{ $c->sort }}</td>
                            <td>
                                @if($c->is_active)<span class="badge ok">노출</span>@else<span class="badge off">숨김</span>@endif
                            </td>
                            <td>
                                <div style="display:flex;gap:6px">
                                    <a href="{{ route('manage.categories.index', ['edit' => $c->id]) }}" class="btn btn-sm">수정</a>
                                    <form action="{{ route('manage.categories.toggle', $c) }}" method="post">@csrf
                                        <button class="btn btn-sm">{{ $c->is_active ? '숨김' : '노출' }}</button>
                                    </form>
                                    <form action="{{ route('manage.categories.destroy', $c) }}" method="post"
                                          onsubmit="return confirm('{{ $c->name }} 카테고리를 삭제할까요?')">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger">삭제</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="6" class="empty">등록된 카테고리가 없습니다. 오른쪽에서 첫 카테고리를 추가하세요.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="panel-h">
                <div><h2>{{ $isEdit ? '카테고리 수정' : '카테고리 추가' }}</h2>
                    <div class="sub">{{ $isEdit ? $editing->name : '쇼핑몰 상단·상품 분류에 사용됩니다' }}</div></div>
                @if($isEdit)<a href="{{ route('manage.categories.index') }}" class="btn btn-sm">새로 추가</a>@endif
            </div>
            <div class="panel-b">
                <form action="{{ $isEdit ? route('manage.categories.update', $editing) : route('manage.categories.store') }}" method="post">
                    @csrf
                    @if($isEdit) @method('PUT') @endif

                    <div class="form-row">
                        <label>카테고리명 <span class="req">*</span></label>
                        <input class="input" name="name" value="{{ $val('name') }}" placeholder="예) 안전화">
                        @error('name')<div class="err-msg">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-row">
                        <label>상위 카테고리</label>
                        <select class="select" name="parent_id">
                            <option value="">— 대분류로 등록 —</option>
                            @foreach($parents as $p)
                                @continue($isEdit && $p->id === $editing->id)
                                <option value="{{ $p->id }}" @selected($val('parent_id') == $p->id)>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        @error('parent_id')<div class="err-msg">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-2">
                        <div class="form-row">
                            <label>URL 주소 (slug)</label>
                            <input class="input" name="slug" value="{{ $val('slug') }}" placeholder="safety-shoes">
                            <div class="hint">비워 두면 자동 생성됩니다.</div>
                            @error('slug')<div class="err-msg">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-row">
                            <label>정렬 순서</label>
                            <input class="input" type="number" name="sort" value="{{ $val('sort', 0) }}" min="0">
                            <div class="hint">작을수록 앞에 표시됩니다.</div>
                        </div>
                    </div>
                    <div class="form-row" style="display:flex;align-items:center;gap:12px">
                        <label class="switch" style="margin:0">
                            <input type="checkbox" name="is_active" value="1" {{ $val('is_active', true) ? 'checked' : '' }}><span class="slider"></span>
                        </label>
                        <span style="font-weight:700;font-size:14px">쇼핑몰에 노출</span>
                    </div>

                    <button class="btn btn-accent" type="submit" style="width:100%">{{ $isEdit ? '수정 저장' : '카테고리 추가' }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
