@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add bulk attendance</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
                <div class="alert alert-info">
                    {{Session::get('message')}}
                </div>
          @endif
        </nav>
      </div>
    </div>
  </div>

<div class="content content-full" id="addBulkAttendance">
<div class="row">
    <div class="col-md-5 block-rounded block-bordered">
        <div class="block block-rounded block-themed block-transparent mb-0" style="background-color:#ffffff">
            <div class="block-content">
                <form class="mb-5" action="{{ url('/add-bulkAttendance') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                    @csrf
                    <div class="col-12 form-floating mb-4">
                        <input type="text" class="form-control" id="bulkAttendance_description" name="bulkAttendance_description" v-model="state.bulkAttendanceDescription" placeholder="Enter bulkAttendance Description">
                        <label for="invoice_discount">Attendance notes and reasons</label>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7 block block-rounded block-bordered">
        <h2 class="flex-grow-1 fs-4 fw-semibold my-2 my-sm-3">Add student to the group</h1>
            <div v-if="state">
                <div class="row haven-floating">
                    <div class="col-6 form-floating mb-4 text-uppercase">
                        <input
                            class="form-control"
                            id="student"
                            name="student"
                            v-model="state.studentName"
                            @input="studentSearch()"
                            @blur="onStudentChange($event)"
                            placeholder="Select student"
                            required>
                        <label for="student" class="text-capitalize">Select student</label>
                    </div>
                    <div class="col-6 form-floating mb-4">
                        <div v-if="state.isLoadingLessons" class="text-center my-3">
                            <span class="loading-dots">Loading lessons<span class="dot one">.</span><span class="dot two">.</span><span class="dot three">.</span></span>
                        </div>
                        <select v-if="!state.isLoadingLessons"
                            class="form-control"
                            id="bulkAttendance"
                            name="bulkAttendance"
                            v-model="state.bulkAttendance"
                            :disabled="!studentLessons.length"
                            placeholder="Select bulkAttendance Type"
                            required
                        >
                            <option disabled value="">Select option</option>
                            <option
                                v-for="opt in studentLessons"
                                :key="opt.id"
                                :value="opt.id"
                            >
                                @{{ opt.name }}
                            </option>
                        </select>
                        <label for="bulkAttendance" v-if="!state.isLoadingLessons">Lesson</label>
                        <small v-if="!studentLessons.length && state.studentId && !state.isLoadingLessons" class="text-danger">
                            No lessons found for the selected student.
                        </small>
                    </div>
                </div>
                <div class="block-content block-content-full text-end">
                    <button type="submit" @click="addStudentToGroup()" class="btn btn-primary rounded-pill px-4">Add to list</button>
                </div>
                <h2 class="flex-grow-1 fs-5 fw-semibold my-2 my-sm-3 border-lg mb-5">Select students</h2>
                    <hr>
                <div>
                    <table class="table table-striped">
                        <thead class="bg-primary text-white">
                          <tr>
                            <th>Student Name</th>
                            <th>Lesson attended</th>
                            <th class="text-end">Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(student, index) in state.selectedStudents" :key="index">
                            <td>
                              @{{ student.studentName }}
                              <div
                                v-if="student.bulkAttendances && student.bulkAttendances.some(e => e.pivot?.repeat === 1)"
                                class="text-danger fw-bold small mt-1">
                                Repeating
                              </div>
                            </td>
                            <td>
                                @{{ student.lessonName }}
                            </td>
                            <td class="text-end">
                              <button class="btn btn-danger btn-sm" @click="removeStudentFromGroup(index)">
                                Remove
                              </button>
                            </td>
                          </tr>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
    <div class="block-content block-content-full text-end">
        <button type="submit" :disabled="state.isSubmitButtonDisabled" @click="saveBulkAttendances()" class="btn btn-primary rounded-pill px-4">
            <template v-if="state.isLoading">
                Processing...
              </template>
              <template v-else>
                @{{ state.buttonText }}
              </template>
        </button>
    </div>
