@extends('manage.layout')
@php $editing = $buyer->exists; @endphp
@section('title', $editing ? '구매자 수정' : '구매자 등록')
@section('page', $editing ? '구매자 수정' : '구매자 등록')
@section('crumb', '소매처 · 구매자')

@section('content')
<form action="{{ $editing ? route('purchaser.buyers.update', $buyer) : route('purchaser.buyers.store') }}" method="post" style="max-width:720px">
    @csrf @if($editing) @method('PUT') @endif
    <div class="panel">
        <div class="panel-h"><h2>구매자 정보</h2></div>
        <div class="panel-b">
            <div class="form-2">
                <div class="form-row"><label>소매처명 <span class="req">*</span></label>
                    <input class="input" name="shop_name" value="{{ old('shop_name', $buyer->shop_name) }}" placeholder="예) OO약국">
                    @error('shop_name')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="form-row"><label>구매자 이름 <span class="req">*</span></label>
                    <input class="input" name="name" value="{{ old('name', $buyer->name) }}" placeholder="홍길동">
                    @error('name')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-2">
                <div class="form-row"><label>구매자 사업자번호</label><input class="input" name="business_no" value="{{ old('business_no', $buyer->business_no) }}" placeholder="000-00-00000"></div>
                <div class="form-row"><label>구매자 전화번호</label><input class="input" name="phone" value="{{ old('phone', $buyer->phone) }}" placeholder="010-0000-0000"></div>
            </div>
            <div class="form-row"><label>주소</label><input class="input" name="address" value="{{ old('address', $buyer->address) }}"></div>
            <div class="form-row"><label>메모</label><textarea class="input" name="memo" rows="2">{{ old('memo', $buyer->memo) }}</textarea></div>
        </div>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-accent" type="submit">{{ $editing ? '수정 저장' : '등록' }}</button>
        <a href="{{ route('purchaser.buyers.index') }}" class="btn">취소</a>
    </div>
</form>
@endsection
