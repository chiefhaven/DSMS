<!-- Vue Mount Point -->
<div id="announcement">

    <div class="modal fade" id="announcement-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">

                <!-- Header -->
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-bullhorn me-2"></i> Send Announcement
                    </h5>
                    <button type="button"
                            class="btn-close btn-close-white"
                            @click="cancelAnnouncement"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <form ref="announcementForm" @submit.prevent="sendAnnouncement">
                        @csrf

                        <!-- Student Group -->
                        <div class="form-floating mb-3">
                            <select class="form-select"
                                    v-model="state.group"
                                    @change="getBalanceTemplate">
                                <option value="All students">All students</option>
                                <option value="Students with balance">Students with balance</option>
                            </select>
                            <label>Student Group</label>
                        </div>

                        <!-- Instructor -->
                        <div class="form-floating mb-3">
                            <select class="form-select"
                                    v-model="state.selectedInstructor"
                                    @change="getInstructors">
                                <option value="">All instructors</option>
                                <option v-for="instructor in state.instructors"
                                        :key="instructor.id"
                                        :value="instructor.id">
                                    @{{ instructor.name }}
                                </option>
                            </select>
                            <label>Instructor</label>
                        </div>

                        <!-- Tags -->
                        <p class="small text-muted">
                            Available tags:
                            <code>{FIRST_NAME}</code>
                            <code>{SIR_NAME}</code>
                            <code>{BALANCE}</code>
                            <code>{FEES_PAID}</code>
                            <code>{FEES_TOTAL}</code>
                            <code>{INVOICE_NUMBER}</code>
                        </p>

                        <!-- Message -->
                        <div class="form-floating mb-3">
                            <textarea class="form-control"
                                      style="height:120px"
                                      v-model="state.body"
                                      required></textarea>
                            <label>Message Body</label>
                        </div>

                        <!-- Buttons -->
                        <x-form-actions modal cancel-click="cancelAnnouncement">
                            <button type="submit"
                                    class="btn btn-primary rounded-pill px-4"
                                    :disabled="state.isSubmitButtonDisabled">
                                <span v-if="state.isLoading">Processing...</span>
                                <span v-else>@{{ state.buttonText }}</span>
                            </button>

                        </x-form-actions>
                    </form>
                </div>

            </div>
        </div>
    </div>

</div>


<script>
    const app = Vue.createApp({
        setup() {

            const state = Vue.ref({
                isSubmitButtonDisabled: false,
                isLoading: false,
                buttonText: 'Send Announcement',
                body: '',
                group: 'All students',
                instructors: [],
                selectedInstructor: ''
            });

            const announcementForm = Vue.ref(null);

            const loadInstructorsOnModalOpen = () => {
                const modalEl = document.getElementById('announcement-modal');

                modalEl.addEventListener('shown.bs.modal', () => {
                    if (state.value.instructors.length === 0) {
                        getInstructors();
                    }
                });
            };

            const getInstructors = async () => {
                try {
                    const response = await axios.get('/api/fetch-instructors');
                    state.value.instructors = response.data;
                } catch {
                    notification('Failed to load instructors', 'error');
                }
            };

            const cancelAnnouncement = () => {
                resetState();

                const modalEl = document.getElementById('announcement-modal');
                const modal =
                    bootstrap.Modal.getInstance(modalEl) ||
                    new bootstrap.Modal(modalEl);

                modal.hide();
            };

            const resetState = () => {
                state.value.body = '';
                state.value.group = '';
                state.value.selectedInstructor = '';
                state.value.isLoading = false;
                state.value.isSubmitButtonDisabled = false;
                state.value.buttonText = 'Send Announcement';
            };

            const sendAnnouncement = async () => {
                state.value.isSubmitButtonDisabled = true;
                state.value.isLoading = true;
                state.value.buttonText = 'Processing...';

                try {
                    await axios.post('/api/sendAnnouncement', {
                        body: state.value.body,
                        group: state.value.group,
                        instructor: state.value.selectedInstructor
                    });

                    notification('Announcement sent successfully', 'success');
                    cancelAnnouncement();

                } catch {
                    notification('Announcement not sent', 'error');
                    resetState();
                }
            };

            const getBalanceTemplate = async () => {
                if (state.value.group === 'Students with balance') {
                    try {
                        const response = await axios.post('/get-balance-template');
                        state.value.body = response.data;
                    } catch {
                        notification('Failed to load balance template', 'error');
                    }
                } else {
                    state.value.body = '';
                }
            };

            const selectInstructor = (event) => {
                const instructorId = event.currentTarget.getAttribute('data-instructor-id');
                state.value.instructor = instructorId;

                getInstructors();
            };

            const notification = (text, icon) => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon,
                    text,
                    showConfirmButton: false,
                    timer: 5000
                });
            };


            onMounted(() => {
                // Listen for clicks on all links that open the announcement modal
                document.querySelectorAll('[data-bs-target="#announcement-modal"]').forEach(link => {
                    link.addEventListener('click', (e) => {
                        const instructorId = e.currentTarget.getAttribute('data-instructor-id');
                        state.value.selectedInstructor = instructorId;

                    loadInstructorsOnModalOpen();

                });
            });
            });

            return {
                state,
                announcementForm,
                sendAnnouncement,
                cancelAnnouncement,
                getBalanceTemplate,
                selectInstructor
            };
        }
    });

    app.mount('#announcement');
</script>
