@php
    $bankDirectory = config('bank_directory', []);
    $selectedBank = old('bank_name', $employee?->bank_name);
    $selectedBranch = old('bank_branch', $employee?->bank_branch);
@endphp

<div class="mt-4">
    <h4 class="fs-6 fw-semibold mb-3">Bank account details</h4>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="form-floating">
                <select class="form-select @error('bank_name') is-invalid @enderror" id="bank_name" name="bank_name"
                        onchange="filterBankBranchOptions(true)">
                    <option value="">Select bank</option>
                    @foreach ($bankDirectory as $bankName => $bankData)
                        <option value="{{ $bankName }}" {{ (string) $selectedBank === (string) $bankName ? 'selected' : '' }}>
                            {{ $bankName }}
                        </option>
                    @endforeach
                </select>
                <label for="bank_name">Bank name</label>
                @error('bank_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <select class="form-select @error('bank_branch') is-invalid @enderror" id="bank_branch" name="bank_branch">
                    <option value="">Select branch</option>
                    @foreach ($bankDirectory as $bankName => $bankData)
                        @foreach (($bankData['branches'] ?? []) as $branchName => $branchSortCode)
                            <option value="{{ $branchName }}" data-bank="{{ $bankName }}"
                                    {{ (string) $selectedBranch === (string) $branchName && (string) $selectedBank === (string) $bankName ? 'selected' : '' }}>
                                {{ $branchName }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
                <label for="bank_branch">Branch</label>
                @error('bank_branch')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <input type="text" class="form-control @error('bank_account_name') is-invalid @enderror"
                       id="bank_account_name" name="bank_account_name"
                       value="{{ old('bank_account_name', $employee?->bank_account_name) }}"
                       maxlength="255" placeholder="Account name">
                <label for="bank_account_name">Account name</label>
                @error('bank_account_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <input type="text" inputmode="numeric" autocomplete="off"
                       class="form-control @error('bank_account_number') is-invalid @enderror"
                       id="bank_account_number" name="bank_account_number"
                       value="{{ old('bank_account_number', $employee?->bank_account_number) }}"
                       maxlength="100" placeholder="Account number">
                <label for="bank_account_number">Account number</label>
                @error('bank_account_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-floating">
                <input type="text" class="form-control @error('bank_account_type') is-invalid @enderror"
                       id="bank_account_type" name="bank_account_type"
                       value="{{ old('bank_account_type', $employee?->bank_account_type) }}"
                       maxlength="100" placeholder="Account type">
                <label for="bank_account_type">Account type</label>
                @error('bank_account_type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<script>
    function filterBankBranchOptions(resetInvalidSelection) {
        const bankSelect = document.getElementById('bank_name');
        const branchSelect = document.getElementById('bank_branch');
        if (!bankSelect || !branchSelect) return;
        const selectedOption = branchSelect.options[branchSelect.selectedIndex];

        if (resetInvalidSelection && selectedOption && selectedOption.value
            && selectedOption.dataset.bank !== bankSelect.value) {
            branchSelect.value = '';
        }

        Array.from(branchSelect.options).forEach((option) => {
            if (!option.value) return;
            option.hidden = option.dataset.bank !== bankSelect.value;
        });
    }

    filterBankBranchOptions(false);
</script>
