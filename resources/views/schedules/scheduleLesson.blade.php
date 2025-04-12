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
                <h5 class="modal-title" id="lessonScheduleModalLabel">@{{ selectedSchedule ? 'Edit Lesson Schedule' : 'Create Lesson Schedule' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                  <!-- Form to add student to list -->
                  <div class="col-md-5">
                    <form @submit.prevent="addStudentToList">
                      @csrf

                      <!-- Student -->
                      <div class="form-group mb-3">
                        <label for="student_id" class="form-label">Search student</label>
                        <input
                          name="student_id"
                          id="student_id"
                          v-model="student"
                          class="form-control"
                          @input="searchStudent"
                          required
                        />
                      </div>

                      <!-- Lesson -->
                      <div class="form-group mb-3">
                        <label for="lesson_id" class="form-label">Lesson</label>
                        <select v-model="selectedLesson" id="lesson_id" class="form-control" required>
                          <option value="">Select Lesson</option>
                          <option v-for="lesson in lessons" :key="lesson.id" :value="lesson">
                            @{{ lesson.name }}
                          </option>
                        </select>
                      </div>

                      <!-- Location -->
                      <div class="form-group mb-3">
                        <label class="form-label">Location</label>
                        <select v-model="location" id="location" class="form-select" required>
                          <option value="">Select location</option>
                          <option>Area 4</option>
                          <option>Area 49</option>
                          <option>City center</option>
                          <option>Students Home</option>
                          <option>Other</option>
                        </select>
                      </div>

                      <div class="form-group">
                        <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                          <span v-if="isSubmitting">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                            Adding...
                          </span>
                          <span v-else>
                            Add to list
                          </span>
                        </button>
                      </div>
                    </form>
                  </div>

                  <!-- Selected students table -->
                  <div class="col-md-7">
                    <strong>Selected students</strong>
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>Student</th>
                          <th>Lesson</th>
                          <th>Location</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(selectedStudent, index) in selectedStudents" :key="index">
                            <td>@{{ selectedStudent.student }}</td>
                            <td>@{{ selectedStudent.selectedLesson.name }}</td>
                            <td>@{{ selectedStudent.location }}</td>
                            <td>
                                <span><button class="btn btn-danger btn-sm" @click="removeStudentFromList(index)">Remove</button></span>
                            </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="row mt-5">
                    <hr>
                    <div class="col-md-5">
                    </div>
                    <div class="col-md-7">
                        <!-- Form to submit the schedule -->
                        <form @submit.prevent="submitForm">
                            <div class="form-group mb-3">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input
                                type="datetime-local"
                                v-model="startTime"
                                id="start_time"
                                class="form-control"
                                required
                                />
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label text-success">
                                Finish time will be @{{ formatDate(finishTime) }}
                                </label>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
                                <span v-if="isSubmitting">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                    @{{ selectedSchedule ? "Updating..." : "Creating..." }}
                                </span>
                                <span v-else>
                                    @{{ selectedSchedule ? "Update Schedule" : "Create Schedule" }}
                                </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
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
                        <template v-for="(event, index) in eventItems" :key="index">
                            <p class="mb-4 d-flex align-items-center gap-3 flex-wrap">
                                <span>
                                  <strong>Date:</strong>
                                  <span> @{{ event.date }}</span>
                                </span>
                                <span>
                                  <strong>Time:</strong>
                                  <span> @{{ event.time }}</span>
                                </span>
                              </p>

                        </template>
                        DETAILS
                        <table class="table table-striped">
                            <thead>
                              <tr>
                                <th>Student</th>
                                <th>Lesson</th>
                                <th>Location</th>
                                <th>Status</th>
                              </tr>
                            </thead>
                            <tbody>
                              <template v-for="(event, index) in eventItems" :key="index">
                                <tr v-for="(student, idx) in event.students" :key="idx">
                                    <td>
                                      @{{ student.fname }} @{{ student.mname ?? '' }} @{{ student.sname }}
                                    </td>
                                    <td>@{{ student.pivot.lesson.name }}</td>
                                    <td>@{{ student.pivot.location }}</td>
                                    <td>@{{ student.pivot.status }}</td>
                                </tr>
                              </template>
                            </tbody>
                        </table>
                    </div>

                    <div v-else>
                        No schedules attendances this day!
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" @click="editSchedule(eventItems)">
                        Edit schedule
                    </button>
                    <button class="btn btn-sm text-danger" @click="deleteEvent(eventItems.id)">
                        <i class="fas fa-trash"></i> Delete Schedule
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
        const selectedStudents = ref([]);
        const studentId = ref("");
        const lessonId = ref("");
        const selectedLesson = ref("");
        const startTime = ref("");
        const location = ref("");
        const comments = ref("");
        const lessons = ref([]);
        const studentsData = ref([]);
        const events = ref([]);
        const eventItems = ref([]);
        const selectedSchedule = ref(null);
        const clickedDate = ref("");
        const calendarInstance = ref(null);
        const isSubmitting = ref(false);
        const scheduleId = ref('');
        const hasError = ref(false);

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
          if (!selectedStudents.value || !startTime.value) {
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
            selectedStudents: selectedStudents.value,
            start_time: startTime.value,
            finish_time: finishTime.value,
            lessonScheduleId: selectedSchedule.value?.id ?? null,
          };

          try {
            const endpoint = selectedSchedule.value?.id
              ? `/update-lesson-schedule/${selectedSchedule.value.id}`
              : "/store-lesson-schedule";

            const method = selectedSchedule.value?.id ? "put" : "post";
            const response = await axios[method](endpoint, payload);

            notification(response.data.message || "Lesson schedule saved successfully!", "success");
            await fetchSchedules();
            closeModal('lessonScheduleModal');
            clearForm();
          } catch (error) {
            handleApiError(error);
          } finally {
            isSubmitting.value = false;
            NProgress.done();
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
          selectedLesson.value = '',
          startTime.value = '';
          location.value = '';
          comments.value = '';
          selectedSchedule.value = null;
        };

        const clearStudentSelection = () => {
            studentId.value = '';
            student.value = '';
            location.value = '';
            lessons.value = [];
            selectedLesson.value = '',
            hasError.value = false;
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
              eventItems.value = events.value
                .filter(event => moment(event.start).format('YYYY-MM-DD') === clickedDateStr)
                .map(event => ({
                    id: event.id,
                    date: event.start,
                    time: `${moment(event.start).format('HH:mm')} - ${moment(event.end).format('HH:mm')}`,
                    students: event.extendedProps?.students,
                    instructor: `${event.extendedProps?.instructor?.fname ?? ''} ${event.extendedProps?.instructor?.sname ?? ''}`.trim(),
                    lesson: event.extendedProps?.lesson?.name ?? '',
                    location: event.extendedProps?.location ?? '',
                    comments: event.extendedProps?.comments ?? '',
                }));
                console.log(eventItems.value);
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

            if (!calendarEl) return;

            // Destroy previous instance if re-initializing
            if (calendarInstance.value) {
              calendarInstance.value.destroy();
            }

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
              eventClick: handleDateClick,
              headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
              },
              height: 'auto',
            });

            calendarInstance.value.render();
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

            const cleanOptions = Object.fromEntries(
                Object.entries(baseOptions).filter(([_, v]) => v !== undefined)
            );

            return Swal.fire(cleanOptions);
        };

        const editSchedule = (event) => {
            try {
              NProgress.start();

              console.log(event);

              const props = event[0] || {};
              const studentsList = props.students || [];

              selectedSchedule.value = {
                id: props.id,
                ...props
              };

              console.log(selectedSchedule.value);

              // Clear and repopulate selected students
              selectedStudents.value = studentsList.map((student) => ({
                student: [student.fname, student.mname, student.sname].filter(Boolean).join(' '),
                selectedLesson: student.pivot?.lesson ?? null,
                location: student.pivot?.location ?? props.location ?? '',
                comments: props.comments ?? ''
              }));

              startTime.value = selectedSchedule.value.date
                ? new Date(selectedSchedule.value.date).toISOString().slice(0, 16)
                : '';

              closeModal('lessonsModal');
              showModal('lessonScheduleModal');
            } catch (error) {
              console.error("Error editing event:", error);
              notification("Failed to edit the lesson. Please try again.", "error");
            } finally {
              NProgress.done();
            }
        };

        const deleteEvent = async (eventId) => {
            if (confirm("Are you sure you want to delete this lesson?")) {
              try {
                NProgress.start();
                await axios.delete(`/schedule-lesson/${eventId}`);

                // Remove from eventItems list
                const index = eventItems.value.findIndex((item) => item.id === eventId);
                if (index !== -1) {
                  eventItems.value.splice(index, 1);
                }

                await fetchSchedules();


                notification("Lesson deleted successfully!", "success");
              } catch (error) {
                notification("Failed to delete the lesson. Please try again.", "error");
              } finally {
                NProgress.done();
              }
            }
        };

        const addStudentToList = async () => {
            if (!studentId.value) {
                showError('Student name must be filled', 'error');
                hasError.value = true;
                return;
            }

            if (!selectedLesson.value) {
                showError('Lesson must be filled', 'error');
                hasError.value = true;
                return;
            }

            const alreadyExists = selectedStudents.value.some(
                (item) => item.studentId === studentId.value
            );

            if (alreadyExists) {
                showError('Student already in list', 'error');
                hasError.value = true;
                return;
            }

            try {
                const response = await axios.post('/checkStudentSchedule', {
                    studentId: studentId.value,
                    scheduleId: scheduleId.value
                });

                console.log(response);

                if (response.data.feedback === "success") {
                    // Add the student to the selected students list
                    selectedStudents.value.push({
                        studentId: studentId.value,
                        location: location.value,
                        student: student.value,
                        selectedLesson: selectedLesson.value,
                    });

                    clearStudentSelection();

                    showSuccess("Student added successifully, remember to click create schedule to save changes");

                } else {
                    // Handle the error if the response is not success
                    showError(response.data.message, 'error');
                    hasError.value = true; // Set error state to true if failed
                }

            } catch (error) {
                console.log(error)
                showError("Error", 'An error occurred while checking the student schedule.');
            }
        };

        const removeStudentFromList = (index) => {

            selectedStudents.value.splice(index, 1);

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
          selectedSchedule,
          isSubmitting,
          searchStudent,
          submitForm,
          formatDate,
          handleDateClick,
          handleAddSchedule,
          editSchedule,
          deleteEvent,
          selectedStudents,
          addStudentToList,
          selectedLesson,
          removeStudentFromList
        };
      }
    });

    lessonSchedule.mount('#lessonSchedule');
</script>
@endsection
