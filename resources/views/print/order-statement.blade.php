<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>거래명세서 · {{ $order->order_no }}</title>
<style>
    @font-face { font-family: 'Nanum'; font-style: normal; font-weight: normal; src: url("{{ storage_path('fonts/NanumGothic.ttf') }}") format('truetype'); }
    @font-face { font-family: 'Nanum'; font-style: normal; font-weight: bold; src: url("{{ storage_path('fonts/NanumGothicBold.ttf') }}") format('truetype'); }
    * { font-family: 'Nanum', 'Malgun Gothic', sans-serif; box-sizing: border-box; }
    body { margin: 0; padding: 26px 30px; color: #1a1a1a; font-size: 12px; }
    .title { text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 14px; margin: 0 0 4px; }
    .subtitle { text-align: center; font-size: 11px; color: #666; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    .meta td { padding: 3px 2px; font-size: 11px; }
    .parties { margin: 8px 0 14px; }
    .parties td { vertical-align: top; width: 50%; padding: 0 6px; }
    .box { border: 1.5px solid #333; }
    .box .bh { background: #f0f0f0; font-weight: bold; text-align: center; padding: 5px; border-bottom: 1px solid #333; font-size: 12px; }
    .box table { font-size: 11px; }
    .box table td { padding: 4px 7px; border-bottom: 1px solid #ddd; }
    .box table td.k { background: #fafafa; width: 78px; color: #555; white-space: nowrap; }
    .items { border: 1.5px solid #333; margin-top: 4px; }
    .items th { background: #333; color: #fff; padding: 6px 4px; font-size: 11px; font-weight: bold; }
    .items td { padding: 5px 6px; border-bottom: 1px solid #e0e0e0; font-size: 11px; }
    .items .r { text-align: right; }
    .items .c { text-align: center; }
    .totals { margin-top: 10px; }
    .totals td { padding: 6px 10px; border: 1px solid #333; font-size: 12px; }
    .totals .k { background: #f0f0f0; font-weight: bold; width: 90px; text-align: center; }
    .totals .grand { background: #fff6f2; font-weight: bold; font-size: 15px; color: #d84315; }
    .foot { margin-top: 16px; font-size: 11px; color: #444; line-height: 1.7; }
    .stamp { text-align: right; margin-top: 8px; font-size: 12px; }
    .stamp b { font-size: 14px; }
</style>
</head>
<body>
    <div class="title">거 래 명 세 서</div>
    <div class="subtitle">{{ $seq > 1 ? '('.$seq.'회차 재발행) · ' : '' }}발행일 {{ optional($statementDate)->format('Y. m. d') }}</div>

    <table class="meta">
        <tr>
            <td>주문번호 : <b>{{ $order->order_no }}</b></td>
            <td style="text-align:right">거래일자 : {{ optional($statementDate)->format('Y-m-d') }}</td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td>
                <div class="box">
                    <div class="bh">공급자</div>
                    <table>
                        <tr><td class="k">등록번호</td><td>{{ config('company.biz_no') }}</td></tr>
                        <tr><td class="k">상호</td><td>{{ config('company.name') }}</td></tr>
                        <tr><td class="k">대표자</td><td>{{ config('company.ceo') }}</td></tr>
                        <tr><td class="k">주소</td><td>{{ config('company.address') }}</td></tr>
                        <tr><td class="k">업태 / 종목</td><td>{{ config('company.biz_class') }} / {{ config('company.biz_type') }}</td></tr>
                        <tr><td class="k">연락처</td><td>TEL {{ config('company.tel') }} · FAX {{ config('company.fax') }}</td></tr>
                    </table>
                </div>
            </td>
            <td>
                <div class="box">
                    <div class="bh">공급받는자</div>
                    <table>
                        <tr><td class="k">상호 / 성명</td><td>{{ $order->customer_name ?: $order->receiver_name }}</td></tr>
                        <tr><td class="k">연락처</td><td>{{ $order->customer_phone ?: '-' }}</td></tr>
                        <tr><td class="k">수령인</td><td>{{ $order->receiver_name ?: '-' }}</td></tr>
                        <tr><td class="k">주소</td><td>{{ trim(($order->address1 ?: '').' '.($order->address2 ?: '')) ?: '-' }}</td></tr>
                        <tr><td class="k">우편번호</td><td>{{ $order->postcode ?: '-' }}</td></tr>
                        <tr><td class="k">결제수단</td><td>{{ $order->payment_method ?: '-' }}</td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:34px">No</th>
                <th>품목</th>
                <th style="width:60px">수량</th>
                <th style="width:90px">단가</th>
                <th style="width:100px">금액</th>
            </tr>
        </thead>
        <tbody>
            @php $rows = $order->items; $min = 6; @endphp
            @foreach($rows as $i => $it)
                <tr>
                    <td class="c">{{ $i + 1 }}</td>
                    <td>{{ $it->product_name }}</td>
                    <td class="c">{{ number_format($it->qty) }}</td>
                    <td class="r">{{ number_format($it->price) }}</td>
                    <td class="r">{{ number_format($it->line_total) }}</td>
                </tr>
            @endforeach
            @for($i = $rows->count(); $i < $min; $i++)
                <tr><td class="c">{{ $i + 1 }}</td><td>&nbsp;</td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="k">공급가액</td><td class="r">{{ number_format($amounts['supply']) }} 원</td>
            <td class="k">부가세</td><td class="r">{{ number_format($amounts['tax']) }} 원</td>
        </tr>
        <tr>
            <td class="k">합계금액</td>
            <td class="r grand" colspan="3">{{ number_format($amounts['total']) }} 원</td>
        </tr>
    </table>

    <div class="foot">
        @if(config('company.bank'))
            · 입금계좌 : {{ config('company.bank') }} {{ config('company.bank_acct') }} (예금주 {{ config('company.bank_holder') }})<br>
        @endif
        · 위와 같이 거래 내역을 명세합니다. 상기 금액은 부가가치세가 포함된 금액입니다.
        <div class="stamp"><b>{{ config('company.name') }}</b> &nbsp; (인)</div>
    </div>
</body>
</html>
