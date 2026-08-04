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
                        <div class="rte" id="descWrap"><div id="descEditor"></div></div>
                        <input type="hidden" name="description" id="descInput" value="{{ old('description', $product->description) }}">
                        <div class="hint">이미지는 복사·붙여넣기(Ctrl+V)하거나 툴바의 🖼 아이콘으로 넣을 수 있습니다.</div>
                        @error('description')<div class="err-msg">{{ $message }}</div>@enderror
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
                        <div class="up-thumb" style="width:100%;height:280px;margin-bottom:12px">
                            <img src="{{ asset($product->main_image) }}" alt="" data-main-preview>
                        </div>
                        @if($editing)
                            <button type="button" class="btn btn-sm" style="width:100%;margin-bottom:10px" onclick="openImageModal()">
                                ✎ 이미지 편집 (회전·밝기·대비·크롭)
                            </button>
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

            <div class="panel">
                <div class="panel-h">
                    <div><h2>상세 이미지</h2><div class="sub">상품 상세페이지 본문에 세로로 이어붙여 표시됩니다</div></div>
                    @if($editing && $product->detailImages->count())
                        <button type="button" class="btn btn-sm" onclick="markAllDetail()">전체 선택 삭제</button>
                    @endif
                </div>
                <div class="panel-b">
                    @if($editing && $product->detailImages->count())
                        <div class="up-grid" id="detailGrid">
                            @foreach($product->detailImages as $img)
                                <div class="up-thumb">
                                    <img src="{{ asset($img->path) }}" alt="">
                                    <label title="삭제"><input type="checkbox" class="rmDetail" name="remove_images[]" value="{{ $img->id }}" hidden onchange="this.closest('.up-thumb').style.opacity=this.checked?0.3:1">✕</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="hint">총 {{ $product->detailImages->count() }}장 · ✕ 클릭 시 저장할 때 삭제됩니다.</div>
                    @else
                        <div class="hint">등록된 상세 이미지가 없습니다.</div>
                    @endif
                    <label class="filebox" style="margin-top:10px">
                        <input type="file" name="detail_images[]" accept="image/*" multiple hidden id="detInput">
                        <span id="detLabel">상세 이미지 추가 (여러 장 선택 가능)</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- 상품 옵션 --}}
    <div class="panel">
        <div class="panel-h">
            <div><h2>상품 옵션</h2><div class="sub">사이즈·색상 등 선택지와 추가 금액. 옵션이 있으면 고객이 반드시 선택해야 장바구니에 담깁니다.</div></div>
            <button type="button" class="btn btn-sm btn-accent" onclick="addOptionRow()">+ 옵션 추가</button>
        </div>
        <div class="panel-b">
            <table class="table" id="optTable">
                <thead><tr>
                    <th style="width:170px">옵션 구분</th>
                    <th>선택지 <span class="req">*</span></th>
                    <th style="width:140px">추가 금액</th>
                    <th style="width:110px">재고</th>
                    <th style="width:80px">사용</th>
                    <th style="width:70px">삭제</th>
                </tr></thead>
                <tbody id="optBody">
                    @foreach(old('options', $editing ? $product->options->map(fn($o) => [
                        'id' => $o->id, 'group_name' => $o->group_name, 'name' => $o->name,
                        'extra_price' => $o->extra_price, 'stock' => $o->stock, 'is_active' => $o->is_active ? 1 : 0,
                    ])->all() : []) as $i => $row)
                        <tr>
                            <td><input type="hidden" name="options[{{ $i }}][id]" value="{{ $row['id'] ?? '' }}">
                                <input class="input" style="height:36px" name="options[{{ $i }}][group_name]" value="{{ $row['group_name'] ?? '' }}" placeholder="예) 사이즈"></td>
                            <td><input class="input" style="height:36px" name="options[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" placeholder="예) 260mm"></td>
                            <td><input class="input" style="height:36px;text-align:right" type="number" name="options[{{ $i }}][extra_price]" value="{{ $row['extra_price'] ?? 0 }}"></td>
                            <td><input class="input" style="height:36px;text-align:right" type="number" min="0" name="options[{{ $i }}][stock]" value="{{ $row['stock'] ?? 0 }}"></td>
                            <td style="text-align:center"><input type="checkbox" name="options[{{ $i }}][is_active]" value="1" {{ ($row['is_active'] ?? 1) ? 'checked' : '' }}></td>
                            <td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">삭제</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="hint" style="margin-top:10px">
                추가 금액은 판매가에 더해집니다(할인가가 있으면 할인가 기준). 음수도 넣을 수 있습니다.<br>
                옵션 구분이 같은 선택지끼리 하나의 선택 상자로 묶입니다. 비워 두면 ‘옵션’으로 표시됩니다.
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <button class="btn btn-accent" type="submit">{{ $editing ? '수정 저장' : '상품 등록' }}</button>
        <a href="{{ route('manage.products.index') }}" class="btn">취소</a>
    </div>
