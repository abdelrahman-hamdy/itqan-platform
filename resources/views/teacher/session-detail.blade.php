@extends('components.layouts.teacher')

@section('title', $session->title ?? 'تفاصيل الجلسة')

@section('content')
    <x-sessions.quran-session-detail :session="$session" view-type="teacher" />
@endsection
