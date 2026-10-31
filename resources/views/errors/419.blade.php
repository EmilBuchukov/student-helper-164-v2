@extends('errors.layout')

@section('code', '419')
@section('message', __('ui.errors.title_419'))
@section('description', __('ui.errors.419'))
@section('actions')<a href="javascript:location.reload()" class="inline-flex min-h-11 items-center rounded border border-line-strong bg-surface px-5 text-sm font-semibold text-ink transition hover:bg-sunken">{{ __('ui.errors.reload') }}</a>@endsection
