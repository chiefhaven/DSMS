@php
    $isKnowledgeActive = request()->is('knowledge*');
@endphp
<li>
    <a class="nav-main-link {{ $isKnowledgeActive ? 'active' : '' }}" href="{{ url('/knowledge') }}" @if($isKnowledgeActive) style="background-color: rgba(255,255,255,0.08);" @endif>
        <i class="nav-main-link-icon fa fa-book-open"></i>
        <span class="nav-main-link-name">Knowledge</span>
        <span class="badge bg-danger ms-2">New</span>
    </a>
</li>