@extends('errors::layout')

@section('code', '500')
@section('home_link', true)
@section('title_ar', __('app.server_error', [], 'ar'))
@section('text_ar', __('app.server_error_hint', [], 'ar'))
@section('title_en', __('app.server_error', [], 'en'))
@section('text_en', __('app.server_error_hint', [], 'en'))
