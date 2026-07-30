<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>피킹 리스트</title>
<style>
    @font-face { font-family: 'Nanum'; font-weight: normal; src: url("{{ storage_path('fonts/NanumGothic.ttf') }}") format('truetype'); }
    @font-face { font-family: 'Nanum'; font-weight: bold; src: url("{{ storage_path('fonts/NanumGothicBold.ttf') }}") format('truetype'); }
    * { font-family: 'Nanum', 'Malgun Gothic', sans-serif; box-sizing: border-box; }
    body { margin: 0; padding: 20px 22px; color: #1a1a1a; font-size: 12px; }
    h1 { font-size: 20px; margin: 0 0 2px; letter-spacing: 2px; }
    .meta { font-size: 11px; color: #666; margin-bottom: 14px; }
    .sec { font-size: 13px; font-weight: bold; margin: 16px 0 6px; padding: 5px 8px; background: #eee; border-left: 4px solid #2f7d32; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #bbb; padding: 6px 7px; font-size: 11.5px; }
    th { background: #333; color: #fff; }
    .c { text-align: center; } .r { text-align: right; }
    .agg th { background: #2f7d32; }
    .agg .qty { font-weight: bold; font-size: 13px; text-align: center; }
    .ord { margin-top: 14px; page-break-inside: avoid; }
    .ord-h { border-bottom: 2px solid #333; margin-bottom: 4px; }
    .ord-h table { width: 100%; }
    .ord-h td { border: 0; padding: 4px 0; vertical-align: middle; font-size: 12px; font-weight: bold; }
    .ord-h .no { color: #2f7d32; }
    .ord-h .addr { font-weight: normal; color: #555; }
    .pick-c { width: 30px; text-align: center; }
    .pick-box { display: inline-block; width: 15px; height: 15px; border: 1.5px solid #333; }
    .bcode { height: 34px; display: block; }
    .bcode-c { width: 130px; text-align: center; }
    .bcode-c img { height: 30px; width: 120px; }
    .bcode-c .num { font-size: 9px; color: #444; letter-spacing: .5px; }
    .ord-bc { width: 158px; text-align: right; white-space: nowrap; }
    .ord-bc img { height: 32px; width: 150px; display: inline-block; }
    .ord-bc .num { font-size: 9px; color: #444; text-align: center; }
    .toolbar { position: sticky; top: 0; background: #12151b; color: #fff; padding: 12px 16px; margin: -20px -22px 16px; display: flex; gap: 10px; align-items: center; }
    .toolbar button { background: #ff5722; color: #fff; border: 0; border-radius: 8px; padding: 9px 18px; font-weight: 700; font-size: 13px; cursor: pointer; }
    .toolbar .x { background: #333; }
    @media print { .toolbar { display: none; } body { padding: 0; } }
</style>
</head>
<body>
    @if($print ?? false)
    <div class="toolbar">
        <b>피킹 리스트</b>
        <span style="font-size:12px;color:#aaa">— 인쇄(Ctrl+P)하거나 PDA로 확인하세요</span>
        <button onclick="window.print()" style="margin-left:auto">🖨 인쇄</button>
        <button class="x" onclick="window.close()">닫기</button>
    </div>
    @endif

    <h1>피 킹 리 스 트</h1>
    <div class="meta">출력일시 {{ $printedAt->format('Y-m-d H:i') }} · 대상 주문 {{ $orders->count() }}건 · 총 피킹 수량 {{ number_format($totalItems) }}개</div>

    {{-- 품목별 집계 (효율 피킹) --}}
    <div class="sec">① 품목별 집계 — 창고에서 한 번에 피킹</div>
    <table class="agg">
        <thead><tr><th style="width:40px">No</th><th>품목</th><th style="width:132px">상품코드</th><th style="width:80px">총 수량</th><th style="width:64px">주문 수</th><th style="width:40px">완료</th></tr></thead>
        <tbody>
            @foreach($agg as $row)
                <tr>
                    <td class="c">{{ $loop->iteration }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="bcode-c">
                        @if($row['barcode'])<img src="{{ $row['barcode'] }}" alt=""><div class="num">{{ $row['code'] }}</div>@else<span class="t-sub">-</span>@endif
                    </td>
                    <td class="qty">{{ number_format($row['qty']) }}</td>
                    <td class="c">{{ $row['orders'] }}</td>
                    <td class="c"><span class="pick-box"></span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- 주문별 상세 (분류·포장) --}}
    <div class="sec">② 주문별 상세 — 분류 · 포장</div>
    @foreach($orders as $o)
        <div class="ord">
            <div class="ord-h">
                <table>
                    <tr>
                        <td>주문 <span class="no">{{ $o->order_no }}</span> · 수령인 {{ $o->receiver_name ?: $o->customer_name }} · {{ $o->customer_phone }}
                            @if($o->address1)<span class="addr"> / {{ trim(($o->address1?:'').' '.($o->address2?:'')) }}</span>@endif
                        </td>
                        @if(!empty($orderBarcodes[$o->id]))
                        <td class="ord-bc"><img src="{{ $orderBarcodes[$o->id] }}" alt=""><div class="num">{{ $o->order_no }}</div></td>
                        @endif
                    </tr>
                </table>
            </div>
            <table>
                <thead><tr><th class="pick-c">✔</th><th style="width:34px">No</th><th>품목</th><th style="width:70px">수량</th></tr></thead>
                <tbody>
                    @foreach($o->items as $it)
                        <tr>
                            <td class="pick-c"><span class="pick-box"></span></td>
                            <td class="c">{{ $loop->iteration }}</td>
                            <td>{{ $it->product_name }}</td>
                            <td class="c" style="font-weight:bold">{{ number_format($it->qty) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
