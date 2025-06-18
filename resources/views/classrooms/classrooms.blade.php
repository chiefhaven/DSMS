@extends('layouts.backend')

@section('content')
<div id="classrooms">
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Classes</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a href="#" class="btn btn-primary" @click.prevent="openAddclassroomModal('null')">
                <i class="fa fa-fw fa-plus mr-1"></i> Add class
            </a>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
      <div class="block-content">
      <div class="row">
        <div class="mb-5 col-md-12">
            <div class="card">
                <div class="card-body">
                    <p class="card-text">Classrooms meant for theory lessons.</p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3" v-for="classroom in classrooms" :key="classroom.id">
            <div class="block block-rounded block-link-shadow text-center">
                <div class="block-content block-content-full p-5">
                    <i class="fa fa-fw fa-house fa-2xl text-large"></i>
                </div>
                <div class="block-content block-content-full block-content-sm bg-body-light">
                    @{{classroom.name}} <br>
                    <div class="text-muted text-small">
                       Location: @{{classroom.location}}<br>
                       Description: @{{classroom.description}}
                    </div>
                </div>
                <div class="block-content block-content-full overflow-visible">
                    <div class="row ">
                        <div class="col-md-12">
                            <p class="mb-2">
                                <p class="font-size-sm font-italic text-muted mb-0">
                                    Instructors:
                                    <div v-for="instructor in classroom.instructors" :key="instructors.id">
                                        @{{ instructor.fname }} @{{ instructor.sname }}
                                    </div>
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
                                    <a class="dropdown-item" href="{{ url('/view-classroom') }}">
                                        View
                                    </a>
                                    <button class="dropdown-item" @click="openAddclassroomModal(classroom.id)">
                                        Edit
                                    </button>

                                    <button class="dropdown-item" @click="confirmDeleteClassRoom(classroom.id)">
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
      @include('classrooms/includes/addclassroomModal')
      </div>
  </div>
</div>
  <!-- END Hero -->