</div>
</div>
<!-- END Hero -->

    <script setup>

        const addBulkAttendance = createApp({
        setup() {
            const currentDate = new Date();
            const options = { day: 'numeric', month: 'long', year: 'numeric'};
            const state = ref({
                bulkAttendanceDescription: '',
                studentName: '',
                studentId:'',
                selectedStudents: [],
                errors: [],
                isSubmitButtonDisabled: false,
                isLoading: false,
                isLoadingLessons: false,
                buttonText: 'Submit',
            })

            var hasError = ref(false)
            const studentLessons = ref(false)

            const addStudentToGroup = async () => {
                hasError.value = false;

                if (!state.value.studentName || !state.value.studentId) {
                    notification('Student name must be filled', 'error');
                    hasError.value = true;
                    return hasError;
                }

                if (!state.value.bulkAttendance) {
                    notification('You must select a lesson', 'error');
                    hasError.value = true;
                    return hasError;
                }

                // Get lesson object
                const selectedLesson = studentLessons.value.find(
                    lesson => lesson.id === state.value.bulkAttendance
                );

                // Get attendance count from DB
                let dbAttendanceCount = 0;
                try {
                    const response = await axios.get('/api/student-lesson-attendances-count', {
                        params: {
                            lessonID: selectedLesson?.id,
                            studentId: state.value.studentId,
                        }
                    });

                    dbAttendanceCount = response.data.count ?? 0;

                    console.log(dbAttendanceCount)
                } catch (error) {
                    console.error('Error fetching attendance count:', error);
                    notification('Unable to fetch attendance count.', 'error');
                    return;
                }

                // Count pending same lesson additions in the current session
                const localLessonCount = state.value.selectedStudents.filter(
                    s => s.lessonId === selectedLesson?.id && s.studentId === state.value.studentId
                ).length;

                const totalSoFar = dbAttendanceCount + localLessonCount;
                console.log(totalSoFar)

                if (totalSoFar >= (selectedLesson?.pivot.lesson_quantity || 0)) {
                    notification('Reached the maximum number of this lesson for this student.', 'warning');
                    return;
                }

                // Add to list
                state.value.selectedStudents.push({
                    studentId: state.value.studentId,
                    studentName: state.value.studentName,
                    lessonId: selectedLesson?.id,
                    lessonQuantity: selectedLesson?.lesson_quantity,
                    lessonName: selectedLesson?.name || 'Lesson',
                });

                // Reset inputs
                state.value.studentName = '';
                state.value.studentId = '';
                state.value.bulkAttendance = '';

                notification('Student added to group', 'success');
            };

            function removeStudentFromGroup(index) {

                state.value.selectedStudents.splice(index, 1)
            }

            const saveBulkAttendances = async()=> {
                if(Object.keys( state.value.selectedStudents ).length == 0){
                    showAlert('Can not save', 'Student list must not be empty; add students or cancel the creation.', {
                        toast: false,
                        icon: 'error',
                        confirmText: 'Ok'
                    });

                    return false
                }

                try{
                    NProgress.start();
                    state.value.isSubmitButtonDisabled = true;
                    state.value.isLoading = true;

                    console.log('bulkAttendance data to be saved:', state.value.selectedStudents)

                    const response = await axios.post('/api/store-bulk-attendance', {
                        students: state.value.selectedStudents,
                        bulkAttendanceDescription: state.value.bulkAttendanceDescription,
                    }, {
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    });

                    notification('Attendances added successfully, page redirecting...', 'success');
                    window.location.replace('/bulk-attendances');
                }catch (error) {
                    notification('An error occurred while saving the bulk attendance. Please try again.', 'error');
                    state.value.isSubmitButtonDisabled = false;
                    state.value.isLoading = false;
                    state.value.buttonText = 'Submit';
                    console.log(error);
                    if (error.response && error.response.data) {
                        console.error('Error response:', error.response.data);
                        showAlert('Error', error.response.data.message || 'An unexpected error occurred.', {
                            toast: false,
                            icon: 'error',
                            confirmText: 'Close'
                        });
                    } else {
                        console.error('Error:', error);
                        showAlert('Error', 'An unexpected error occurred. Please try again later.', {
                            toast: false,
                            icon: 'error',
                            confirmText: 'Close'
                        });
                    }
                } finally {
                    NProgress.done();
                }
            }

            const onStudentChange = async(event) => {
                state.value.studentName = event.target.value;

            }

            const getStudentLessons = async () => {
                NProgress.start();
                state.value.isLoadingLessons = true;
                const path = "{{ route('api.student-lessons') }}";

                if (!state.value.studentId) {
                    return;
                }

                try {
                    const response = await axios.get(path, {
                        params: { studentId: state.value.studentId }
                    });

                    studentLessons.value = response.data;

                    if (!studentLessons.value || studentLessons.value.length === 0) {
                        notification('Student has no lessons remaining in course enrolled or is not enrolled yet', 'warning');
                        studentLessons.value = [];

                        setTimeout(() => {
                            state.value.studentName = '';
                            state.value.studentId = '';
                        }, 500);
                    }

                } catch (error) {
                    notification('Error fetching student lessons', 'error');
                } finally {
                    NProgress.done();
                    state.value.isLoadingLessons = false;
                }
            }

            function studentSearch() {
                var path = "{{ route('expense-student-search') }}";
                NProgress.start();

                $('#student').typeahead({
                    minLength: 2,
                    autoSelect: true,
                    highlight: true,
                    source: function (query, process) {
                        $.get(path, { student: query })
                            .done(function (data) {
                                NProgress.done();
                                if (data.length === 0) {
                                    notification('Student not found or not enrolled, search another name', 'error');
                                    return process([]);
                                }
                                return process(data);
                            })
                            .fail(function () {
                                NProgress.done();
                                notification('Error fetching student data', 'error');
                                return process([]);
                            });
                    },
                    updater: function (item) {
                        if (item && item.id) {
                            state.value.studentId = item.id;
                            state.value.studentName = item.name;

                            // Clear existing lessons
                            studentLessons.value = [];

                            // Fetch lessons for selected student
                            getStudentLessons();
                        } else {
                            notification('Invalid student selected', 'error');
                        }

                        return item.name || '';
                    }
                });
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

            onMounted(() => {

            });

            const showAlert = (
                message = '', // title
                detail = '',  // text
                {
                    icon = 'info',
                    toast = true,
                    confirmText = 'OK',
                    showCancel = false,
                    cancelText = 'Cancel'
                } = {}
            ) => {
                const baseOptions = {
                    icon,
                    title: message,
                    text: detail,
                    toast,
                    position: toast ? 'top-end' : 'center',
                    showConfirmButton: !toast,
                    confirmButtonText: confirmText,
                    showCancelButton: showCancel,
                    cancelButtonText: cancelText,
                    timer: toast ? 3000 : undefined,
                    timerProgressBar: toast,
                    didOpen: (toastEl) => {
                        if (toast) {
                            toastEl.addEventListener('mouseenter', Swal.stopTimer);
                            toastEl.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    }
                };

                return Swal.fire(baseOptions);
            };

            return {
                addStudentToGroup,
                removeStudentFromGroup,
                saveBulkAttendances,
                studentSearch,
                onStudentChange,
                state,
                hasError,
                studentLessons,
            }
        }
        })
        addBulkAttendance.mount('#addBulkAttendance')
    </script>
@endsection

