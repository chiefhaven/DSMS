<div class="row block px-3 py-4">
    <div class="block-content pt-3">
        <div class="row">
            <div class="col-md-6 col-xl-6 mb-4">
                <a href="/scan-to-pay" class="text-decoration-none" aria-label="Scan to pay for student expense">
                    <div class="block block-rounded block-link-shadow border p-4 hover-effect h-100">
                        <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                            <i class="fa fa-qrcode fa-4x text-primary"></i>
                            <div class="text-end ms-3">
                                <h5 class="fw-bold text-dark mb-1">Scan to Pay</h5>
                                <p class="mb-0">Pay for student expense</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-xl-6 mb-4" id="smsBalance" v-cloak>
                <div class="block block-rounded block-link-shadow border p-4 hover-effect h-100">
                    <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                        <i class="fa fa-envelope fa-4x text-success"></i>
                        <div class="text-end ms-3">
                            <h5 class="fw-bold text-dark mb-1">SMS Balance</h5>
                            <p class="mb-0">
                                <span v-if="loading">Checking...</span>
                                <span v-else-if="error" class="text-danger">@{{ error }}</span>
                                <span v-else>
                                    Balance:
                                    <span
                                        class="badge rounded-pill"
                                        :class="balance < 5000 ? 'bg-danger' : 'bg-success'"
                                    >
                                        K @{{ balance }}
                                    </span>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>