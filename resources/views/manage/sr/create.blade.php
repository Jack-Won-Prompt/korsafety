@extends('manage.layout')
@section('title', 'SR 등록')
@section('page', 'SR 등록')
@section('crumb', '서비스 요청 접수')
@section('actions')
    <a href="{{ route('manage.sr.index') }}" class="btn btn-sm">← 목록</a>
@endsection

@section('content')
<form action="{{ route('manage.sr.store') }}" method="post">
    @csrf
    <div class="panel">
        <div class="panel-h"><div><h2>요청 내용</h2><div class="sub">접수되면 본사 담당자가 확인 후 답변합니다.</div></div></div>
        <div class="panel-b">
            <div class="form-row">
                <label>제목 <span class="req">*</span></label>
                <input class="input" name="title" value="{{ old('title') }}" placeholder="예) 주문 목록에서 피킹리스트 PDF가 열리지 않습니다">
                @error('title')<div class="err-msg">{{ $message }}</div>@enderror
            </div>
            <div class="form-2">
                <div class="form-row">
                    <label>유형 <span class="req">*</span></label>
                    <select class="select" name="category">
                        @foreach(\App\Models\ServiceRequest::CATEGORIES as $k => $v)
                            <option value="{{ $k }}" @selected(old('category', 'etc') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <label>중요도 <span class="req">*</span></label>
                    <select class="select" name="priority">
                        @foreach(\App\Models\ServiceRequest::PRIORITIES as $k => $v)
                            <option value="{{ $k }}" @selected(old('priority', 'normal') === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                    <div class="hint">‘긴급’은 업무가 중단된 장애일 때만 선택해 주세요.</div>
                </div>
            </div>
            <div class="form-row">
                <label>내용 <span class="req">*</span></label>
                <textarea class="input" name="content" rows="10" style="height:auto;padding:14px;line-height:1.7"
                          placeholder="언제 · 어느 화면에서 · 무엇을 하려다 · 어떤 결과가 났는지 순서대로 적어 주세요.">{{ old('content') }}</textarea>
                @error('content')<div class="err-msg">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div style="display:flex;gap:10px">
        <button class="btn btn-accent" type="submit">SR 접수</button>
        <a href="{{ route('manage.sr.index') }}" class="btn">취소</a>
    </div>
</form>
@endsection
