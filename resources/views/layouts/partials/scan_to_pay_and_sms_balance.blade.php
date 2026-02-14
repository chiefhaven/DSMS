@role(['superAdmin', 'financeAdmin'])
        <button type="button"
            class="btn btn-alt-secondary position-relative"
            title="Scan to Pay"
            onclick="window.location.href='/scan-to-pay'">

            <!-- Optional badge (example: pending scans) -->
            @if(isset($scanCount) && $scanCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-info"
                    style="font-size: 12px; padding: 0.3em 0.6em;">
                    {{ $scanCount }}
                </span>
            @endif

            <!-- QR Icon -->
            <i class="fa fa-fw fa-qrcode"></i>
        </button>

        @role('superAdmin')
            <button type="button"
                    class="btn btn-alt-secondary position-relative"
                    title="SMS Balance"
                    id="smsBalance"
                    onclick="fetchBalance"
                    v-cloak>

                <!-- Balance badge -->
                <span v-if="!loading && !error"
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                    :class="balance < 100 ? 'bg-danger' : 'bg-success'"
                    style="font-size: 12px; padding: 0.3em 0.6em;">
                    @{{ balance }}
                </span>

                <!-- Loading -->
                <span v-if="loading"
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-secondary"
                    style="font-size: 11px; padding: 0.25em 0.5em;">
                    ...
                </span>

                <!-- Error -->
                <span v-if="error"
                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                    style="font-size: 11px; padding: 0.25em 0.5em;">
                    !
                </span>

                <!-- SMS Icon -->
                <i class="fa fa-fw fa-sms"></i>
            </button>
        @endrole
@endrole

    <script>
        const smsBalance = createApp({
                setup() {
                    const balance = ref(null);
                    const loading = ref(true);
                    const error = ref(null);

                    const fetchBalance = async () => {
                        loading.value = true;
                        try {
                            const response = await axios.get("/api/sms-balance");
                            balance.value = response.data.balance;
                        } catch (err) {
                            error.value = "Something went wrong";
                            console.log(err.response?.data?.error || err.message)
                        } finally {
                            loading.value = false;
                        }
                    };

                    onMounted(() => {
                        fetchBalance();

                        // Optional: auto-refresh every 30 seconds
                        setInterval(fetchBalance, 30000);
                    });

                    return {
                        balance,
                        loading,
                        error,
                        fetchBalance
                    };
                }
        }).mount('#smsBalance');
    </script>
