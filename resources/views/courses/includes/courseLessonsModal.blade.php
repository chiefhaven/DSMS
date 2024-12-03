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
                    <div class="row" v-if="showActions">
                        <div class="col-md-12 p-4">
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
                                Times
                            </th>
                            <th>
                                Sequence
                            </th>
                            <th v-if="showActions">
                                Action
                            </th>
                        </thead>
                        <tr v-for="lesson in courseLessons" :key="lesson.id">
                            <td>@{{ lesson.name }}</td>
                            <td>@{{ lesson.description }}</td>
                            <td>@{{ lesson.type }}</td>
                            <td>@{{ lesson.pivot.lesson_quantity }}</td>
                            <td>@{{ lesson.order }}</td>
                            <td v-if="showActions">
                                <button class="btn btn-danger btn-small" @click="removeLessonFromcourseLessons(lesson.id)">Remove</button>
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
