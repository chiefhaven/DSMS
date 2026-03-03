<style>
.split-layout {
    display: flex;
    min-height: 100vh;
    background: rgb(2, 2, 92);
}

.illustration-side {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: rgb(2, 2, 92);
    color: white;
}

.illustration-content {
    text-align: center;
    max-width: 500px;
}

.illustration-content svg {
    width: 100%;
    max-width: 400px;
    height: auto;
    margin-bottom: 2rem;
    filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
    animation: float 6s ease-in-out infinite;
}

.illustration-content h2 {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
}

.illustration-content p {
    font-size: 1.1rem;
    opacity: 0.9;
    line-height: 1.6;
}

.form-side {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background-color: #f3f4f6;
}

.form-container {
    width: 100%;
    max-width: 500px;
}

/* Card styling */
.form-container .bg-white {
    border-radius: 1rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    padding: 2rem;
}

/* Animation */
@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
}

/* Form styling */
.form-container input {
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.form-container input:focus {
    border-color: rgb(2, 2, 92);
    box-shadow: 0 0 0 3px rgba(2, 2, 92, 0.1);
}

.form-container button {
    background: rgb(2, 2, 92);
    border: none;
    border-radius: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.form-container button:hover {
    background: rgb(4, 4, 120);
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.form-container button:active {
    transform: translateY(0);
}

.form-container label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.25rem;
    display: block;
}

/* Logo styling */
.form-container .logo-container {
    text-align: center;
    margin-bottom: 1.5rem;
}

.form-container .logo-container a {
    display: inline-block;
}

.form-container .logo-container svg {
    width: 60px;
    height: 60px;
    color: rgb(2, 2, 92);
}

/* Responsive */
@media (max-width: 768px) {
    .split-layout {
        flex-direction: column;
    }

    .illustration-side {
        padding: 2rem 1rem;
    }

    .illustration-content svg {
        max-width: 200px;
    }

    .illustration-content h2 {
        font-size: 2rem;
    }

    .form-container {
        max-width: 100%;
    }
}
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

                    <!-- Cloud -->
                    <g opacity="0.3">
                        <circle cx="300" cy="50" r="15" stroke="white" stroke-width="1.5" fill="none"/>
                        <circle cx="315" cy="45" r="18" stroke="white" stroke-width="1.5" fill="none"/>
                        <circle cx="330" cy="50" r="15" stroke="white" stroke-width="1.5" fill="none"/>
                    </g>
                </svg>
                <h2>{{ env('SCHOOL_NAME', 'Driving School') }}</h2>
                <p>Reset your password and get back on the road.</p>
            </div>
        </div>

        <!-- Right side - Reset Password Form -->
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

                    <h1 style="font-size: 1.8em; text-align:center; color: rgb(2, 2, 92); margin-bottom: 1.5rem; font-weight: bold;">
                        Reset Password
                    </h1>

                    <!-- Validation Errors -->
                    <x-auth-validation-errors class="mb-4" :errors="$errors" />

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf

                        <!-- Password Reset Token -->
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <!-- Email Address -->
                        <div>
                            <x-label for="email" :value="__('Email')" />
                            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus />
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-label for="password" :value="__('New Password')" />
                            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                        </div>

                        <!-- Confirm Password -->
                        <div class="mt-4">
                            <x-label for="password_confirmation" :value="__('Confirm New Password')" />
                            <x-input id="password_confirmation" class="block mt-1 w-full"
                                                type="password"
                                                name="password_confirmation" required />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-button style="background: rgb(2, 2, 92);">
                                {{ __('Reset Password') }}
                            </x-button>
                        </div>
                    </form>
                </x-auth-card>
            </div>
        </div>
    </div>
</x-guest-layout>
