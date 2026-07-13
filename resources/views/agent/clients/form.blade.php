@extends('manage.layout')
@php $editing = $client->exists; @endphp
@section('title', $editing ? '거래처 수정' : '거래처 등록')
@section('page', $editing ? '거래처 수정' : '거래처 등록')
@section('crumb', '기업 · 병원 거래처')

@section('content')
<form action="{{ $editing ? route('agent.clients.update', $client) : route('agent.clients.store') }}" method="post" style="max-width:720px">
    @csrf @if($editing) @method('PUT') @endif
    <div class="panel">
        <div class="panel-h"><h2>거래처 정보</h2></div>
        <div class="panel-b">
            <div class="form-2">
                <div class="form-row"><label>거래처명 <span class="req">*</span></label>
                    <input class="input" name="name" value="{{ old('name', $client->name) }}" placeholder="예) 서울아산병원">
                    @error('name')<div class="err-msg">{{ $message }}</div>@enderror
                </div>
                <div class="form-row"><label>유형 <span class="req">*</span></label>
                    <select class="select" name="type">
                        @foreach(['company'=>'기업','hospital'=>'병원','etc'=>'기타'] as $k=>$v)
                            <option value="{{ $k }}" {{ old('type', $client->type)===$k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-2">
                <div class="form-row"><label>담당자</label><input class="input" name="contact_name" value="{{ old('contact_name', $client->contact_name) }}"></div>
                <div class="form-row"><label>연락처</label><input class="input" name="phone" value="{{ old('phone', $client->phone) }}"></div>
            </div>
            <div class="form-2">
                <div class="form-row"><label>사업자번호</label><input class="input" name="business_no" value="{{ old('business_no', $client->business_no) }}"></div>
                <div class="form-row"><label>주소</label><input class="input" name="address" value="{{ old('address', $client->address) }}"></div>
            </div>
            <div class="form-row"><label>메모</label><textarea class="input" name="memo" rows="2">{{ old('memo', $client->memo) }}</textarea></div>
        </div>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-accent" type="submit">{{ $editing ? '수정 저장' : '등록' }}</button>
        <a href="{{ route('agent.clients.index') }}" class="btn">취소</a>
    </div>
</form>
@endsection
