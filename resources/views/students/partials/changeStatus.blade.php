@role(['superAdmin', 'admin', 'instructor'])
    <div class="modal" id="change-status" tabindex="-1" aria-labelledby="modal-block-vcenter" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-themed block-transparent mb-0">
            <div class="block-header bg-primary-dark">
                <h3 class="block-title">Change students status</h3>
                <div class="block-options">
                    <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-fw fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="block-content">
                <form class="mb-5" action="{{ url('/updateStudentStatus', $student->id) }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                    @csrf
                    <div class="row">
                        <div class="col-sm-12 mb-4">
                            <label for="invoice_discount">Date</label>
                            <select class="form-select dropdown-toggle" id="status" name="status">
                                @role(['superAdmin'])
                                    <option value="Pending" {{ $student->status == 'Pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="In progress" {{ $student->status == 'In progress' ? 'selected' : '' }}>In progress</option>
                                @endrole
                                <option value="Finished" {{ $student->status == 'Finished' ? 'selected' : '' }}>Finished</option>
                            </select>
                        </div>
                        <div class="block-content block-content-full text-end bg-body">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        </div>
        </div>
    </div>
@endrole