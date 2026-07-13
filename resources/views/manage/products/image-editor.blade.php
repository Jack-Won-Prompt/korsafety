@extends('manage.layout')
@section('title', '이미지 편집')
@section('page', '대표 이미지 편집')
@section('crumb', $product->name)

@section('content')
<style>
    .ed-grid{display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start}
    .ed-stage{background:
        linear-gradient(45deg,#eef0f4 25%,transparent 25%),linear-gradient(-45deg,#eef0f4 25%,transparent 25%),
        linear-gradient(45deg,transparent 75%,#eef0f4 75%),linear-gradient(-45deg,transparent 75%,#eef0f4 75%);
        background-size:20px 20px;background-position:0 0,0 10px,10px -10px,-10px 0;
        border:1px solid var(--line);border-radius:14px;padding:20px;display:flex;align-items:center;justify-content:center;min-height:460px}
    .ed-wrap{position:relative;display:inline-block;line-height:0;box-shadow:0 6px 24px rgba(0,0,0,.12)}
    .ed-wrap canvas{max-width:100%;max-height:520px;display:block;background:#fff}
    .ed-wrap.cropping{cursor:crosshair}
    .ed-crop{position:absolute;border:2px dashed var(--accent);background:rgba(255,87,34,.14);display:none;pointer-events:none}
    .ed-ctrl{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:18px}
    .ed-ctrl h3{font-size:14px;margin:0 0 12px}
    .ed-group{padding:14px 0;border-top:1px solid var(--line)}
    .ed-group:first-of-type{border-top:0;padding-top:0}
    .ed-label{display:flex;justify-content:space-between;font-size:13px;font-weight:700;margin-bottom:8px}
    .ed-label .v{color:var(--accent);font-weight:800}
    .ed-row{display:flex;gap:8px}
    input[type=range]{width:100%;accent-color:var(--accent);height:28px}
    .ed-actions{display:flex;gap:8px;margin-top:16px}
</style>

<div class="ed-grid">
    <div>
        <div class="ed-stage">
            <div class="ed-wrap" id="edWrap">
                <canvas id="edCanvas"></canvas>
                <div class="ed-crop" id="edCrop"></div>
            </div>
        </div>
        <div class="hint" style="margin-top:10px" id="edHint">회전·밝기·대비를 조정하거나, 크롭으로 워터마크가 있는 영역을 잘라낼 수 있습니다.</div>
    </div>

    <div>
        <form action="{{ route('manage.products.image.save', $product) }}" method="post" id="edForm">
            @csrf
            <input type="hidden" name="image" id="edData">
            <div class="ed-ctrl">
                <h3>이미지 보정</h3>

                <div class="ed-group">
                    <div class="ed-label">회전 <span class="v" id="rotV">0°</span></div>
                    <div class="ed-row" style="margin-bottom:10px">
                        <button type="button" class="btn btn-sm" id="rotL" style="flex:1">↺ 왼쪽 90°</button>
                        <button type="button" class="btn btn-sm" id="rotR" style="flex:1">오른쪽 90° ↻</button>
                    </div>
                    <input type="range" id="rot" min="-45" max="45" step="1" value="0">
                    <div class="hint">미세 회전(수평 보정) −45°~45°</div>
                </div>

                <div class="ed-group">
                    <div class="ed-label">밝기 <span class="v" id="briV">100%</span></div>
                    <input type="range" id="bri" min="50" max="150" step="1" value="100">
                </div>

                <div class="ed-group">
                    <div class="ed-label">대비 <span class="v" id="conV">100%</span></div>
                    <input type="range" id="con" min="50" max="150" step="1" value="100">
                </div>

                <div class="ed-group">
                    <div class="ed-label">크롭 (영역 잘라내기)</div>
                    <div class="ed-row">
                        <button type="button" class="btn btn-sm" id="cropStart" style="flex:1">크롭 시작</button>
                        <button type="button" class="btn btn-sm btn-accent" id="cropApply" style="flex:1;display:none">적용</button>
                        <button type="button" class="btn btn-sm" id="cropCancel" style="flex:1;display:none">취소</button>
                    </div>
                </div>

                <div class="ed-actions">
                    <button type="button" class="btn" id="resetBtn" style="flex:1">초기화</button>
                    <button type="submit" class="btn btn-accent" id="saveBtn" style="flex:2">저장</button>
                </div>
            </div>
            <a href="{{ route('manage.products.edit', $product) }}" class="btn" style="width:100%;margin-top:12px">← 상품 편집으로</a>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function(){
    var SRC = "{{ asset($product->main_image) }}";
    var canvas = document.getElementById('edCanvas');
    var ctx = canvas.getContext('2d');
    var wrap = document.getElementById('edWrap');
    var cropBox = document.getElementById('edCrop');
    var base = new Image();
    var rot = 0, bri = 100, con = 100;      // rot = 미세(-45~45) + 90단위 누적
    var quarter = 0;                         // 90° 단위 누적
    var cropping = false, cropRect = null, dragStart = null;

    function totalDeg(){ return quarter*90 + rot; }

    function render(){
        var deg = totalDeg(), rad = deg*Math.PI/180;
        var iw = base.naturalWidth||base.width, ih = base.naturalHeight||base.height;
        if(!iw){ return; }
        var cos = Math.abs(Math.cos(rad)), sin = Math.abs(Math.sin(rad));
        var bw = Math.round(iw*cos + ih*sin), bh = Math.round(iw*sin + ih*cos);
        canvas.width = bw; canvas.height = bh;
        ctx.clearRect(0,0,bw,bh);
        ctx.save();
        ctx.filter = 'brightness('+bri+'%) contrast('+con+'%)';
        ctx.translate(bw/2, bh/2);
        ctx.rotate(rad);
        ctx.drawImage(base, -iw/2, -ih/2, iw, ih);
        ctx.restore();
    }

    base.onload = render;
    base.src = SRC;

    function bind(id, cb){ document.getElementById(id).addEventListener('input', cb); }
    bind('rot', function(){ rot=+this.value; document.getElementById('rotV').textContent=totalDeg()+'°'; render(); });
    bind('bri', function(){ bri=+this.value; document.getElementById('briV').textContent=bri+'%'; render(); });
    bind('con', function(){ con=+this.value; document.getElementById('conV').textContent=con+'%'; render(); });
    document.getElementById('rotL').onclick=function(){ quarter=(quarter+3)%4; document.getElementById('rotV').textContent=totalDeg()+'°'; render(); };
    document.getElementById('rotR').onclick=function(){ quarter=(quarter+1)%4; document.getElementById('rotV').textContent=totalDeg()+'°'; render(); };

    document.getElementById('resetBtn').onclick=function(){
        base = new Image(); base.onload=render; base.src=SRC;
        rot=0; quarter=0; bri=100; con=100;
        document.getElementById('rot').value=0; document.getElementById('bri').value=100; document.getElementById('con').value=100;
        document.getElementById('rotV').textContent='0°'; document.getElementById('briV').textContent='100%'; document.getElementById('conV').textContent='100%';
        endCrop();
    };

    // ---- crop ----
    function startCrop(){
        cropping=true; wrap.classList.add('cropping');
        document.getElementById('cropStart').style.display='none';
        document.getElementById('cropApply').style.display='';
        document.getElementById('cropCancel').style.display='';
        document.getElementById('edHint').textContent='이미지 위에서 드래그하여 남길 영역을 선택한 뒤 [적용]을 누르세요.';
    }
    function endCrop(){
        cropping=false; cropRect=null; dragStart=null; wrap.classList.remove('cropping');
        cropBox.style.display='none';
        document.getElementById('cropStart').style.display='';
        document.getElementById('cropApply').style.display='none';
        document.getElementById('cropCancel').style.display='none';
    }
    document.getElementById('cropStart').onclick=startCrop;
    document.getElementById('cropCancel').onclick=endCrop;

    wrap.addEventListener('mousedown', function(e){
        if(!cropping) return;
        var r=canvas.getBoundingClientRect();
        dragStart={x:e.clientX-r.left, y:e.clientY-r.top};
    });
    window.addEventListener('mousemove', function(e){
        if(!cropping || !dragStart) return;
        var r=canvas.getBoundingClientRect();
        var x=Math.max(0,Math.min(e.clientX-r.left, r.width));
        var y=Math.max(0,Math.min(e.clientY-r.top, r.height));
        var left=Math.min(x,dragStart.x), top=Math.min(y,dragStart.y);
        var w=Math.abs(x-dragStart.x), h=Math.abs(y-dragStart.y);
        cropBox.style.display='block';
        cropBox.style.left=left+'px'; cropBox.style.top=top+'px';
        cropBox.style.width=w+'px'; cropBox.style.height=h+'px';
        var scale = canvas.width / r.width;
        cropRect={x:left*scale, y:top*scale, w:w*scale, h:h*scale};
    });
    window.addEventListener('mouseup', function(){ dragStart=null; });

    document.getElementById('cropApply').onclick=function(){
        if(!cropRect || cropRect.w<5 || cropRect.h<5){ endCrop(); return; }
        var oc=document.createElement('canvas');
        oc.width=Math.round(cropRect.w); oc.height=Math.round(cropRect.h);
        oc.getContext('2d').drawImage(canvas, cropRect.x, cropRect.y, cropRect.w, cropRect.h, 0, 0, oc.width, oc.height);
        var url=oc.toDataURL('image/png');
        base=new Image();
        base.onload=function(){ quarter=0; rot=0; bri=100; con=100;
            document.getElementById('rot').value=0; document.getElementById('bri').value=100; document.getElementById('con').value=100;
            document.getElementById('rotV').textContent='0°'; document.getElementById('briV').textContent='100%'; document.getElementById('conV').textContent='100%';
            render();
        };
        base.src=url;
        endCrop();
        document.getElementById('edHint').textContent='크롭이 적용되었습니다. [저장]을 누르면 반영됩니다.';
    };

    // ---- save ----
    document.getElementById('edForm').addEventListener('submit', function(e){
        render();
        document.getElementById('edData').value = canvas.toDataURL('image/jpeg', 0.92);
    });
})();
</script>
@endpush
@endsection
