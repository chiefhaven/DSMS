@extends('errors.layout')

@section('code', '500')
@section('title', 'Something went wrong')
@section('message', 'We could not complete your request. Please try again later.')

@section('artwork')
<svg class="error-artwork" viewBox="0 0 360 210" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" data-artwork="car-repair">
    <ellipse cx="180" cy="101" rx="148" ry="91" fill="#eef5ff"/>
    <path d="M30 178h300" stroke="#d8e4f4" stroke-width="3" stroke-linecap="round"/>
    <ellipse cx="148" cy="174" rx="86" ry="9" fill="#d8e4f4"/>
    <g class="artwork-car">
    <path d="m86 126 19-35c3-5 8-8 14-8h48c6 0 11 3 14 8l21 35" fill="#0b5ed7"/>
    <path d="m112 94-14 28h40V94zm35 0v28h43l-17-28z" fill="#dcecff"/>
    <path d="M80 122h125c10 0 18 8 18 18v18H69v-21c0-8 5-15 11-15z" fill="#0d6efd"/>
    <path d="m188 122 21-36 10 6-14 30" fill="#0d6efd" stroke="#0b5ed7" stroke-width="3" stroke-linejoin="round"/>
    <rect x="74" y="133" width="17" height="8" rx="3" fill="#fff"/>
    <rect x="204" y="133" width="15" height="8" rx="3" fill="#ffdc80"/>
    <path d="M69 155h155" stroke="#0b5ed7" stroke-width="7" stroke-linecap="round"/>
    <circle cx="99" cy="158" r="16" fill="#243247"/>
    <circle cx="99" cy="158" r="7" fill="#d8e4f4"/>
    <circle cx="191" cy="158" r="16" fill="#243247"/>
    <circle cx="191" cy="158" r="7" fill="#d8e4f4"/>
    <rect x="135" y="66" width="25" height="19" rx="4" fill="#fff" stroke="#b5ccef" stroke-width="2"/>
    <path d="M144 71v9h9" fill="none" stroke="#0d6efd" stroke-width="3" stroke-linecap="round"/>
    </g>
    <path class="artwork-tool" d="m255 82-25 25a7 7 0 0 0 10 10l25-25c11 3 23-6 21-18l-10 10-10-3-3-10 10-10c-12-2-21 10-18 21z" fill="#526175"/>
    <path d="M230 63h-8m19-15-5-7m18 11 3-8" stroke="#94b9ed" stroke-width="3" stroke-linecap="round"/>
    <rect x="248" y="145" width="54" height="32" rx="5" fill="#ffcf70"/>
    <path d="M263 145v-7h24v7m-39 12h54" fill="none" stroke="#bc7a16" stroke-width="3" stroke-linejoin="round"/>
    <rect x="271" y="153" width="8" height="9" rx="2" fill="#bc7a16"/>
</svg>
@endsection
