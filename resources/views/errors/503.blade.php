@extends('errors.layout')

@section('code', '503')
@section('title', 'Maintenance in progress')
@section('message', 'We are making a few improvements. Please try again shortly.')
@section('hide-actions', 'true')

@section('artwork')
<svg class="error-artwork" viewBox="0 0 360 210" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" data-artwork="roadwork">
    <ellipse cx="180" cy="101" rx="148" ry="91" fill="#eef5ff"/>
    <path d="M30 174h300" stroke="#d8e4f4" stroke-width="3" stroke-linecap="round"/>
    <path d="M49 184h262" stroke="#b5ccef" stroke-width="3" stroke-dasharray="12 10" stroke-linecap="round"/>
    <ellipse cx="146" cy="165" rx="86" ry="9" fill="#d8e4f4"/>
    <g class="artwork-car">
        <path d="m87 119 18-33c3-5 8-8 14-8h48c6 0 11 3 14 8l21 33" fill="#0b5ed7"/>
        <path d="m112 90-13 26h40V90zm35 0v26h43l-17-26z" fill="#dcecff"/>
        <path d="M80 116h125c10 0 18 8 18 18v18H69v-21c0-8 5-15 11-15z" fill="#0d6efd"/>
        <rect x="74" y="127" width="17" height="8" rx="3" fill="#fff"/>
        <rect x="204" y="127" width="15" height="8" rx="3" fill="#ffdc80"/>
        <path d="M69 149h155" stroke="#0b5ed7" stroke-width="7" stroke-linecap="round"/>
        <circle cx="99" cy="152" r="16" fill="#243247"/>
        <circle cx="99" cy="152" r="7" fill="#d8e4f4"/>
        <circle cx="191" cy="152" r="16" fill="#243247"/>
        <circle cx="191" cy="152" r="7" fill="#d8e4f4"/>
    </g>
    <path d="M253 151h59l-8-43h-43z" fill="#ffcf70"/>
    <path d="m269 140 7-20 7 20zm-3-35h26" fill="none" stroke="#bc7a16" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M238 156h88m-75 7 5 7m53-7-5 7" fill="none" stroke="#526175" stroke-width="3" stroke-linecap="round"/>
</svg>
@endsection
