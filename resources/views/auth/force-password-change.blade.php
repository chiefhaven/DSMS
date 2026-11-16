<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-md rounded-lg bg-white p-8 shadow">
            <h1 class="mb-2 text-xl font-semibold text-gray-900">Set a new password</h1>
            <p class="mb-6 text-sm text-gray-600">Your temporary password must be replaced before you can continue.</p>

            <x-auth-validation-errors class="mb-4" :errors="$errors" />

            <form method="POST" action="{{ route('students.password.force-change.update') }}">
                @csrf
                @method('PUT')

                <div>
                    <x-label for="password" :value="__('New password')" />
                    <x-input id="password" class="mt-1 block w-full" type="password" name="password" required autofocus autocomplete="new-password" />
                </div>

                <div class="mt-4">
                    <x-label for="password_confirmation" :value="__('Confirm new password')" />
                    <x-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <button class="text-sm text-gray-600 underline" type="submit" form="logout-form">Log out</button>
                    <x-button>{{ __('Update password') }}</x-button>
                </div>
            </form>

            <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
                @csrf
            </form>
        </div>
    </div>
</x-guest-layout>
