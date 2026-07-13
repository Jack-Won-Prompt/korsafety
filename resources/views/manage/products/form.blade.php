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
                            <label>정상가 (원)</label>
                            <input class="input" type="number" name="price" value="{{ old('price', $product->price) }}" placeholder="0">
                        </div>
                        <div class="form-row">
                            <label>할인가 (원)</label>
                            <input class="input" type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" placeholder="할인 시 입력">
                            <div class="hint">할인가가 정상가보다 낮으면 할인 배지가 표시됩니다.</div>
                        </div>
                    </div>
                    <div class="form-row" style="display:flex;align-items:center;gap:12px">
                        <label class="switch" style="margin:0"><input type="checkbox" name="is_soldout" value="1" {{ old('is_soldout', $product->is_soldout) ? 'checked' : '' }}><span class="slider"></span></label>
                        <span style="font-weight:700;font-size:14px">품절 처리</span>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="panel">
                <div class="panel-h"><h2>대표 이미지</h2></div>
                <div class="panel-b">
                    @if($product->main_image)
                        <div class="up-thumb" style="width:100%;height:180px;margin-bottom:12px"><img src="{{ asset($product->main_image) }}" alt=""></div>
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
