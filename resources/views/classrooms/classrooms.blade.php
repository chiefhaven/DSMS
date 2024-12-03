@extends('layouts.backend')

@section('content')
<div id="classrooms">
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
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
        <div class="p-1 mb-5 card col-md-12">
            <div class="card-body">
                <p class="card-text">Classrooms meant for theory lessons.</p>
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
                        @{{classroom.type}}
                    </div>
                </div>
                <div class="block-content block-content-full overflow-visible">
                    <div class="row ">
                        <div class="col-md-12">
                            <p class="mb-2">
                                <p class="font-size-sm font-italic text-muted mb-0">
                                    Capacity: @{{classroom.capacity}}
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

    const { createApp, ref, reactive, onMounted } = Vue

    const classrooms = createApp({
      setup() {
        const showAddClassRoomModal = ref(false);
        const classroomData = ref({});
        const classrooms = ref([]);
        const state = ref({
            modalTitle: 'Add classroom',
            buttonName:'Save',
            classroom_type: 'Practical',
            name:'',
            capacity:0,
            classroomId:null,
        })

        const openAddclassroomModal = (classRoom) => {
            if (classRoom != 'null') {
                // Populate modal for editing
                state.value.modalTitle = 'Edit class room';
                state.value.buttonName = 'Update';

                // Ensure classroom exists in the classrooms array/object
                const selectedclassroom = classrooms.value.find((l) => l.id === classroom);
                if (selectedclassroom) {
                    state.value.classroomId = selectedclassroom.id;
                    state.value.name = selectedclassroom.name;
                    state.value.capacity = selectedclassroom.capacity;
                    state.value.classroom_type = selectedclassroom.type;
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
            if (!classroom) {
                notification('Invalid classroom provided for deletion.', 'error');
                return;
            }

            // Perform the delete action
            try {
                const response = await axios.delete(`/delete-classroom/${classRoom}`);

                if (response.status === 200) {
                    notification('Classroom deleted successfully.', 'success');

                    // Update local state to remove the deleted classroom
                    classrooms.value = classrooms.value.filter((l) => l.id !== classroom);
                } else {
                    notification('Failed to delete the classroom.', 'error');
                }
            } catch (error) {
                notification(error.response.data.message, 'error');
            }
        };

        const postclassroom = async () => {
            NProgress.start();

            try {
                // Prepare the payload
                const payload = {
                    classroom_name: state.value.name,
                    classroom_capacity: state.value.capacity,
                    classroom_type: state.value.classroom_type,
                };

                // Determine if this is an update or a new classroom
                const endpoint = state.value.classroomId
                    ? `/updateclassroom/${state.value.classroomId}`
                    : '/storeclassroom';
                const method = state.value.classroomId ? 'put' : 'post';

                // Send the request
                const response = await axios[method](endpoint, payload);

                if (response.status === 200 || response.status === 201) {
                    // Handle success
                    notification('classroom saved successfully.', 'success');

                    // Reset the form
                    resetForm();

                    // Close the modal
                    closeForm();

                    // Refresh the classroom list
                    fetchclassrooms();
                } else {
                    throw new Error('Unexpected response status');
                }
            } catch (error) {
                console.error('Error saving classroom:', error);

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
            state.value.capacity = '';
            state.value.classroom_type = '';
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
            postclassroom,
            classroomData,
            classrooms,
            confirmDeleteClassRoom,
        }
      }
    })

    classrooms.mount('#classrooms')

</script>

@endsection
