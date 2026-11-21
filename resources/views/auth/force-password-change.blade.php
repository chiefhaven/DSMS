<x-guest-layout>
    <div class="split-layout">
        <div class="illustration-side">
            <div class="illustration-content">
                <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="200" cy="150" r="118" stroke="white" stroke-opacity=".18" stroke-width="2"/>
                    <circle cx="200" cy="150" r="92" stroke="white" stroke-opacity=".12" stroke-width="2"/>
                    <rect x="137" y="128" width="126" height="102" rx="18" stroke="white" stroke-width="4"/>
                    <path d="M163 128V102C163 67 182 48 200 48C218 48 237 67 237 102V128" stroke="white" stroke-width="4" stroke-linecap="round"/>
                    <circle cx="200" cy="174" r="10" fill="white"/>
                    <path d="M200 184V204" stroke="white" stroke-width="8" stroke-linecap="round"/>
                    <path d="M86 238H314" stroke="white" stroke-width="3" stroke-linecap="round" stroke-dasharray="12 12" opacity=".75"/>
                    <path d="M91 92H122M278 92H309M75 118H99M301 118H325" stroke="white" stroke-width="3" stroke-linecap="round" opacity=".5"/>
                </svg>
                <h2>{{ env('SCHOOL_NAME', 'Daron Driving School') }}</h2>
                <p>Secure your account before continuing to your dashboard.</p>
            </div>
        </div>

        <div class="form-side">
            <div class="form-container px-4 py-10">
                <div class="w-full bg-white px-6 py-8 sm:px-8">
                    <div class="mb-7 text-center">
                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full" style="background:#eef2ff;color:rgb(2,2,92);">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="4" y="10" width="16" height="11" rx="2"/>
                                <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                                <path d="M12 15v2"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold" style="color:rgb(2,2,92)">Set a new password</h1>
                        <p class="mt-2 text-sm text-gray-600">Your temporary password must be replaced before you can continue.</p>
                    </div>

                    <x-auth-validation-errors class="mb-4" :errors="$errors" />

                    <form method="POST" action="{{ route('students.password.force-change.update') }}">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-label for="password" :value="__('New password')" />
                            <x-input id="password" class="mt-1 block w-full" type="password" name="password" required autofocus autocomplete="new-password" />
                        </div>

                        <div class="mt-5">
                            <x-label for="password_confirmation" :value="__('Confirm new password')" />
                            <x-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                        </div>

                        <div class="mt-7">
                            <x-button class="w-full justify-center">
                                {{ __('Update password') }}
                            </x-button>
                        </div>
                    </form>

                    <div class="mt-5 text-center">
                        <button class="logout-button text-sm text-gray-500 underline hover:text-gray-800" type="submit" form="logout-form">
                            Log out
                        </button>
                    </div>

                    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                        @csrf
                    </form>
                </div>

                <p class="mt-5 text-center text-xs text-gray-500">
                    Powered by Mbira ERP Engine {{ config('version.tag') }}.
                    <br>
                    Supported by <a class="hover:underline" href="https://www.havenplustechnologies.co.mw">HavenPlus Technologies</a>
                </p>
            </div>
        </div>
    </div>
</x-guest-layout>
