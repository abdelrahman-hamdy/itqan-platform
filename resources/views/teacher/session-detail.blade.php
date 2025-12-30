@extends('components.layouts.teacher')

@section('title', $session->title ?? __('teacher.sessions.academic.session_details'))

@section('content')
    <x-sessions.quran-session-detail :session="$session" view-type="teacher" />
@endsection
