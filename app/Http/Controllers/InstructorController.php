<?php
namespace App\Http\Controllers;

use App\Models\Instructor;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\District;
use App\Models\Lesson;
use App\Models\Permission;
use App\Models\Role;
use App\Http\Requests\StoreInstructorRequest;
use App\Http\Requests\UpdateInstructorRequest;
use App\Models\Administrator;
use Session;
use Illuminate\Support\Str;
use PDF;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class InstructorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['role:superAdmin|admin']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $instructor = Instructor::with('User', 'Lesson', 'Fleet')->get();
        return view('instructors.instructors', compact('instructor'));
    }

    public function indexInstructors(Request $request)
    {
        // Validate the query parameters
        $validated = $request->validate([
            'status' => 'nullable|string|in:active,inactive',
            'department' => 'nullable|string',
        ]);

        // Apply filters dynamically
        $query = Instructor::with(['User', 'Lesson', 'Fleet']);

        if ($request->has('status')) {
            $query->where('status', $validated['status']);
        }

        if ($request->has('department')) {
            $query->where('department', $validated['department']);
        }

        // Fetch the data with pagination
        $instructors = $query->get(); // Adjust the number per page as needed

        return response()->json($instructors);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $district = District::get();
        $lesson = Lesson::get();
        return view('instructors.addinstructor', compact('district', 'lesson'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreInstructorRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInstructorRequest $request)
    {
        $messages = [
            'fname.required' => 'The "First name" field is required!',
            'sname.required'   => 'The "Sir name" field is should be unique!',
            'email.required' => 'The "Email" is required!',
            'email.unique' => 'The "Email" is already in use',
            'gender.required'   => 'The "Gender" field required!',
            'date_of_birth.required' => 'Date of birth is required',
        ];

        // Validate the request
        $this->validate($request, [
            'first_name'  =>'required',
            'sir_name' =>'required',
            'email'   =>'required | unique:users',
            'address' =>'required',
            'gender'  =>'required',
            'date_of_birth' =>'required',
            'district' =>'required',
            'phone' =>'required'

        ], $messages);

        $post = $request->All();

        $district = havenUtils::selectDistrict($post['district']);

        $instructor = new instructor;

        $instructor->fname = $post['first_name'];
        $instructor->sname = $post['sir_name'];
        $instructor->gender = $post['gender'];
        $instructor->phone = $post['phone'];
        $instructor->address = $post['address'];
        $instructor->date_of_birth = $post['date_of_birth'];
        $instructor->district_id = $district;

        $instructor->save();


        $user = new User;
        $user->name = $post['username'];
        $user->instructor_id = $instructor->id;
        $user->email = $post['email'];
        $user->password = Hash::make($post['password']);
        $user->save();

        $user->assignRole('instructor');

        return redirect()->back()->with('message', 'Instructor added!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function show(Instructor $instructor)
    {
        return view('instructors.viewinstructor', [ 'instructor' => $instructor ], compact('instructor'));
    }

    /**
     * Display own profile.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function showProfile()
    {
        $instructor = Instructor::find(Auth::user()->instructor_id);
        return view('instructors.viewinstructor', compact('instructor'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $instructor = Instructor::with('User')->find($id);
        $district = district::get();
        $lesson = Lesson::get();
        return view('instructors.editinstructor', [ 'instructor' => $instructor ], compact('instructor', 'district', 'lesson'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateInstructorRequest  $request
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInstructorRequest $request, Instructor $instructor)
    {
        $messages = [
            'fname.required' => 'Firstname is required',
            'sname.required'   => 'Sirname is required',
            'email.required' => 'Email is required',
            'email.unique' => 'Email is already in use',
            'gender.required'   => 'The "Gender" field required',
            'date_of_birth.required' => 'Date of birth is required',
        ];

        // Validate the request
        $this->validate($request, [
            'first_name'  =>'required',
            'sir_name' =>'required',
            'email'          => 'required|unique:users,email,' . Instructor::find($request->instructor_id)->user->id,
            'address' =>'required',
            'gender'  =>'required',
            'date_of_birth' =>'required',
            'district' =>'required',
            'phone' =>'required',

        ], $messages);

        $post = $request->All();

        $district = havenUtils::selectDistrict($post['district']);

        $instructor = Instructor::find($post['instructor_id']);

        $instructor->fname = $post['first_name'];
        $instructor->sname = $post['sir_name'];
        $instructor->gender = $post['gender'];
        $instructor->phone = $post['phone'];
        $instructor->address = $post['address'];
        $instructor->date_of_birth = $post['date_of_birth'];
        $instructor->district_id = $district;

        $user = User::where('instructor_id', $post['instructor_id'])->firstOrFail();

        $user->email = $post['email'];

         if(isset($post['password'])){
            $user->password = Hash::make($post['password']);
        }

        $instructor->save();
        $user->save();
        $user->assignRole('instructor');

        return redirect('/instructors')->with('message', 'Instructor updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Instructor  $instructor
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $instructor = Instructor::find($id);

        // Check if the instructor exists
        if ($instructor) {
            // Store the instructor's name for alert
            $instructorName = $instructor->fname . " " . $instructor->sname;

            // Start a database transaction to ensure consistency
            DB::beginTransaction();

            try {
                // Delete related users with the instructor_id
                User::where('instructor_id', $id)->delete();

                // Delete the instructor record
                $instructor->delete();

                // Commit the transaction
                DB::commit();

                // Show success message
                Alert::toast('Instructor ' . $instructorName . ' deleted', 'success');
            } catch (\Exception $e) {
                // Rollback in case of error
                DB::rollBack();

                // Log the error for debugging
                Log::error('Error deleting instructor: ' . $e->getMessage());

                // Show error message
                Alert::toast('Failed to delete instructor. Please try again later.', 'error');
            }
        } else {
            // Show error message if instructor not found
            Alert::toast('Instructor not found', 'error');
        }

        // Redirect to the instructors page
        return redirect('/instructors');
    }

    public function instructorSearch(Request $request)
    {
        $datas = Instructor::select("fname", "sname")
            ->where("fname","LIKE","%{$request->instructor}%")
            ->orWhere("sname","LIKE","%{$request->instructor}%")
            ->get();

        $dataModified = array();

        foreach ($datas as $instructor){
           $dataModified[] = $instructor->fname.' '.$instructor->sname;
         }

        return response()->json($dataModified);
    }
}
