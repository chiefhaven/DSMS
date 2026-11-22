
{{-- Scan to Pay --}}
@can('record expense payments')
    <button type="button"
        class="btn btn-alt-secondary position-relative"
        title="Scan to Pay"
        onclick="window.location.href='/scan-to-pay'">

        @if(isset($scanCount) && $scanCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info"
                style="font-size: 12px; padding: 0.3em 0.6em;">
                {{ $scanCount }}
            </span>
        @endif

        <i class="fa fa-fw fa-qrcode"></i>
    </button>
@endcan


{{-- SMS Balance --}}
@can('view sms balance')
    <div id="smsBalance" class="d-inline-block">
        <button type="button"
            class="btn btn-alt-secondary position-relative"
            title="SMS Balance (Click to refresh)"
            @click="fetchBalance"
            :disabled="loading"
            v-cloak>

            {{-- Balance badge --}}
            <span v-if="!loading && !error"
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                :class="balance < 100 ? 'bg-danger' : 'bg-success'"
                style="font-size: 10px; padding: 0.3em 0.6em;">
                @{{ balance }} Units
            </span>

            {{-- Loading badge --}}
            <span v-if="loading"
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary"
                style="font-size: 11px; padding: 0.25em 0.5em;">
                ...
            </span>

            {{-- Error badge --}}
            <span v-if="error"
                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                style="font-size: 11px; padding: 0.25em 0.5em;"
                title="Unable to retrieve SMS balance">
                !
            </span>

            {{-- SMS Icon --}}
            <i class="fa fa-fw fa-sms"
                :class="{ 'fa-spin': loading }"></i>
        </button>
    </div>
@endcan


{{-- SMS Balance Vue Application --}}
@can('view sms balance')
<script>
    (() => {
        const smsBalance = createApp({
            setup() {
                const balance = ref(null);
                const loading = ref(false);
                const error = ref(false);

                let refreshInterval = null;

                const fetchBalance = async () => {
                    // Prevent overlapping requests
                    if (loading.value) return;

                    loading.value = true;
                    error.value = false;

                    try {
                        const response = await axios.get('/api/sms-balance');

                        const newBalance = Number(response.data.balance);

                        if (!Number.isFinite(newBalance)) {
                            throw new Error('Invalid SMS balance response');
                        }

                        balance.value = newBalance;

                    } catch (err) {
                        error.value = true;

                        console.error(
                            'SMS Balance Error:',
                            err.response?.data?.error || err.message
                        );

                    } finally {
                        loading.value = false;
                    }
                };

                onMounted(() => {
                    // Initial balance retrieval
                    fetchBalance();

                    // Automatically refresh every 30 seconds
                    refreshInterval = setInterval(fetchBalance, 30000);
                });

                onUnmounted(() => {
                    // Clean up refresh interval
                    if (refreshInterval) {
                        clearInterval(refreshInterval);
                    }
                });

                return {
                    balance,
                    loading,
                    error,
                    fetchBalance
                };
            }
        });

        smsBalance.mount('#smsBalance');
    })();
</script>
@endcan
