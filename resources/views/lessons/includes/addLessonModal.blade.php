<div>
    <!-- Modal Background Overlay -->
    <div v-if="showAddLessonModal" class="modal-backdrop fade" :class="{ show: showAddLessonModal }"></div>

    <!-- Modal Dialog -->
    <div
    class="modal fade"
    :class="{ show: showAddLessonModal }"
    v-if="showAddLessonModal"
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
                        <form @submit.prevent="postLesson">
                            @csrf
                            <!-- Lesson Name Input -->
                            <div class="form-floating mb-4">
                                <input
                                    type="text"
                                    class="form-control"
                                    id="lesson_name"
                                    name="lesson_name"
                                    v-model="state.name"
                                    placeholder="Lesson name"
                                    required
                                />
                                <label for="lesson_name">Lesson Name</label>
                            </div>

                            <!-- Description Input -->
                            <div class="form-floating mb-4">
                                <textarea
                                    class="form-control"
                                    id="lesson_description"
                                    name="lesson_description"
                                    v-model="state.description"
                                    style="height: 150px"
                                    placeholder="Description"
                                ></textarea>
                                <label for="lesson_description">Description</label>
                            </div>
                            <div class="form-floating mb-4">
                                <select
                                    class="form-control"
                                    id="lesson_type"
                                    name="lesson_type"
                                    v-model="state.lesson_type"
                                    placeholder="Lesson type"
                                    required
                                >
                                    <option value="practical">Practical</option>
                                    <option value="theory">Theory</option>
                                </select>
                                <label for="lesson_type">Lesson Type</label>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" @click="postLesson()">
                    @{{ state.buttonName }}
                </button>
                <button type="button" class="btn btn-default" @click="closeForm">Cancel</button>
            </div>
        </div>
    </div>
</div>
</div>