<script>
    $('.delete-confirm').on('click', function (e) {
        e.preventDefault();
        var form = $(this).parents('form');
        swal({
            title: 'Delete class room',
            text: 'Are you sure you want to delete classroom',
            icon: 'warning',
            buttons: ["Cancel", "Yes!"],
        }).then(function(isConfirm){
                if(isConfirm){
                        form.submit();
                }
        });
    });


    const classrooms = createApp({
      setup() {
        const showAddClassRoomModal = ref(false);
        const classroomData = ref({});
        const classrooms = ref([]);
        const instructors = ref([]);
        const instructor = ref('');
        const error = ref(false);
        const state = ref({
            modalTitle: 'Add classroom',
            buttonName:'Save',
            name:'',
            description:'',
            location:'',
            classroomId:null,
        })

        const openAddclassroomModal = async(classRoom) => {
            try{
                const payload = {
                    department: 'theory',
                    status: 'active',
                };

                const response = await axios.get(`/instructors-json`, { params: payload });

                if (response.status === 200) {
                    instructors.value = response.data;

                } else {
                    notification('Failed to fetch instructors.', 'error');
                }
            } catch (error) {
                notification(error.response.data.message, 'error');
            }

            if (classRoom != 'null') {
                // Populate modal for editing
                state.value.modalTitle = 'Edit class room';
                state.value.buttonName = 'Update';

                // Ensure classroom exists in the classrooms array/object
                const selectedclassroom = classrooms.value.find((l) => l.id === classRoom);
                if (selectedclassroom) {
                    state.value.classroomId = selectedclassroom.id;
                    state.value.name = selectedclassroom.name;
                    state.value.description = selectedclassroom.description;
                    state.value.location = selectedclassroom.location;

                    if (Array.isArray(selectedclassroom.instructors) && selectedclassroom.instructors.length > 0) {
                        instructor.value = selectedclassroom.instructors[0].id;
                    } else {
                        instructor.value = null;
                    }
                } else {
                    return; // Exit if classroom is invalid
                }
            } else {
                // Reset modal for adding a new classroom
                state.value.modalTitle = 'Add class room';
                state.value.buttonName = 'Save';
                state.value.classroomData = { name: '', capacity: '' }; // Clear form data
            }

            showAddClassRoomModal.value = true;
        };

        onMounted(() => {
            fetchClassRooms();
        });


        const closeForm = () => {
            resetForm();
            showAddClassRoomModal.value = false;
        }

        const confirmDeleteClassRoom = async(classroomId) => {
            Swal.fire({
                title: 'Delete class room?',
                text: 'Do you want to delete this class room? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteClassRoom(classroomId);
                }
            });
        };

        const deleteClassRoom = async(classRoom) => {
            NProgress.start();

            if (!classRoom) {
                notification('Invalid classroom provided for deletion.', 'error');
                return;
            }

            // Perform the delete action
            try {
                const response = await axios.delete(`/delete-classroom/${classRoom}`);

                if (response.status === 200) {
                    notification('Classroom deleted successfully.', 'success');

                    // Update local state to remove the deleted classroom
                    classrooms.value = classrooms.value.filter((l) => l.id !== classRoom);
                } else {
                    notification('Failed to delete the classroom.', 'error');
                }
            } catch (error) {
                notification(error, 'error');
            } finally{
                NProgress.done();
            }
        };

        const checkInstructorClassFleetAssignment = async (instructor) => {
            try {
                // Send the request to check assignment
                const response = await axios.post('/check-class-fleet-assignment', { instructor });

                console.log(response)

                if (response.status === 200 && response.data === true) {
                    NProgress.done()
                    const result = await Swal.fire({
                        title: 'Assigned Car Detected',
                        text: 'The instructor is already assigned to a car. Do you want to continue? This will unassign them from the car.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Continue',
                        cancelButtonText: 'Cancel',
                    });

                    // Return true if user confirms, false otherwise
                    return result.isConfirmed;
                } else if (response.status === 200 && response.data === false) {
                    // No assignment exists, proceed normally
                    return true;
                } else {
                    throw new Error('Unexpected response status');
                }
            } catch (error) {
                // Notify the user of an error
                notification(
                    error.response?.data?.message || 'Failed to verify the assignment. Please try again.',
                    'error'
                );
                return false; // Return false on error
            } finally {
                NProgress.done();
            }

        };

        const postClassRoom = async () => {
            NProgress.start();
            try {
                // Prepare the payload
                const payload = {
                    name: state.value.name,
                    location: state.value.location,
                    description: state.value.description,
                    instructor: instructor.value,
                };

                // Check if instructor is already assigned
                const isAssignmentValid = await checkInstructorClassFleetAssignment(instructor.value);
                if (!isAssignmentValid) {
                    return;
                }

                // Determine endpoint and HTTP method
                const endpoint = state.value.classroomId
                    ? `/updateclassroom/${state.value.classroomId}`
                    : '/storeclassroom';
                const method = state.value.classroomId ? 'put' : 'post';

                NProgress.start();

                // Send the request to save classroom
                const response = await axios[method](endpoint, payload);

                if (response.status === 200 || response.status === 201) {
                    // Notify success and refresh classrooms
                    notification('Classroom saved successfully.', 'success');
                    closeForm();
                    fetchClassRooms();
                } else {
                    throw new Error('Unexpected response status');
                }
            } catch (error) {
                notification(
                    error.response?.data?.message || 'Failed to save the classroom. Please try again.',
                    'error'
                );
            } finally {
                NProgress.done();
            }
        };

        const resetForm = () => {
            state.value.name = '';
            state.value.location = '';
            state.value.description = '';
            instructor.value = '';
            state.value.classroomId = null; // Reset the ID for a new classroom
            state.value.modalTitle = 'Add classroom'; // Set default title
            state.value.buttonName = 'Save'; // Set default button label
        };

        const fetchClassRooms = async () => {
            try {
                // Send GET request to fetch classrooms
                const response = await axios.get('/getClassRooms');

                // Update the classroom list in state
                classrooms.value = response.data;
            } catch (error) {

                // Optionally, show an error message
                notification('Failed to load classrooms. Please try again later.', 'error');
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
            showAddClassRoomModal,
            closeForm,
            openAddclassroomModal,
            postClassRoom,
            classroomData,
            classrooms,
            confirmDeleteClassRoom,
            instructors,
            instructor,
            error,
        }
      }
    })

    classrooms.mount('#classrooms')

</script>

@endsection
