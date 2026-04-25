<style>
</style>

<x-guest-layout>
    <div class="split-layout">
        <!-- Left side - Illustration -->
        <div class="illustration-side">
            <div class="illustration-content">
                <!-- Outline Only Driving Illustration -->
                <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Outline Car -->
                    <g transform="translate(120, 150)">
                        <!-- Car body outline -->
                        <rect x="20" y="30" width="120" height="40" rx="8" stroke="white" stroke-width="3" fill="none"/>
                        <rect x="40" y="15" width="80" height="25" rx="6" stroke="white" stroke-width="3" fill="none"/>

                        <!-- Window outlines -->
                        <rect x="45" y="20" width="30" height="15" rx="3" stroke="white" stroke-width="2" fill="none"/>
                        <rect x="85" y="20" width="30" height="15" rx="3" stroke="white" stroke-width="2" fill="none"/>

                        <!-- Wheel outlines -->
                        <circle cx="45" cy="70" r="12" stroke="white" stroke-width="3" fill="none"/>
                        <circle cx="115" cy="70" r="12" stroke="white" stroke-width="3" fill="none"/>

                        <!-- Door handle -->
                        <circle cx="90" cy="42" r="2" stroke="white" stroke-width="1.5" fill="none"/>
                    </g>

                    <!-- Road line -->
                    <line x1="0" y1="220" x2="400" y2="220" stroke="white" stroke-width="3" stroke-dasharray="10 10"/>

                    <!-- Traffic light -->
                    <g transform="translate(280, 100)">
                        <rect x="0" y="0" width="20" height="60" rx="3" stroke="white" stroke-width="2" fill="none"/>
                        <circle cx="10" cy="15" r="5" stroke="white" stroke-width="2" fill="none"/>
                        <circle cx="10" cy="30" r="5" stroke="white" stroke-width="2" fill="none"/>
                        <circle cx="10" cy="45" r="5" stroke="white" stroke-width="2" fill="none"/>
                    </g>

                    <!-- Steering wheel -->
                    <g transform="translate(180, 30)">
                        <circle cx="20" cy="20" r="15" stroke="white" stroke-width="2" fill="none"/>
                        <line x1="20" y1="10" x2="20" y2="30" stroke="white" stroke-width="2"/>
                        <line x1="10" y1="20" x2="30" y2="20" stroke="white" stroke-width="2"/>
                    </g>

                    <!-- Question mark for "forgot" theme -->
                    <g transform="translate(80, 50)">
                        <text x="20" y="20" text-anchor="middle" fill="white" font-size="40" font-weight="bold" opacity="0.3">?</text>
                    </g>

                    <!-- Cloud -->
                    <g opacity="0.3">
                        <circle cx="300" cy="50" r="15" stroke="white" stroke-width="1.5" fill="none"/>
                        <circle cx="315" cy="45" r="18" stroke="white" stroke-width="1.5" fill="none"/>
                        <circle cx="330" cy="50" r="15" stroke="white" stroke-width="1.5" fill="none"/>
                    </g>
                </svg>
                <h2>{{ env('SCHOOL_NAME', 'Driving School') }}</h2>
                <p>Don't worry! We'll help you get back on track.</p>
            </div>
        </div>

        <!-- Right side - Forgot Password Form -->
        <div class="form-side">
            <div class="form-container">
                <x-auth-card>
                    <div class="logo-container">
                        <x-slot name="logo">
                            <a href="/">
                                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                            </a>
                        </x-slot>
                    </div>

                    <h1 style="font-size: 1.8em; text-align:center; color: rgb(2, 2, 92); margin-bottom: 0.5rem; font-weight: bold;">
                        Forgot Password?
                    </h1>

                    <!-- Info Text -->
                    <div class="forgot-info">
                        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                    </div>

                    <!-- Session Status -->
                    <x-auth-session-status class="mb-4" :status="session('status')" />

                    <!-- Validation Errors -->
                    <x-auth-validation-errors class="mb-4" :errors="$errors" />

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <!-- Email Address -->
                        <div>
                            <x-label for="email" :value="__('Email Address')" />
                            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" placeholder="your@email.com" required autofocus />
                        </div>

                        <div class="flex items-center justify-between mt-4">
                            <a class="underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                                {{ __('Back to Login') }}
                            </a>

                            <x-button style="background: rgb(2, 2, 92);">
                                {{ __('Send Reset Link') }}
                            </x-button>
                        </div>
                    </form>
                </x-auth-card>
            </div>
        </div>
    </div>
</x-guest-layout>
