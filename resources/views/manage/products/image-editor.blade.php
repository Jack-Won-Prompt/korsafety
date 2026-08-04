@extends('manage.layout')
@section('title', '이미지 편집')
@section('page', '대표 이미지 편집')
@section('crumb', $product->name)

@section('content')
@include('manage.products._image-editor', ['product' => $product, 'ajax' => false])
@endsection
