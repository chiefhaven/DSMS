@extends('layouts.backend')

@section('content')
    @include('students.partials.student-form', ['editing' => true])
@endsection
