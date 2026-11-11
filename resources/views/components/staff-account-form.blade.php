@props(['user', 'profileLabel'])

@if ($user)
    <div v-if="accountMessage" class="alert alert-success" role="status">@{{ accountMessage }}</div>
    <div v-if="accountError" class="alert alert-danger" role="alert">@{{ accountError }}</div>
    <form @submit.prevent="saveAccount">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="account-name" class="form-label">Username</label>
                <input id="account-name" type="text" class="form-control" v-model="account.name" required maxlength="255" autocomplete="off">
                <div v-if="accountErrors.name" class="text-danger fs-sm">@{{ accountErrors.name[0] }}</div>
            </div>
            <div class="col-md-6">
                <label for="account-email" class="form-label">Email</label>
                <input id="account-email" type="email" class="form-control" v-model="account.email" required maxlength="255" autocomplete="off">
                <div v-if="accountErrors.email" class="text-danger fs-sm">@{{ accountErrors.email[0] }}</div>
            </div>
            <div class="col-md-6">
                <label for="account-password" class="form-label">New password</label>
                <input id="account-password" type="password" class="form-control" v-model="account.password" minlength="8" autocomplete="new-password">
                <div class="form-text">Leave blank to keep the current password. Minimum 8 characters.</div>
                <div v-if="accountErrors.password" class="text-danger fs-sm">@{{ accountErrors.password[0] }}</div>
            </div>
            <div class="col-md-6">
                <label for="account-password-confirmation" class="form-label">Confirm new password</label>
                <input id="account-password-confirmation" type="password" class="form-control" v-model="account.password_confirmation" minlength="8" autocomplete="new-password">
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input id="account-active" type="checkbox" class="form-check-input" v-model="account.is_active">
                    <label for="account-active" class="form-check-label">Login enabled</label>
                </div>
                <div class="form-text">Disabling login blocks web and API access. The {{ $profileLabel }} profile{{ $profileLabel === 'instructor' ? ' and assignments are' : ' is' }} retained.</div>
                <div v-if="accountErrors.is_active" class="text-danger fs-sm">@{{ accountErrors.is_active[0] }}</div>
            </div>
        </div>
        <x-form-actions cancel-click="resetAccount">
            <button type="submit" class="btn btn-primary rounded-pill px-4" :disabled="accountSaving">
                <span v-if="accountSaving" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                @{{ accountSaving ? 'Saving...' : 'Save account' }}
            </button>
        </x-form-actions>
    </form>
@else
    <div class="alert alert-warning mb-0" role="alert">This {{ $profileLabel }} has no linked user account.</div>
@endif
