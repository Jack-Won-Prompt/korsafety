{{-- 이미지 편집을 별도 페이지로 띄우던 래퍼(@extends/@section)를 걷어내고,
     상품 등록/수정 폼 안에 그대로 끼워 넣을 수 있는 부분 뷰 형태로 바꾼다.
     이렇게 해야 가격·재고 등과 같은 화면에서 함께 입력·저장된다. --}}
@php
    // 신규 등록 화면에서는 아직 상품이 없으므로 $product 가 전달되지 않을 수 있다.
    $product = $product ?? null;
@endphp

@include('manage.products._image-editor', ['product' => $product, 'ajax' => false])
