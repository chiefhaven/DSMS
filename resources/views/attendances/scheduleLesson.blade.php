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
            <!-- Modal Button to Trigger the Form -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#lessonScheduleModal">
                Schedule Lesson
            </button>
        </nav>
      </div>
    </div>
  </div>

<div class="content content-full" id="lessonSchedule">
    @include('components.alert')

    <div class="block block-rounded">
        <div class="p-4">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="lessonScheduleModal" tabindex="-1" aria-labelledby="lessonScheduleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="lessonScheduleModalLabel">{{ isset($lessonSchedule) ? 'Edit Lesson Schedule' : 'Create Lesson Schedule' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <form @submit.prevent="submitForm">
                @csrf
                <!-- Student -->
                <div class="form-group mb-3">
                    <label for="student_id" class="form-label">Search student</label>
                    <input name="student_id" id="student_id" class="form-control" @input="searchStudent()" required>
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

                <!-- Finish Time -->
                <div class="form-group mb-3">
                    <label for="finish_time" class="form-label">Finish Time</label>
                    <input type="datetime-local" v-model="finishTime" id="finish_time" class="form-control" required />
                </div>

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
                        @{{ lessonSchedule ? "Update Schedule" : "Create Schedule" }}
                    </button>
                </div>
            </form>
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

            // Define the fetchLessons function
            const fetchLessons = async (studentId) => {
                try {
                    const response = await axios.get(`/student-lessons/${studentId}`);
                    lessons.value = response.data;
                    console.log(lessons.value);
                } catch (error) {
                    console.error("Error fetching lessons:", error);
                }
            };

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
                };

                console.log(payload);

                try {
                    if (lessonSchedule.value && lessonSchedule.value.id) {
                        await axios.put(`/lesson-schedules/${lessonSchedule.value.id}`, payload);
                    } else {
                        await axios.post("/store-lesson-schedule", payload);
                    }

                    notification("Lesson schedule saved successfully!", "success");
                    window.location.reload();
                } catch (error) {
                    notification(error.response?.data?.message || "An error occurred!", "error");
                } finally {

                }

            };

            const searchStudent = () => {
                const path = '/attendance-student-search';

                $('#student_id').typeahead({
                    source: function (query, process) {
                        return $.get(path, { search: query }, function (data) {
                            studentsData = data; // Store data globally
                            return process(data.map(student => student.full_name));
                        });
                    },
                    updater: function (selectedName) {
                        // Find the selected student from the fetched data
                        const selectedStudent = studentsData.find(student => student.full_name === selectedName);

                        if (selectedStudent) {
                            studentId.value = selectedStudent.id;
                            fetchLessons(selectedStudent.id);
                        }
                        return selectedName;
                    }
                });
            };

            const handleDateClick = (info) => {
                // Log the clicked date
                console.log("Date clicked:", info);

                // Get the clicked date (you can use it to set values in the form)
                const clickedDate = info.dateStr;

                // Set the start time field to the clicked date
                startTime.value = clickedDate; // Assuming you want to set the start time to the clicked date

                // Open the modal programmatically
                const modal = new bootstrap.Modal(document.getElementById('lessonScheduleModal'));
                modal.show();


            };

              onMounted(() => {
                // Check if events are being passed properly
                const events = @json($events) || [];
                console.log('Events:', events);
                // Initialize FullCalendar
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    slotMinTime: '00:00:00', // Start time
                    slotMaxTime: '23:59:00', // End time
                    slotDuration: '00:30:00',
                    slotLabelInterval: '00:30:00',
                    allDaySlot: false,
                    nowIndicator: true,
                    dateClick: handleDateClick, // Use the handleDateClick method
                    events: events, // Pass the validated events
                });
                calendar.render();
            });

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
            };
        }
    });

    // Mount the Vue app to the DOM element
    lessonSchedule.mount('#lessonSchedule');
</script>
@endsection
