@extends('errors::layout')

@section('code', '503')
@section('title_ar', __('app.maintenance_1st_message', [], 'ar'))
@section('text_ar', __('app.maintenance_2nd_message', [], 'ar'))
@section('title_en', __('app.maintenance_1st_message', [], 'en'))
@section('text_en', __('app.maintenance_2nd_message', [], 'en'))
@section('meta')
    <meta property="og:title" content="{{ __('app.maintenance_title', [], 'ar') }} - {{ __('app.name', [], 'ar') }}">
    <meta property="og:image" content="{{ asset('images/pages/maintenance.webp') }}">
    <meta name="twitter:card" content="summary_large_image">
@endsection
