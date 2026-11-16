<div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card student-profile-card bg-body-light p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h3 class="fs-6 fw-semibold mb-1">Student portal account</h3>
                        <p class="text-muted small mb-0">Login details and account status.</p>
                    </div>
                    @if ($student->user)
                        <span class="badge {{ $student->user->is_active ? 'bg-success' : 'bg-secondary' }}">
                            {{ $student->user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    @endif
                </div>

                @if ($student->user)
                    <dl class="row g-2 mb-0">
                        <dt class="col-sm-4 text-muted fw-normal">Username</dt>
                        <dd class="col-sm-8 mb-1">{{ $student->user->name ?? 'Not set' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Login email</dt>
                        <dd class="col-sm-8 mb-1">{{ $student->user->email ?? 'Not set' }}</dd>

                        <dt class="col-sm-4 text-muted fw-normal">Email verification</dt>
                        <dd class="col-sm-8 mb-0">
                            {{ $student->user->email_verified_at ? 'Verified' : 'Not verified' }}
                        </dd>
                    </dl>

                    @can('manage student accounts')
                        <hr class="my-3">
                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('students.account.password-reset-link', $student) }}" @submit.prevent="submitStudentAccountAction($event, 'reset-link')">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary" type="submit" :disabled="studentAccountAction.loading">
                                    <span v-if="studentAccountAction.loading && studentAccountAction.action === 'reset-link'">Sending...</span>
                                    <span v-else>Send password reset link</span>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('students.account.temporary-password', $student) }}" @submit.prevent="submitStudentAccountAction($event, 'temporary-password')">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning" type="submit" :disabled="studentAccountAction.loading">
                                    <span v-if="studentAccountAction.loading && studentAccountAction.action === 'temporary-password'">Generating...</span>
                                    <span v-else>Generate temporary password</span>
                                </button>
                            </form>
                        </div>
                    @endcan
                @else
                    <p class="text-muted small mb-0">No student portal account is linked.</p>
                @endif
            </div>
        </div>
    </div>
</div>
