@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">
            Schedule lessons <span class="badge bg-danger ms-2">New</span>
        </h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="d-sm-inline-block">Action</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0">
                <div class="p-2">
                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#lessonScheduleModal">
                        Schedule Lesson
                    </button>
                </div>
            </div>
        </nav>
      </div>
    </div>
  </div>

<div class="content content-full" id="lessonSchedule">
    @include('components.alert')

    <div class="block block-rounded p-4">
        <div class="overflow-auto">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="lessonScheduleModal" tabindex="-1" aria-labelledby="lessonScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lessonScheduleModalLabel">@{{ selectedEvent ? 'Edit Lesson Schedule' : 'Create Lesson Schedule' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form @submit.prevent="submitForm">
                    @csrf
                    <!-- Student -->
                    <div class="form-group mb-3">
                        <label for="student_id" class="form-label">Search student</label>
                        <input name="student_id" id="student_id" v-model="student" class="form-control" @input="searchStudent()" required>
                    </div>

                    <!-- Lesson -->
                    <div class="form-group mb-3">
                        <label for="lesson_id" class="form-label">Lesson</label>
                        <select v-model="lessonId" id="lesson_id" class="form-control" required>
                            <option value="">Select Lesson</option>
                            <option v-for="lesson in lessons" :key="lesson.id" :value="lesson.id">
                                @{{ lesson.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Start Time -->
                    <div class="form-group mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="datetime-local" v-model="startTime" id="start_time" class="form-control" required />
                    </div>

                    <div class="form-group mb-3">
                        <label for="start_time" class="form-label text-success">Finish time will be @{{ formatDate(finishTime) }}</label>
                    </div>

                    {{--  <!-- Finish Time -->
                    <div class="form-group mb-3">
                        <label for="finish_time" class="form-label">Finish Time</label>
                        <input type="datetime-local" v-model="finishTime" id="finish_time" class="form-control" required />
                    </div>  --}}

                    <!-- Location -->
                    <div class="form-group mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" v-model="location" id="location" class="form-control" placeholder="Enter location" />
                    </div>

                    <!-- Comments -->
                    <div class="form-group mb-3">
                        <label for="comments" class="form-label">Comments</label>
                        <textarea v-model="comments" id="comments" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            @{{ selectedEvent ? "Update Schedule" : "Create Schedule" }}
                        </button>
                    </div>
                </form>
            </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="lessonsModal" tabindex="-1" aria-labelledby="lessonScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Scheduled Lessons</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div v-if="eventItems.length > 0" class="table-responsive">
                        <table class="table table striped">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Lesson</th>
                                    <th>Date/Time</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="event in eventItems" :key="event.id">
                                    <td>@{{ event.student.fname }} @{{ event.student.mname ?? '' }} @{{ event.student.sname }}</td>
                                    <td>@{{ event.lesson.name }}</td>
                                    <td>@{{ formatDate(event.start) }}</td>
                                    <td>
                                        <button class="btn btn-sm me-1" @click="editEvent(event)">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <button class="btn btn-sm" @click="deleteEvent(event.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else>
                        No schedules attendances this day!
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" @click="handleAddSchedule()">
                        Add Schedule
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    const { createApp, ref, onMounted, computed } = Vue;

    const lessonSchedule = createApp({
        setup() {
            const student = ref('');
            const lessonSchedule = ref(null);
            const studentId = ref("");
            const lessonId = ref("");
            const startTime = ref("");
            const location = ref("");
            const comments = ref("");
            const lessons = ref([]);
            let studentsData = [];
            let events = ref([]);
            const eventItems = ref([]);
            const selectedEvent = ref(null);
            const clickedDate = ref();

            // Define the fetchLessons function
            const fetchLessons = async (studentId) => {
                try {
                    const response = await axios.get(`/student-lessons/${studentId}`);
                    lessons.value = response.data;
                } catch (error) {
                    console.error("Error fetching lessons:", error);
                }
            };

            function formatDate(dateString) {
                const date = new Date(dateString);

                // Define month names
                const months = [
                    "January", "February", "March", "April", "May", "June",
                    "July", "August", "September", "October", "November", "December"
                ];

                const day = date.getDate();
                const month = months[date.getMonth()];
                const year = date.getFullYear();

                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');
                const seconds = String(date.getSeconds()).padStart(2, '0');

                return `${day} ${month}, ${year} ${hours}:${minutes}:${seconds}`;
            }

            const finishTime = computed(() => {
                if (startTime.value) {
                    // Parse startTime, add 30 minutes, and return the result in "YYYY-MM-DDTHH:mm" format
                    return moment(startTime.value, "YYYY-MM-DDTHH:mm")
                        .add(30, "minutes")
                        .format("YYYY-MM-DDTHH:mm");
                }
                return ""; // Default value if startTime is not set
            });

            // Handle form submission
            const submitForm = async () => {
                const payload = {
                    student_id: studentId.value,
                    lesson_id: lessonId.value,
                    start_time: startTime.value,
                    finish_time: finishTime.value,
                    location: location.value,
                    comments: comments.value,
                    lessonScheduleId: lessonSchedule.value.id
                };

                try {
                    if (lessonSchedule.value && lessonSchedule.value.id) {
                        await axios.put(`/update-lesson-schedule/${lessonSchedule.value.id}`, payload);
                    } else {
                        await axios.post("/store-lesson-schedule", payload);
                    }

                    notification("Lesson schedule saved successfully!", "success");
                    eventItems.value = eventItems.value.filter(event => event.id !== eventId);
                    events.value = events.value.filter(event => event.id !== eventId);

                    calendarInitialization();
                } catch (error) {
                    notification(error.response?.data?.message || "An error occurred!", "error");
                } finally {

                    clearForm();

                    // Get existing modal instance
                    const lessonsModalEl = document.getElementById('lessonScheduleModal');
                    const lessonsModal = bootstrap.Modal.getInstance(lessonsModalEl);

                    if (lessonsModal) {
                        lessonsModal.hide(); // Properly hide the modal
                    }
                }

            };

            const clearForm = () => {
                studentId.value = '';
                student.value = '';
                lessonId.value = '';
                startTime.value = '';
                location.value = '';
                comments.value = '';
                lessonSchedule.value = {};
            };

            const searchStudent = () => {
                const path = '/attendance-student-search';

                $('#student_id').typeahead({
                    source: function (query, process) {
                        return $.get(path, { search: query }, function (data) {
                            studentsData = data; // Store globally
                            return process(data.map(student => student.full_name));
                        });
                    },
                    updater: function (selectedName) {
                        // Find the selected student
                        const selectedStudent = studentsData.find(student => student.full_name === selectedName);

                        if (selectedStudent) {
                            studentId.value = selectedStudent.id;
                            student.value = selectedStudent.full_name;
                            fetchLessons(selectedStudent.id);
                        }
                        return selectedName;
                    }
                });
            };

            const handleDateClick = (info) => {

                
                clickedDate.value = moment(info.date).format("YYYY-MM-DD");

                // Check if events exist for the clicked date
                const eventsOnClickedDate = events.value.filter(event => moment(event.start).format('YYYY-MM-DD') === clickedDate.value);

                if (eventsOnClickedDate.length > 0) {
                    // If events exist, show the list of events
                    showEventList(eventsOnClickedDate);
                } else {
                    // If no events, show the modal
                    startTime.value = moment(clickedDate.value).format("YYYY-MM-DDTHH:mm");
                    const modal = new bootstrap.Modal(document.getElementById('lessonScheduleModal'));
                    modal.show();
                }
            };

            const handleAddSchedule = () => {

                    // If no events, show the modal
                    startTime.value = moment(clickedDate.value).format("YYYY-MM-DDTHH:mm");

                    // Get existing modal instance
                    const lessonsModalEl = document.getElementById('lessonsModal');
                    const lessonsModal = bootstrap.Modal.getInstance(lessonsModalEl);

                    if (lessonsModal) {
                        lessonsModal.hide(); // Properly hide the modal
                    }

                    const modal = new bootstrap.Modal(document.getElementById('lessonScheduleModal'));
                    modal.show();

            };

            // Function to display the event list for the clicked date
            const showEventList = (events) => {

                eventItems.value = events;

                // Display the event list container
                const modal = new bootstrap.Modal(document.getElementById('lessonsModal'));
                modal.show();
            };

            onMounted(() => {
                events.value = @json($events) || [];

                calendarInitialization()

            });

            const calendarInitialization = () => {
                // Initialize FullCalendar
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    slotMinTime: '06:00:00', // Start time
                    slotMaxTime: '18:30:00', // End time
                    slotDuration: '00:30:00',
                    slotLabelInterval: '00:30:00',
                    allDaySlot: false,
                    nowIndicator: true,
                    dateClick: handleDateClick,
                    events: events.value,
                });
                calendar.render();
            }

            const notification = ($text, $icon) =>{
                Swal.fire({
                    toast: true,
                    position: "top-end",
                    html: $text,
                    showConfirmButton: false,
                    timer: 5500,
                    timerProgressBar: true,
                    icon: $icon,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                      }
                  });
            }

            const editEvent = (event) => {
                NProgress.start();
                selectedEvent.value = { ...event };
                student.value = `${event.student.fname} ${event.student.mname} ${event.student.sname}`;
                studentId.value = event.student.id;
                lessonId.value = event.lesson.id;
                comments.value = event.comments;
                startTime.value = moment(event.start).format("YYYY-MM-DDTHH:mm");
                lessonSchedule.value = event;
                fetchLessons(studentId.value);

                // Get existing modal instance
                const lessonsModalEl = document.getElementById('lessonsModal');
                const lessonsModal = bootstrap.Modal.getInstance(lessonsModalEl);

                if (lessonsModal) {
                    lessonsModal.hide(); // Properly hide the modal
                }

                // Show the modal for editing
                const modal = new bootstrap.Modal(document.getElementById('lessonScheduleModal'));
                modal.show();

                NProgress.done();
            };


            const deleteEvent = async (eventId) => {
                // Confirm deletion
                if (confirm("Are you sure you want to delete this lesson?")) {
                    try {

                        NProgress.start();

                        // Send DELETE request to server
                        const response = await axios.delete(`/schedule-lesson/${eventId}`);

                        eventItems.value = eventItems.value.filter(event => event.id !== eventId);
                        events.value = events.value.filter(event => event.id !== eventId);

                        calendarInitialization()

                        notification("Lesson deleted successfully!", "success");

                    } catch (error) {
                        notification("Failed to delete the lesson. Please try again.", "error");
                    } finally{
                        NProgress.done();
                    }
                }
            };


            return {
                student,
                lessons,
                lessonId,
                startTime,
                finishTime,
                location,
                comments,
                searchStudent,
                submitForm,
                lessonSchedule,
                studentId,
                eventItems,
                formatDate,
                deleteEvent,
                editEvent,
                selectedEvent,
                handleDateClick,
                handleAddSchedule,

            };
        }
    });

    // Mount the Vue app to the DOM element
    lessonSchedule.mount('#lessonSchedule');
</script>
@endsection
