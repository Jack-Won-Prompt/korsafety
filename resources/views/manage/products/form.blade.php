@extends('manage.layout')
@php $editing = $product->exists; @endphp
@section('title', $editing ? '상품 수정' : '상품 등록')
@section('page', $editing ? '상품 수정' : '상품 등록')
@section('crumb', '내 스토어 상품')

@section('content')
<form action="{{ $editing ? route('manage.products.update', $product) : route('manage.products.store') }}"
      method="post" enctype="multipart/form-data">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="grid-2">
        <div>
            <div class="panel">
                <div class="panel-h"><h2>기본 정보</h2></div>
                <div class="panel-b">
                    <div class="form-row">
                        <label>상품명 <span class="req">*</span></label>
                        <input class="input" name="name" value="{{ old('name', $product->name) }}" placeholder="상품명을 입력하세요">
                        @error('name')<div class="err-msg">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-2">
                        <div class="form-row">
                            <label>브랜드</label>
                            <input class="input" name="brand" value="{{ old('brand', $product->brand) }}" placeholder="예) 한국안전">
                        </div>
                        <div class="form-row">
                            <label>카테고리</label>
                            <select class="select" name="category_id">
                                <option value="">선택 안 함</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" {{ old('category_id', $product->category_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-2">
                        <div class="form-row">
                            <label>SKU (품번)</label>
                            <input class="input" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="예) KS-SHOE-001">
                            <div class="hint">창고·발주에서 상품을 구분하는 코드입니다.</div>
                        </div>
                        <div class="form-row">
                            <label>진열 순서</label>
                            <input class="input" type="number" name="sort" value="{{ old('sort', $product->sort ?? 0) }}" min="0">
                            <div class="hint">작을수록 목록 앞쪽에 표시됩니다.</div>
                        </div>
                    </div>
                    <div class="form-row">
                        <label>상세 설명</label>
                        <textarea class="input" name="description" rows="7" style="height:auto;padding:13px;line-height:1.7"
                                  placeholder="규격 · 소재 · 인증 정보 등 구매 판단에 필요한 내용을 적어 주세요.">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-h"><div><h2>가격 · 재고</h2><div class="sub">할인가가 정상가보다 낮을 때 할인 배지가 표시됩니다</div></div></div>
                <div class="panel-b">
                    <div class="form-2">
                        <div class="form-row">
                            <label>정상가 (원)</label>
                            <input class="input" type="number" name="price" value="{{ old('price', $product->price) }}" placeholder="0" min="0">
                        </div>
                        <div class="form-row">
                            <label>할인가 (원)</label>
                            <input class="input" type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" placeholder="할인 시 입력" min="0">
                        </div>
                    </div>
                    <div class="form-2">
                        <div class="form-row">
                            <label>매입가 (원)</label>
                            <input class="input" type="number" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" placeholder="마진 계산용" min="0">
                            @if($product->margin_percent !== null)<div class="hint">현재 마진율 {{ $product->margin_percent }}%</div>@endif
                        </div>
                        <div class="form-row">
                            <label>재고 수량</label>
                            <input class="input" type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" min="0">
                            <div style="display:flex;align-items:center;gap:12px;margin-top:12px">
                                <label class="switch" style="margin:0"><input type="checkbox" name="track_stock" value="1" {{ old('track_stock', $product->exists ? $product->track_stock : true) ? 'checked' : '' }}><span class="slider"></span></label>
                                <span style="font-weight:700;font-size:14px">재고 관리 사용</span>
                            </div>
                            <div class="hint">끄면 재고 부족·소진 경고 대상에서 제외됩니다.</div>
                        </div>
                    </div>
                    <div class="form-2">
                        <div class="form-row">
                            <label>안전재고</label>
                            <input class="input" type="number" name="safety_stock" value="{{ old('safety_stock', $product->safety_stock ?? 0) }}" min="0">
                            <div class="hint">재고가 이 수량 이하로 떨어지면 ‘재고 부족’으로 표시됩니다. (0이면 사용 안 함)</div>
                        </div>
                        <div class="form-row">
                            <label>판매 상태</label>
                            <div style="display:flex;flex-direction:column;gap:12px;padding-top:8px">
                                <div style="display:flex;align-items:center;gap:12px">
                                    <label class="switch" style="margin:0"><input type="checkbox" name="is_soldout" value="1" {{ old('is_soldout', $product->is_soldout) ? 'checked' : '' }}><span class="slider"></span></label>
                                    <span style="font-weight:700;font-size:14px">품절 처리</span>
                                </div>
                                <div style="display:flex;align-items:center;gap:12px">
                                    <label class="switch" style="margin:0"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->exists ? $product->is_active : true) ? 'checked' : '' }}><span class="slider"></span></label>
                                    <span style="font-weight:700;font-size:14px">쇼핑몰에 노출</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="panel">
                <div class="panel-h"><h2>대표 이미지</h2></div>
                <div class="panel-b">
                    @if($product->main_image)
                        <div class="up-thumb" style="width:100%;height:280px;margin-bottom:12px"><img src="{{ asset($product->main_image) }}" alt=""></div>
                        @if($editing)
                            <a href="{{ route('manage.products.image', $product) }}" class="btn btn-sm" style="width:100%;margin-bottom:10px">✎ 이미지 편집 (회전·밝기·대비·크롭)</a>
                        @endif
                    @endif
                    <label class="filebox" id="mainDrop">
                        <input type="file" name="main_image" accept="image/*" hidden id="mainInput">
                        <span id="mainLabel">{{ $product->main_image ? '대표 이미지 변경 (클릭)' : '대표 이미지 업로드 (클릭)' }}</span>
                    </label>
                    @error('main_image')<div class="err-msg">{{ $message }}</div>@enderror
                    <div class="hint">JPG·PNG·WEBP, 최대 8MB</div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-h"><h2>추가 이미지 (갤러리)</h2></div>
                <div class="panel-b">
                    @if($editing && $product->galleryImages->count())
                        <div class="up-grid">
                            @foreach($product->galleryImages as $img)
                                <div class="up-thumb">
                                    <img src="{{ asset($img->path) }}" alt="">
                                    <label title="삭제"><input type="checkbox" name="remove_images[]" value="{{ $img->id }}" hidden onchange="this.closest('.up-thumb').style.opacity=this.checked?0.3:1">✕</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="hint">✕ 클릭 시 저장할 때 삭제됩니다.</div>
                    @endif
                    <label class="filebox" style="margin-top:10px">
                        <input type="file" name="gallery[]" accept="image/*" multiple hidden id="galInput">
                        <span id="galLabel">갤러리 이미지 추가 (여러 장 선택 가능)</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <button class="btn btn-accent" type="submit">{{ $editing ? '수정 저장' : '상품 등록' }}</button>
        <a href="{{ route('manage.products.index') }}" class="btn">취소</a>
    </div>
</form>

@push('scripts')
<script>
    var mi=document.getElementById('mainInput');
    if(mi) mi.addEventListener('change',function(){document.getElementById('mainLabel').textContent=this.files[0]?('선택됨: '+this.files[0].name):'대표 이미지 업로드 (클릭)';});
    var gi=document.getElementById('galInput');
    if(gi) gi.addEventListener('change',function(){document.getElementById('galLabel').textContent=this.files.length?(this.files.length+'장 선택됨'):'갤러리 이미지 추가';});
</script>
@endpush
@endsection
