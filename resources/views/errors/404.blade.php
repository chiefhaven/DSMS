@extends('errors.layout')

@section('code', '404')
@section('title', 'Page not found')
@section('message', 'This page may have moved or no longer exists.')

@section('artwork')
<svg class="error-artwork" viewBox="0 0 360 210" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" data-artwork="lost-car">
    <ellipse cx="180" cy="101" rx="148" ry="91" fill="#eef5ff"/>
    <path d="M30 171h300" stroke="#d8e4f4" stroke-width="3" stroke-linecap="round"/>
    <path d="M65 181h230" stroke="#b5ccef" stroke-width="3" stroke-dasharray="12 10" stroke-linecap="round"/>
    <path class="artwork-route" d="M74 82c-39-38 52-69 88-39s-19 64 16 75" fill="none" stroke="#94b9ed" stroke-width="3" stroke-dasharray="5 8" stroke-linecap="round"/>
    <path d="M281 158V53" stroke="#526175" stroke-width="6" stroke-linecap="round"/>
    <path d="M253 48h47l15 14-15 14h-47z" fill="#0d6efd"/>
    <path d="M300 85h-47l-15 14 15 14h47z" fill="#b5ccef"/>
    <path d="M265 62h27m-30 37h23" stroke="#fff" stroke-width="3" stroke-linecap="round"/>
    <ellipse cx="149" cy="166" rx="86" ry="9" fill="#d8e4f4"/>
    <g class="artwork-car">
    <path d="m88 124 18-34c3-5 8-8 14-8h50c6 0 11 3 14 8l22 34" fill="#0b5ed7"/>
    <path d="m112 93-13 27h40V93zm36 0v27h44l-17-27z" fill="#dcecff"/>
    <path d="M80 120h126c10 0 18 8 18 18v18H70v-21c0-8 4-15 10-15z" fill="#0d6efd"/>
    <rect x="73" y="130" width="17" height="9" rx="3" fill="#fff"/>
    <rect x="204" y="130" width="16" height="9" rx="3" fill="#ffdc80"/>
    <path d="M69 153h157" stroke="#0b5ed7" stroke-width="7" stroke-linecap="round"/>
    <circle cx="99" cy="156" r="16" fill="#243247"/>
    <circle cx="99" cy="156" r="7" fill="#d8e4f4"/>
    <circle cx="194" cy="156" r="16" fill="#243247"/>
    <circle cx="194" cy="156" r="7" fill="#d8e4f4"/>
    <rect x="137" y="65" width="25" height="20" rx="4" fill="#fff" stroke="#b5ccef" stroke-width="2"/>
    <path d="M146 70v10h9" fill="none" stroke="#0d6efd" stroke-width="3" stroke-linecap="round"/>
    </g>
    <path d="M220 46c0-7 11-7 11 0 0 5-6 4-6 9" fill="none" stroke="#526175" stroke-width="3" stroke-linecap="round"/>
    <circle cx="225" cy="62" r="2" fill="#526175"/>
</svg>
@endsection
