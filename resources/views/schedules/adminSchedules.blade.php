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

            {{--  <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="d-sm-inline-block">Action</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0">
                <div class="p-2">
                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#lessonScheduleModal">
                        Schedule Lesson
                    </button>
                </div>
            </div>  --}}
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
                    {{--  <div class="form-group mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" v-model="location" id="location" class="form-control" placeholder="Enter location" />
                    </div>  --}}
                    <!-- Location -->
                    <div class="form-group mb-3">
                    <label class="form-label">Location</label>
                    <select v-model="location" id="location" class="form-select">
                        <option value="">Select location</option>
                        <option>Area 4</option>
                        <option>Area 49</option>
                        <option>City center</option>
                        <option>Students Home</option>
                        <option>Other</option>
                    </select>
                    </div>

                    <!-- Comments -->
                    <div class="form-group mb-3">
                        <label for="comments" class="form-label">Comments</label>
                        <textarea v-model="comments" id="comments" class="form-control" rows="3" placeholder="Additional notes..."></textarea>
                    </div>

                    <div class="form-group">
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="isSubmitting"
                        >
                            <span v-if="isSubmitting">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                @{{ selectedEvent ? "Updating..." : "Creating..." }}
                            </span>
                            <span v-else>
                                @{{ selectedEvent ? "Update Schedule" : "Create Schedule" }}
                            </span>
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
                                    <th style="min-width: 100px;">Action</th>
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
    const { createApp, ref, onMounted, computed, watch } = Vue;

    const lessonSchedule = createApp({
      setup() {
        // Reactive state
        const student = ref('');
        const studentId = ref("");
        const lessonId = ref("");
        const startTime = ref("");
        const location = ref("");
        const comments = ref("");
        const lessons = ref([]);
        const studentsData = ref([]);
        const events = ref([]);
        const eventItems = ref([]);
        const selectedEvent = ref(null);
        const clickedDate = ref("");
        const calendarInstance = ref(null);
        const isSubmitting = ref(false);

        // Computed properties
        const finishTime = computed(() => {
          if (!startTime.value) return "";
          return moment(startTime.value, "YYYY-MM-DDTHH:mm")
                 .add(30, "minutes")
                 .format("YYYY-MM-DDTHH:mm");
        });

        // Methods
        const fetchLessons = async (studentId) => {
          try {
            const response = await axios.get(`/student-lessons/${studentId}`);
            lessons.value = response.data;
            if (lessons.value.length === 0) {
              notification('No lessons available for this student', 'error');
            }
          } catch (error) {
            console.error("Error fetching lessons:", error);
            notification('Failed to fetch lessons', 'error');
          }
        };

        const formatDate = (dateString) => {
          const date = new Date(dateString);
          return date.toLocaleString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
          });
        };

        const validateForm = () => {
          if (!studentId.value || !lessonId.value || !startTime.value) {
            notification("Please fill in all required fields!", "error");
            return false;
          }

          if (new Date(finishTime.value) <= new Date(startTime.value)) {
            notification("Finish time must be after start time!", "error");
            return false;
          }

          return true;
        };

        const submitForm = async () => {
          if (!validateForm()) return;

          isSubmitting.value = true;
          NProgress.start();

          const payload = {
            student_id: studentId.value,
            lesson_id: lessonId.value,
            start_time: startTime.value,
            finish_time: finishTime.value,
            location: location.value,
            comments: comments.value,
            lessonScheduleId: selectedEvent.value?.id ?? null,
          };

          try {
            const endpoint = selectedEvent.value?.id
              ? `/update-lesson-schedule/${selectedEvent.value.id}`
              : "/store-lesson-schedule";

            const method = selectedEvent.value?.id ? "put" : "post";
            const response = await axios[method](endpoint, payload);

            notification(response.data.message || "Lesson schedule saved successfully!", "success");
            await fetchSchedules();
            closeModal('lessonScheduleModal');
            clearForm();
          } catch (error) {
            handleApiError(error);
          } finally {
            NProgress.done();
            isSubmitting.value = false;
          }
        };

        const handleApiError = (error) => {
          const errorMessage = error.response?.data?.message ||
                             error.message ||
                             "Failed to save lesson schedule. Please try again.";
          notification(errorMessage, "error");
          console.error("API Error:", {
            status: error.response?.status,
            data: error.response?.data,
            stack: error.stack,
          });
        };

        const clearForm = () => {
          student.value = '';
          studentId.value = '';
          lessonId.value = '';
          startTime.value = '';
          location.value = '';
          comments.value = '';
          selectedEvent.value = null;
        };

        const searchStudent = () => {
          $('#student_id').typeahead({
            source: (query, process) => {
              return $.get('/attendance-student-search', { search: query }, (data) => {

                if(data.length > 0){
                    studentsData.value = data;
                }else{
                    notification('Student not found, please rephrase', 'error')
                }

                return process(data.map(student => student.text));
              });
            },
            updater: (selectedName) => {
              const selectedStudent = studentsData.value.find(s => s.text === selectedName);
              if (selectedStudent) {
                studentId.value = selectedStudent.id;
                student.value = selectedStudent.text;
                fetchLessons(selectedStudent.id);
              }
              return selectedName;
            }
          });
        };

        const handleDateClick = (info) => {
            const clickedMoment = moment(info.date);
            const today = moment().startOf('day');
            clickedDate.value = clickedMoment;

            // Format for comparison
            const clickedDateStr = clickedMoment.format("YYYY-MM-DD");

            // Check for events on this date
            const hasEvents = events.value.some(event =>
              moment(event.start).format('YYYY-MM-DD') === clickedDateStr
            );

            // Only proceed if no events exist
            if (!hasEvents) {
                // Validate date is today or in future
                if (clickedMoment.isBefore(today)) {
                    showError("Can't select date", "Please select today's date or a future date");
                    return;
                }
              // Set smart default time (next hour if today, 9am if future)
              const defaultTime = clickedMoment.isSame(today, 'day')
                ? moment().add(1, 'hour').startOf('hour') // Next full hour
                : clickedMoment.set({ hour: 9, minute: 0 }); // 9:00 AM

              startTime.value = defaultTime.format("YYYY-MM-DDTHH:mm");
              showModal('lessonScheduleModal');
            } else {
              // Optional: Show existing events if needed
              eventItems.value = events.value.filter(event =>
                moment(event.start).format('YYYY-MM-DD') === clickedDateStr
              );
              showModal('lessonsModal');
            }
          };

        const handleAddSchedule = () => {

            const today = moment().startOf('day');

            // Validate date is today or in future
            if (clickedDate.value.isBefore(today)) {
                showError("Can't select date", "Please select today's date or a future date");
                return;
              }

            closeModal('lessonsModal');
            console.log(clickedDate.value);
            startTime.value = moment(clickedDate.value).format("YYYY-MM-DDTHH:mm");
            showModal('lessonScheduleModal');
        };

        const showModal = (modalId) => {
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();
        };

        const closeModal = (modalId) => {
            const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
            if (modal) modal.hide();
        };

        const fetchSchedules = async () => {
          NProgress.start();
          try {
            const response = await axios.get('schedule-lessons');
            events.value = Array.isArray(response.data) ? response.data : [];
            refreshCalendar();
          } catch (error) {
            console.error('Error fetching schedules:', error);
            showError('Failed to fetch schedules', 'error');
          } finally {
            NProgress.done();
          }
        };

        const calendarInitialization = () => {
          const calendarEl = document.getElementById('calendar');
          if (calendarEl) {
            calendarInstance.value = new FullCalendar.Calendar(calendarEl, {
              initialView: 'dayGridMonth',
              slotMinTime: '06:00:00',
              slotMaxTime: '18:30:00',
              slotDuration: '00:30:00',
              slotLabelInterval: '00:30:00',
              allDaySlot: false,
              nowIndicator: true,
              dateClick: handleDateClick,
              events: events.value,
              eventClick: (info) => {
                info.jsEvent.preventDefault();
                editEvent(info.event);
              }
            });
            calendarInstance.value.render();
          }
        };

        const refreshCalendar = () => {
          if (calendarInstance.value) {
            calendarInstance.value.removeAllEvents();
            calendarInstance.value.addEventSource(events.value);
          }
        };

        const notification = (text, icon) => {
          Swal.fire({
            toast: true,
            position: "top-end",
            html: text,
            showConfirmButton: false,
            timer: 5500,
            timerProgressBar: true,
            icon: icon,
            didOpen: (toast) => {
              toast.onmouseenter = Swal.stopTimer;
              toast.onmouseleave = Swal.resumeTimer;
            }
          });
        };

        const showSuccess = (message) => {
            Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'success',
              title: message,
              showConfirmButton: false,
              timer: 3000
            });
          };

          const showError = (
            message,
            detail,
            {
                confirmText = 'OK',
                icon = 'error',
            } = {}
            ) => {
            const baseOptions = {
                icon,
                title: message,
                text: detail,
                confirmButtonText: confirmText,
                didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            };

            // Clean up undefined options
            const cleanOptions = Object.fromEntries(
                Object.entries(baseOptions).filter(([_, v]) => v !== undefined)
            );

            return Swal.fire(cleanOptions);
        };

        const editEvent = (event) => {
          NProgress.start();
          selectedEvent.value = {
            id: event.id,
            ...event.extendedProps
          };
          student.value = `${event.extendedProps.student.fname} ${event.extendedProps.student.mname} ${event.extendedProps.student.sname}`;
          studentId.value = event.extendedProps.student.id;
          lessonId.value = event.extendedProps.lesson.id;
          comments.value = event.extendedProps.comments;
          startTime.value = moment(event.start).format("YYYY-MM-DDTHH:mm");
          fetchLessons(studentId.value);

          closeModal('lessonsModal');
          showModal('lessonScheduleModal');
          NProgress.done();
        };

        const deleteEvent = async (eventId) => {
          if (confirm("Are you sure you want to delete this lesson?")) {
            try {
              NProgress.start();
              await axios.delete(`/schedule-lesson/${eventId}`);
              await fetchSchedules();
              notification("Lesson deleted successfully!", "success");
            } catch (error) {
              notification("Failed to delete the lesson. Please try again.", "error");
            } finally {
              NProgress.done();
            }
          }
        };

        // Lifecycle hooks
        onMounted(() => {
          calendarInitialization();
          fetchSchedules();
        });

        // Watchers
        watch(events, () => {
          refreshCalendar();
        }, { deep: true });

        return {
          student,
          studentId,
          lessonId,
          lessons,
          startTime,
          finishTime,
          location,
          comments,
          eventItems,
          selectedEvent,
          isSubmitting,
          searchStudent,
          submitForm,
          formatDate,
          handleDateClick,
          handleAddSchedule,
          editEvent,
          deleteEvent
        };
      }
    });

    lessonSchedule.mount('#lessonSchedule');
</script>
@endsection
