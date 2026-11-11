const account = ref({
    name: @json($accountUser?->name ?? ''),
    email: @json($accountUser?->email ?? ''),
    is_active: @json($accountUser?->is_active ?? true),
    password: '',
    password_confirmation: ''
});
const accountSaving = ref(false);
const accountErrors = ref({});
const accountMessage = ref('');
const accountError = ref('');
let savedAccount = { ...account.value };
const resetAccount = () => {
    if (accountSaving.value) return;
    account.value = { ...savedAccount };
    accountErrors.value = {};
    accountError.value = '';
    accountMessage.value = '';
};
const saveAccount = async () => {
    if (accountSaving.value) return;
    accountSaving.value = true;
    accountErrors.value = {};
    accountError.value = '';
    accountMessage.value = '';
    try {
        const { data } = await axios.put(@json($accountUpdateUrl), account.value);
        accountMessage.value = data.message;
        account.value.password = '';
        account.value.password_confirmation = '';
        savedAccount = { ...account.value };
    } catch (error) {
        accountErrors.value = error.response?.data?.errors ?? {};
        accountError.value = error.response?.status === 422
            ? 'Please correct the highlighted fields.'
            : 'Unable to update account. Please try again.';
    } finally {
        accountSaving.value = false;
    }
};
