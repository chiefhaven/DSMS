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
                                    id="classRoom_description"
                                    type="text"
                                    name="classRoom_description"
                                    v-model="state.description"
                                    placeholder="Description"
                                />
                                <label for="classRoom_description">Description</label>
                            </div>

                            <!-- Location Input -->
                            <div class="form-floating mb-4">
                                <input
                                    class="form-control"
                                    id="classRoom_location"
                                    type="text"
                                    name="classRoom_location"
                                    v-model="state.location"
                                    placeholder="Location"
                                />
                                <label for="classRoom_location">Location</label>
                            </div>

                            <div class="form-floating mb-4">
                                <select
                                    class="form-control"
                                    id="instructor"
                                    name="instructor"
                                    v-model="instructor"
                                    placeholder="Location"
                                >
                                    <option
                                        v-for="instructor in instructors"
                                        :key="instructor.id"
                                        :value="instructor.id"
                                    >
                                        @{{ instructor.fname }} @{{ instructor.sname }}
                                    </option>
                                </select>

                                <label for="insturctor">Assign instructor</label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" @click="postClassRoom">
                    @{{ state.buttonName }}
                </button>
                <button type="button" class="btn btn-default" @click="closeForm">Cancel</button>
            </div>
        </div>
    </div>
</div>
</div>
