@extends('layouts.backend')

@section('content')
    @include('fleet.partials.vehicle-form', ['editing' => false])
@endsection
