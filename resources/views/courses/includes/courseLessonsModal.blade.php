<div>
    <!-- Modal Background Overlay -->
    <div v-if="courseLessonsModal" class="modal-backdrop fade" :class="{ show: courseLessonsModal }"></div>

    <!-- Modal Dialog -->
    <div class="modal fade"  :class="{ show: courseLessonsModal }" v-if="courseLessonsModal" tabindex="-1" role="dialog"  aria-labelledby="gridSystemModalLabel"  style="display: block;">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h3><b v-if="showActions"> Edit</b> @{{ course.name }} lessons</h3>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="row p-4" v-if="showActions">
                        <div class="col-md-12">
                                <input
                                    class="form-control"
                                    id="lesson"
                                    name="lesson"
                                    v-model="lessonName"
                                    @input="lessonSearch()"
                                    @blur="onLessonChange($event)"
                                    placeholder="Start typing to search lesson..."
                                    required
                                >
                        </div>
                    </div>

                    <table v-if="courseLessons.length > 0" class="table table-responsive">
                        <thead>
                            <th>
                                Name
                            </th>
                            <th>
                                Description
                            </th>
                            <th>
                                Type
                            </th>
                            <th>
                                Periods
                            </th>
                            <th>
                                Order
                            </th>
                            <th v-if="showActions">
                                Action
                            </th>
                        </thead>
                        <tr v-for="(lesson, index) in courseLessons" :key="index">
                            <td>@{{ lesson.name }}</td>
                            <td>@{{ lesson.description }}</td>
                            <td>@{{ lesson.type }}</td>
                            <td>
                                <div v-if="showActions">
                                    <input
                                        type="number"
                                        name="lesson_quantities[]"
                                        v-model="lesson_quantities[index]"
                                        min="1"
                                        class="form-control"
                                        @input="handleRowChanges(index)"
                                    />
                                </div>
                                <div v-else>
                                    @{{ lesson.pivot.lesson_quantity }}
                                </div>
                            </td>
                            <td>
                                <div v-if="showActions">
                                    <input
                                        type="number"
                                        name="lesson_orders[]"
                                        v-model="lesson_orders[index]"
                                        min="1"
                                        class="form-control"
                                        @input="handleRowChanges(index)"
                                        v-if="showActions"
                                    />
                                </div>
                                <div v-else>
                                    @{{ lesson.pivot.order }}
                                </div>
                            </td>
                            <td v-if="showActions">
                                <button class="btn btn-danger btn-small" @click="removeLesson(lesson.id)">Remove</button>
                            </td>
                        </tr>
                    </table>

                    <!-- Display message if no lessons are available -->
                    <p v-else class="text-center text-large">
                        <span class="fa fa-warning text-warning"></span> No lessons added yet
                    </p>

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="submit" v-if="showCourseLessons" class="btn btn-primary" @click="updateCourseLessons">
                        Edit
                    </button>
                    <button type="submit" v-if="showActions" class="btn btn-primary" @click="saveCourseLessons">
                        Save
                    </button>
                    <button type="button" v-if="showActions" class="btn btn-default" @click="back">
                        Back
                    </button>
                    <button type="button" v-if="showCourseLessons" class="btn btn-default" @click="closeForm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
