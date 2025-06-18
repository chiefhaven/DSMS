@extends('layouts.backend')

@section('content')
<div id="lessons">
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Lessons</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a href="#" class="btn btn-primary" @click.prevent="openAddLessonModal('null')">
                <i class="fa fa-fw fa-plus mr-1"></i> Add Lesson
            </a>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
      <div class="block-content">
      <div class="row">
        <div class="col-md-6 col-xl-3" v-for="lesson in lessons" :key="lesson.id">
            <div class="block block-rounded block-link-shadow text-center">
                <div class="block-content block-content-full p-5">
                    <i class="fa fa-fw fa-book fa-2xl text-large"></i>
                </div>
                <div class="block-content block-content-full block-content-sm bg-body-light">
                    @{{lesson.name}} <br>
                    <div class="text-muted text-small">
                        @{{lesson.department.name}}
                    </div>
                </div>
                <div class="block-content block-content-full overflow-visible">
                    <div class="row ">
                        <div class="col-md-12">
                            <p class="mb-2">
                                <p class="font-size-sm font-italic text-muted mb-0">
                                    @{{lesson.description}}
                                </p>
                            </p>
                        </div>
                        <div class="col-md-12">
                            <div class="dropdown d-inline-block">
                                <button type="button" class="btn btn-sm btn-primary" id="" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Action
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-0">
                                <div class="p-2">
                                    <a class="dropdown-item" href="{{ url('#') }}">
                                        View
                                    </a>
                                    <button class="dropdown-item" @click="openAddLessonModal(lesson.id)">
                                        Edit
                                    </button>

                                    <button class="dropdown-item" @click="confirmDeleteLesson(lesson.id)">
                                        Delete
                                    </button>
                                </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
      </div>
      @include('lessons/includes/addLessonModal')
      </div>
  </div>
</div>
  <!-- END Hero -->
<script>
    $('.delete-confirm').on('click', function (e) {
        e.preventDefault();
        var form = $(this).parents('form');
        swal({
            title: 'Delete lesson',
            text: 'Are you sure you want to delete lesson',
            icon: 'warning',
            buttons: ["Cancel", "Yes!"],
        }).then(function(isConfirm){
                if(isConfirm){
                        form.submit();
                }
        });
    });


    const lessons = createApp({
      setup() {
        const showAddLessonModal = ref(false);
        const lessonData = ref({});
        const lessons = ref([]);
        const departments = ref([]);
        const state = ref({
            modalTitle: 'Add lesson',
            buttonName:'Save',
            department: 'Practical',
            name:'',
            description:'',
            lessonId:null,
        })

        const openAddLessonModal = (lesson) => {
            if (lesson != 'null') {
                // Populate modal for editing
                state.value.modalTitle = 'Edit Lesson';
                state.value.buttonName = 'Update';

                // Ensure lesson exists in the lessons array/object
                const selectedLesson = lessons.value.find((l) => l.id === lesson);
                if (selectedLesson) {
                    state.value.lessonId = selectedLesson.id;
                    state.value.name = selectedLesson.name;
                    state.value.description = selectedLesson.description;
                    state.value.department = selectedLesson.department.id;
                } else {
                    return; // Exit if lesson is invalid
                }
            } else {
                // Reset modal for adding a new lesson
                state.value.modalTitle = 'Add Lesson';
                state.value.buttonName = 'Save';
                state.value.lessonData = { name: '', description: '' }; // Clear form data
            }

            showAddLessonModal.value = true;
        };

        onMounted(() => {
            fetchLessons();
        });


        const closeForm = () => {
            showAddLessonModal.value = false;
        }

        const confirmDeleteLesson = async(lessonId) => {
            Swal.fire({
                title: 'Delete Lesson?',
                text: 'Do you want to delete this lesson? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteLesson(lessonId);
                }
            });
        };

        const deleteLesson = async(lesson) => {
            if (!lesson) {
                notification('Invalid lesson provided for deletion.', 'error');
                return;
            }

            // Perform the delete action
            try {
                const response = await axios.delete(`/delete-lesson/${lesson}`);

                if (response.status === 200) {
                    notification('Lesson deleted successfully.', 'success');

                    // Update local state to remove the deleted lesson
                    lessons.value = lessons.value.filter((l) => l.id !== lesson);
                } else {
                    notification('Failed to delete the lesson.', 'error');
                }
            } catch (error) {
                notification(error.response.data.message, 'error');
            }
        };

        const postLesson = async () => {
            NProgress.start();

            console.log('haven')

            try {
                // Prepare the payload
                const payload = {
                    lesson_name: state.value.name,
                    lesson_description: state.value.description,
                    lesson_department: state.value.department,
                };

                // Determine if this is an update or a new lesson
                const endpoint = state.value.lessonId
                    ? `/updatelesson/${state.value.lessonId}`
                    : '/storelesson';
                const method = state.value.lessonId ? 'put' : 'post';

                // Send the request
                const response = await axios[method](endpoint, payload);

                if (response.status === 200 || response.status === 201) {
                    // Handle success
                    notification('Lesson saved successfully.', 'success');

                    // Reset the form
                    resetForm();

                    // Close the modal
                    closeForm();

                    // Refresh the lesson list
                    fetchLessons();
                } else {
                    throw new Error('Unexpected response status');
                }
            } catch (error) {
                console.error('Error saving lesson:', error);

                notification(
                    error.response?.data?.message || 'Failed to save the lesson. Please try again.',
                    'error'
                );
            } finally {
                NProgress.done();
            }
        };

        const resetForm = () => {
            state.value.name = '';
            state.value.description = '';
            state.value.department = '';
            state.value.lessonId = null; // Reset the ID for a new lesson
            state.value.modalTitle = 'Add Lesson'; // Set default title
            state.value.buttonName = 'Save'; // Set default button label
        };

        const fetchLessons = async () => {
            try {
                // Send GET request to fetch lessons
                const response = await axios.get('/getLessons');

                // Update the lesson list in state
                lessons.value = response.data.lessons;
                departments.value = response.data.departments;

            } catch (error) {

                // Optionally, show an error message
                notification('Failed to load lessons. Please try again later.', 'error');
            }
        };


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
            state,
            showAddLessonModal,
            closeForm,
            openAddLessonModal,
            postLesson,
            lessonData,
            lessons,
            confirmDeleteLesson,
            departments,
        }
      }
    })

    lessons.mount('#lessons')

</script>

@endsection