</form>

{{-- 대표 이미지 편집 모달 (상품 폼 바깥에 둬야 폼 중첩이 되지 않는다) --}}
@if($editing && $product->main_image)
<div class="modal" id="imgModal" hidden aria-modal="true" role="dialog" aria-label="대표 이미지 편집">
    <div class="modal-back" onclick="closeImageModal()"></div>
    <div class="modal-card">
        <div class="modal-h">
            <div><h2>대표 이미지 편집</h2><div class="sub">회전 · 밝기 · 대비 · 크롭 — 저장하면 대표 이미지가 교체됩니다</div></div>
            <button type="button" class="modal-x" onclick="closeImageModal()" aria-label="닫기">✕</button>
        </div>
        <div class="modal-b">
            @include('manage.products._image-editor', ['product' => $product, 'ajax' => true])
        </div>
    </div>
</div>
@endif

@push('styles')
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<style>
    .rte{border:1.5px solid var(--line);border-radius:10px;transition:border-color .15s;background:#fff}
    .rte.focused{border-color:var(--accent)}
    .rte .ql-toolbar{border:none;border-bottom:1px solid var(--line);border-radius:10px 10px 0 0;background:#fafbfc;padding:7px 10px}
    .rte .ql-container{border:none;font-family:inherit}
    .rte .ql-editor{min-height:240px;max-height:520px;overflow-y:auto;padding:14px;font-size:14px;line-height:1.75;color:var(--ink)}
    .rte .ql-editor.ql-blank::before{color:#9aa0aa;font-style:normal}
    .rte .ql-editor img{max-width:100%;height:auto}
</style>
@endpush

@push('scripts')
<script>
    var mi=document.getElementById('mainInput');
    if(mi) mi.addEventListener('change',function(){document.getElementById('mainLabel').textContent=this.files[0]?('선택됨: '+this.files[0].name):'대표 이미지 업로드 (클릭)';});
    var gi=document.getElementById('galInput');
    if(gi) gi.addEventListener('change',function(){document.getElementById('galLabel').textContent=this.files.length?(this.files.length+'장 선택됨'):'갤러리 이미지 추가';});
    var di=document.getElementById('detInput');
    if(di) di.addEventListener('change',function(){document.getElementById('detLabel').textContent=this.files.length?(this.files.length+'장 선택됨'):'상세 이미지 추가';});

    // 상세 이미지 전체 삭제 표시
    function markAllDetail(){
        var boxes = document.querySelectorAll('.rmDetail');
        if(!boxes.length) return;
        var on = !boxes[0].checked;
        if(on && !confirm('상세 이미지 ' + boxes.length + '장을 모두 삭제 표시할까요? (저장해야 실제로 지워집니다)')) return;
        boxes.forEach(function(b){ b.checked = on; b.closest('.up-thumb').style.opacity = on ? 0.3 : 1; });
    }
    window.markAllDetail = markAllDetail;

    // 대표 이미지 편집 모달
    window.openImageModal = function(){
        var m = document.getElementById('imgModal');
        if(!m) return;
        m.hidden = false;
        document.body.style.overflow = 'hidden';
    };
    window.closeImageModal = function(){
        var m = document.getElementById('imgModal');
        if(!m) return;
        m.hidden = true;
        document.body.style.overflow = '';
    };
    document.addEventListener('keydown', function(e){
        if(e.key === 'Escape') window.closeImageModal();
    });

    // 저장 완료 알림
    window.manageToast = function(msg){
        var t = document.createElement('div');
        t.className = 'm-toast';
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(function(){ t.remove(); }, 2600);
    };

    // 옵션 행 추가
    var optIndex = document.querySelectorAll('#optBody tr').length;
    window.addOptionRow = function(){
        var i = optIndex++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="hidden" name="options['+i+'][id]" value="">' +
            '<input class="input" style="height:36px" name="options['+i+'][group_name]" placeholder="예) 사이즈"></td>' +
            '<td><input class="input" style="height:36px" name="options['+i+'][name]" placeholder="예) 260mm"></td>' +
            '<td><input class="input" style="height:36px;text-align:right" type="number" name="options['+i+'][extra_price]" value="0"></td>' +
            '<td><input class="input" style="height:36px;text-align:right" type="number" min="0" name="options['+i+'][stock]" value="0"></td>' +
            '<td style="text-align:center"><input type="checkbox" name="options['+i+'][is_active]" value="1" checked></td>' +
            '<td><button type="button" class="btn btn-sm btn-danger">삭제</button></td>';
        tr.querySelector('button').addEventListener('click', function(){ tr.remove(); });
        document.getElementById('optBody').appendChild(tr);
        tr.querySelector('input[name$="[group_name]"]').focus();
    };
</script>

{{-- 상세 설명 리치 에디터 (Quill) --}}
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function(){
    var editorEl = document.getElementById('descEditor');
    var wrapEl   = document.getElementById('descWrap');
    var hiddenEl = document.getElementById('descInput');
    if(!editorEl || !hiddenEl || typeof Quill === 'undefined') return;

    var UPLOAD_URL = @json(route('manage.products.upload-image'));
    var CSRF = document.querySelector('meta[name=csrf-token]').content;

    // 이미지 크기 조절 결과(style/width/height)가 저장 후에도 남도록 blot 확장
    var ImageBlot = Quill.import('formats/image');
    var KEEP = ['alt', 'src', 'width', 'height', 'style'];
    class SizedImage extends ImageBlot {
        static formats(node){
            return KEEP.reduce(function(f, k){
                if(node.hasAttribute(k)) f[k] = node.getAttribute(k);
                return f;
            }, {});
        }
        format(name, value){
            if(KEEP.indexOf(name) > -1){
                value ? this.domNode.setAttribute(name, value) : this.domNode.removeAttribute(name);
            } else {
                super.format(name, value);
            }
        }
    }
    Quill.register(SizedImage, true);

    var quill = new Quill(editorEl, {
        theme: 'snow',
        placeholder: '규격 · 소재 · 인증 정보 등 구매 판단에 필요한 내용을 적어 주세요.',
        modules: {
            toolbar: [
                [{ header: [false, 2, 3, 4] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ align: [] }],
                ['blockquote', 'link', 'image'],
                ['clean'],
            ],
        },
    });

    // 기존 값 로드 — HTML이면 그대로 주입(이미지 크기 보존), 평문이면 텍스트로
    var initial = (hiddenEl.value || '').trim();
    if(initial){
        if(/<\w+[\s\S]*?>/.test(initial)){
            quill.root.innerHTML = initial;
            quill.update();
        } else {
            quill.setText(initial);
        }
    }

    quill.on('selection-change', function(r){ wrapEl.classList.toggle('focused', !!r); });

    function upload(file){
        if(!file) return;
        var fd = new FormData();
        fd.append('image', file);
        fetch(UPLOAD_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: fd,
        }).then(function(r){ return r.ok ? r.json() : Promise.reject(r.status); })
          .then(function(d){
              if(!d.url) return;
              var range = quill.getSelection(true) || { index: quill.getLength() };
              quill.insertEmbed(range.index, 'image', d.url);
              quill.setSelection(range.index + 1);
          })
          .catch(function(err){ alert('이미지 업로드에 실패했습니다. (' + err + ')'); });
    }

    quill.getModule('toolbar').addHandler('image', function(){
        var inp = document.createElement('input');
        inp.type = 'file'; inp.accept = 'image/*';
        inp.onchange = function(){ if(inp.files[0]) upload(inp.files[0]); };
        inp.click();
    });

    // 붙여넣기 · 드래그 앤 드롭으로 이미지 첨부
    quill.root.addEventListener('paste', function(e){
        var item = Array.prototype.slice.call((e.clipboardData || {}).items || [])
            .filter(function(x){ return x.type.indexOf('image/') === 0; })[0];
        if(!item) return;
        e.preventDefault();
        upload(item.getAsFile());
    });
    quill.root.addEventListener('drop', function(e){
        var file = Array.prototype.slice.call((e.dataTransfer || {}).files || [])
            .filter(function(f){ return f.type.indexOf('image/') === 0; })[0];
        if(!file) return;
        e.preventDefault();
        upload(file);
    });

    // 저장 직전 HTML을 hidden으로 동기화 (빈 내용이면 빈 문자열)
    var form = editorEl.closest('form');
    if(form){
        form.addEventListener('submit', function(){
            hiddenEl.value = quill.getLength() <= 1 ? '' : quill.root.innerHTML;
        });
    }
})();
</script>
@endpush
@endsection
