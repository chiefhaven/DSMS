<div>
    <!-- Modal Background Overlay -->
    <div v-if="showAddClassRoomModal" class="modal-backdrop fade" :class="{ show: showAddClassRoomModal }"></div>

    <!-- Modal Dialog -->
    <div
    class="modal fade"
    :class="{ show: showAddClassRoomModal }"
    v-if="showAddClassRoomModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="gridSystemModalLabel"
    style="display: block;"
>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h3>@{{ state.modalTitle }}</h3>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <div class="mb-3 p-4">
                    <div class="box-body">
                        <form @submit.prevent="postClassRoom">
                            @csrf
                            <!-- ClassRoom Name Input -->
                            <div class="form-floating mb-4">
                                <input
                                    type="text"
                                    class="form-control"
                                    id="classRoom_name"
                                    name="classRoom_name"
                                    v-model="state.name"
                                    placeholder="ClassRoom name"
                                    required
                                />
                                <label for="classRoom_name">ClassRoom Name</label>
                            </div>

                            <!-- Description Input -->
                            <div class="form-floating mb-4">
                                <input
                                    class="form-control"
                                    id="classRoom_capacity"
                                    type="number"
                                    name="classRoom_capacity"
                                    v-model="state.capacity"
                                    placeholder="Capacity"
                                />
                                <label for="classRoom_capacity">Capacity</label>
                            </div>
                            <div class="form-floating mb-4">
                                <select
                                    class="form-control"
                                    id="classRoom_type"
                                    name="classRoom_type"
                                    v-model="state.classRoom_type"
                                    placeholder="ClassRoom type"
                                    required
                                >
                                    <option value="practical">Practical</option>
                                    <option value="theory">Theory</option>
                                </select>
                                <label for="classRoom_type">ClassRoom Type</label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" @click="postClassRoom()">
                    @{{ state.buttonName }}
                </button>
                <button type="button" class="btn btn-default" @click="closeForm">Cancel</button>
            </div>
        </div>
    </div>
</div>
</div>
