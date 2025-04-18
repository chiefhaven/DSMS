@role(['superAdmin', 'admin', 'instructor'])
<!-- Modal -->
<div v-if="showStatusChangeModal" class="modal-backdrop fade" :class="{ show: showStatusChangeModal }"></div>

<div class="modal fade" :class="{ show: showStatusChangeModal }" v-if="showStatusChangeModal" tabindex="-1" role="dialog" tabindex="-1" aria-labelledby="modal-block-vcenter" style="display: block;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change @{{ studentName }} status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="closeStatusChangeModal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" :value="studentId" id="selectedStudentId">
                <div class="mb-3">
                    <label for="statusSelect" class="form-label">Select Status</label>
                    <select v-model="studentStatus" class="form-select" id="studentStatus">
                        @role(['superAdmin', 'admin'])
                            <option>Pending</option>
                            <option>In progress</option>
                        @endrole
                        <option>Finished</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" @click="saveStatusChange">Save</button>
                <button type="button" class="btn btn-default" @click="closeStatusChangeModal">Cancel</button>
            </div>
        </div>
    </div>
</div>
@endrole