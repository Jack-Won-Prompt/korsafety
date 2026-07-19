@extends('manage.layout')
@section('title', '주문 등록')
@section('page', '주문 등록')
@section('crumb', '구매자 대신 주문 등록')

@php $rate = (float) auth()->user()->purchaser->cashback_rate; @endphp

@section('content')
@if($buyers->isEmpty())
    <div class="panel"><div class="panel-b empty">
        먼저 구매자(소매처)를 등록해야 주문을 등록할 수 있습니다.
        <div style="margin-top:14px"><a href="{{ route('purchaser.buyers.create') }}" class="btn btn-accent">구매자 등록하러 가기</a></div>
    </div></div>
@else
<form action="{{ route('purchaser.orders.store') }}" method="post" id="orderForm">
    @csrf
    <div class="grid-2">
        <div>
            <div class="panel">
                <div class="panel-h"><h2>상품 추가</h2></div>
                <div class="panel-b">
                    <div style="position:relative">
                        <input class="input" id="prodSearch" placeholder="상품명 또는 브랜드로 검색…" autocomplete="off">
                        <div id="searchResults" style="position:absolute;left:0;right:0;top:50px;background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:0 12px 30px rgba(0,0,0,.12);z-index:20;display:none;max-height:340px;overflow-y:auto"></div>
                    </div>
                    <div class="hint">검색 후 상품을 클릭하면 아래 주문 목록에 추가됩니다.</div>

                    <table class="table" style="margin-top:14px" id="lineTable">
                        <thead><tr><th>상품</th><th style="width:90px">단가</th><th style="width:90px">수량</th><th style="width:100px;text-align:right">합계</th><th style="width:40px"></th></tr></thead>
                        <tbody id="lineBody">
                            <tr id="emptyRow"><td colspan="5" class="empty">추가된 상품이 없습니다.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div>
            <div class="panel">
                <div class="panel-h"><h2>주문 정보</h2></div>
                <div class="panel-b">
                    <div class="form-row">
                        <label>구매자(소매처) <span class="req">*</span></label>
                        <select class="select" name="buyer_id" required>
                            <option value="">구매자 선택</option>
                            @foreach($buyers as $b)
                                <option value="{{ $b->id }}">{{ $b->shop_name }} ({{ $b->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-row">
                        <label>주문 상태</label>
                        <select class="select" name="status">
                            <option value="pending">주문 접수 (캐쉬백 적립 대기)</option>
                            <option value="paid">결제 완료 (캐쉬백 적립)</option>
                        </select>
                        <div class="hint">결제완료 상태의 주문만 캐쉬백이 적립됩니다.</div>
                    </div>
                </div>
            </div>
            <div class="panel">
                <div class="panel-b">
                    <div class="summary-row" style="display:flex;justify-content:space-between;padding:8px 0"><span class="muted">주문금액</span><b id="sumTotal">0원</b></div>
                    <div class="summary-row" style="display:flex;justify-content:space-between;padding:8px 0;border-top:1px solid var(--line);margin-top:6px">
                        <span class="muted">예상 캐쉬백 ({{ rtrim(rtrim(number_format($rate,2),'0'),'.') }}%)</span>
                        <b id="sumComm" style="color:var(--accent);font-size:18px">0원</b>
                    </div>
                </div>
            </div>
            <button class="btn btn-accent" type="submit" style="width:100%;height:50px" id="submitBtn" disabled>주문 등록</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
(function(){
    var RATE = {{ $rate }};
    var idx = 0;
    var searchUrl = "{{ route('purchaser.orders.search') }}";
    var input = document.getElementById('prodSearch');
    var box = document.getElementById('searchResults');
    var body = document.getElementById('lineBody');
    var emptyRow = document.getElementById('emptyRow');
    var timer=null;

    function won(n){ return n.toLocaleString('ko-KR')+'원'; }

    input.addEventListener('input', function(){
        clearTimeout(timer);
        var q=this.value.trim();
        if(q.length<1){ box.style.display='none'; return; }
        timer=setTimeout(function(){
            fetch(searchUrl+'?q='+encodeURIComponent(q),{headers:{'X-Requested-With':'XMLHttpRequest'}})
              .then(function(r){return r.json();})
              .then(function(list){
                if(!list.length){ box.innerHTML='<div style="padding:14px;color:#8b93a1;font-size:13px">검색 결과가 없습니다.</div>'; box.style.display='block'; return; }
                box.innerHTML = list.map(function(p){
                    return '<div class="sr" data-id="'+p.id+'" data-name="'+encodeURIComponent(p.name)+'" data-price="'+p.price+'" style="display:flex;gap:10px;align-items:center;padding:9px 12px;cursor:pointer;border-bottom:1px solid #f1f2f5">'
                      + (p.image?'<img src="'+p.image+'" style="width:34px;height:34px;object-fit:contain;border:1px solid #eee;border-radius:6px">':'')
                      + '<div style="flex:1;min-width:0"><div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">'+p.name+'</div><div style="font-size:12px;color:#8b93a1">'+(p.brand||'')+' · '+won(p.price)+'</div></div></div>';
                }).join('');
                box.style.display='block';
              });
        },250);
    });
    document.addEventListener('click',function(e){
        var sr=e.target.closest('.sr');
        if(sr){ addLine(sr.dataset.id, decodeURIComponent(sr.dataset.name), parseInt(sr.dataset.price,10)); box.style.display='none'; input.value=''; return; }
        if(!e.target.closest('#prodSearch')) box.style.display='none';
    });

    function addLine(id,name,price){
        if(emptyRow) emptyRow.style.display='none';
        var i=idx++;
        var tr=document.createElement('tr');
        tr.innerHTML='<td><span class="t-name" style="font-size:13px">'+name+'</span>'
            +'<input type="hidden" name="items['+i+'][product_id]" value="'+id+'"></td>'
            +'<td>'+won(price)+'</td>'
            +'<td><input class="input qty" style="height:34px;width:70px;padding:0 8px" type="number" min="1" value="1" name="items['+i+'][qty]" data-price="'+price+'"></td>'
            +'<td style="text-align:right;font-weight:700" class="lt">'+won(price)+'</td>'
            +'<td><button type="button" class="btn btn-sm btn-danger rm">✕</button></td>';
        body.appendChild(tr);
        recalc();
    }
    document.addEventListener('input',function(e){ if(e.target.classList.contains('qty')) recalc(); });
    document.addEventListener('click',function(e){ if(e.target.classList.contains('rm')){ e.target.closest('tr').remove(); recalc(); }});

    function recalc(){
        var total=0, rows=body.querySelectorAll('tr');
        rows.forEach(function(tr){
            var q=tr.querySelector('.qty'); if(!q) return;
            var line=(parseInt(q.value,10)||0)*parseInt(q.dataset.price,10);
            var lt=tr.querySelector('.lt'); if(lt) lt.textContent=won(line);
            total+=line;
        });
        document.getElementById('sumTotal').textContent=won(total);
        document.getElementById('sumComm').textContent=won(Math.round(total*RATE/100));
        var hasLine=body.querySelectorAll('.qty').length>0;
        document.getElementById('submitBtn').disabled=!hasLine;
        if(!hasLine && emptyRow) emptyRow.style.display='';
    }
})();
</script>
@endpush
@endif
@endsection
