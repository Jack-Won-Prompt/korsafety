@php $c = config('company'); @endphp
<div style="max-width:560px;margin:0 auto;font-family:'Malgun Gothic',sans-serif;color:#222">
    <div style="background:#12151b;color:#fff;padding:26px 28px;border-radius:14px 14px 0 0">
        <div style="font-size:12px;letter-spacing:.15em;color:#ffab91">KOR SAFETY · 거래명세서</div>
        <div style="font-size:20px;font-weight:800;margin-top:6px">{{ $c['name'] }}</div>
    </div>
    <div style="border:1px solid #e8e9ee;border-top:0;border-radius:0 0 14px 14px;padding:26px 28px">
        <p style="font-size:14px;line-height:1.7;margin:0 0 18px">
            안녕하세요. <b>{{ $c['name'] }}</b>입니다.<br>
            주문 <b>{{ $order->order_no }}</b> 건의 거래명세서를 첨부드립니다.
        </p>
        <table style="width:100%;border-collapse:collapse;font-size:13.5px">
            <tr><td style="padding:8px 0;color:#6b7280;width:96px">주문번호</td><td style="padding:8px 0;font-weight:700">{{ $order->order_no }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280">주문일</td><td style="padding:8px 0">{{ optional($order->paid_at ?: $order->created_at)->format('Y-m-d') }}</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280">상품</td><td style="padding:8px 0">{{ optional($order->items->first())->product_name }} @if($order->items->count() > 1)외 {{ $order->items->count() - 1 }}건 @endif</td></tr>
            <tr><td style="padding:8px 0;color:#6b7280;border-top:1px solid #eee">합계금액</td><td style="padding:8px 0;border-top:1px solid #eee;font-weight:800;font-size:16px;color:#d84315">{{ number_format($order->total) }}원</td></tr>
        </table>
        <p style="font-size:13px;color:#6b7280;line-height:1.7;margin:20px 0 0">
            · 자세한 내역은 첨부된 PDF 거래명세서를 확인해 주세요.<br>
            · 문의: TEL {{ $c['tel'] }} · {{ $c['email'] }}
        </p>
    </div>
    <div style="text-align:center;color:#9aa0aa;font-size:11px;padding:16px">
        © {{ date('Y') }} {{ $c['name'] }}. All rights reserved.
    </div>
</div>
