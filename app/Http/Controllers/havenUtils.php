<?php
    namespace App\Http\Controllers;

    use App\Models\Invoice;
    use App\Models\Course;
    use App\Models\Fleet;
    use App\Models\Student;
    use App\Models\Instructor;
    use App\Models\District;
    use App\Models\Lesson;
    use App\Models\Attendance;
    use App\Models\PaymentMethod;
    use App\Models\Invoice_Setting;
    use Carbon\Carbon;
    use SimpleSoftwareIO\QrCode\Facades\QrCode;
    use Auth;
    use Illuminate\Http\Request;

    use RealRashid\SweetAlert\Facades\Alert;

    class havenUtils extends Controller
    {
        static function student($studentName){

            $student_name = explode(" ", $studentName);
            $studentnameCount = count($student_name);

            if($studentnameCount = 2){

                $student = Student::where('fname', $student_name[0])->where('sname',$student_name[2])->first();

                if(!is_null($student)){

                    return $student;
                }

                else{

                    return null;
                }
            }

            elseif($studentnameCount = 3){

                $student = Student::where('fname', $student_name[0])->where('mname',$student_name[1])->where('sname',$student_name[2])->first();

                if(!is_null($student)){

                    return $student;
                }

                else{

                    return null;
                }
            }

            else{

                return null;

            }

        }

        public function autocompleteLessonSearch(Request $request)
        {
            // Retrieve the search term from the request
            $searchTerm = $request->input('search');

            if (empty($searchTerm)) {
                // Return an empty JSON response if no input is provided
                return response()->json([]);
            }

            // Fetch lessons matching the search term
            $datas = \DB::table('lessons')
                ->where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                ->get();

            return response()->json($datas);
        }


        static function studentID_InvoiceNumber($invoiceNumber){

                $student = Invoice::where('invoice_number', $invoiceNumber)->first();
                return $student;

        }

        static function instructorID($instructorName){

            $instructor_name = explode(" ", $instructorName);

            $instructor = Instructor::where('fname', $instructor_name[0])->where('sname',$instructor_name[1])->firstOrFail();
            return $instructor->id;
        }


        static function courseID($courseName){

            $course = Course::where('name', $courseName)->firstOrFail();
            return $course->id;
        }

        static function invoiceDiscountedPrice($courseName, $discount){

            $course = Course::where('name', $courseName)->firstOrFail();
            $discount = $discount;
            $total_price = $course->price - $discount;
            return $total_price;
        }

        static function coursePrice($courseName){

            $course = Course::where('name', $courseName)->firstOrFail();
            return $course->price;
        }



        static function invoiceTotal($courseName, $invoiceDiscount){

            $course = Course::where('name', $courseName)->firstOrFail();

            $invoiceTotal = $course->price-$invoiceDiscount;
            return $invoiceTotal;
        }

        static function invoiceBalance($paidAmount, $invoiceTotal){

            $invoiceBalance = $invoiceTotal - $paidAmount;
            return $invoiceBalance;
        }

        static function invoicePaid($invoiceNumber, $paid_amount){

            $invoicePaid = Invoice::where('invoice_number', $invoiceNumber)->first()->invoice_amount_paid + $paid_amount;
            return $invoicePaid;
        }


        static function selectDistrict($district){

            $district = District::where('name', $district)->firstOrFail();
            return $district->id;
        }

        static function lessonID($lessonName){

            $lesson = Lesson::where('name', $lessonName)->firstOrFail();
            return $lesson->id;
        }

        static function attendancePercent($studentID){

            $course_id = Student::where('id', $studentID)->firstOrFail()->course_id;

            if(!is_null($course_id)){

                $courseDuration = self::courseDuration($course_id);
                $attendanceCount = Attendance::where('student_id', $studentID)->count();

                if($attendanceCount > 0 && $courseDuration > 0){

                    $attendancePercent = $attendanceCount/$courseDuration*100;
                }

                else{

                    $attendancePercent = 0;
                }
            }

            else{

                $attendancePercent = 0;
            }


            return number_format((integer)$attendancePercent);
        }

        //check for course Duration a students is enrolled in based on current invoice
        static function courseDuration($course_id){

            $course = Course::where('id', $course_id)->first();
            //$courseDuration = $course->duration;
            $courseDuration = $course->lessons->sum('pivot.lesson_quantity');
            return $courseDuration;
        }

        //Generate invoice number
        static function generateInvoiceNumber(){

            $LatestInvoice = Invoice::whereMonth('created_at', Carbon::now())->max('id');

            if(isset($LatestInvoice)){

                $highestInvoiceNumber = Invoice::where('id', $LatestInvoice)->firstOrFail()->invoice_number;
                $invoiceNumberOnly = substr(strrchr($highestInvoiceNumber, '-'), 1) ;
                $newInvoiceNumberPlus =++ $invoiceNumberOnly;
                $newInvoiceNumber = sprintf("%05d", $newInvoiceNumberPlus);
            }

            else{

                $newInvoiceNumber = sprintf("%05d", 1);

            }


            $prefix = Invoice_Setting::find(1)->prefix;

            $useYear = Invoice_Setting::find(1)->year;

            if(isset($prefix) && $useYear == 1){
                $invoiceNumber = $prefix.'-'.date('Y').'-'.date('m').'-'.$newInvoiceNumber;
            }

            else {
                $invoiceNumber = 'Invoice-'.$newInvoiceNumber;
            }

            return $invoiceNumber;
        }

        //Get a payment method
        static function paymentMethod($paymentMethod){

            $paymentMethod = PaymentMethod::where('name', $paymentMethod)->firstOrFail()->id;
            return $paymentMethod;
        }

        static function fleetID($carRegistrationNumber){



                $fleet = Fleet::where('car_registration_number', $carRegistrationNumber)->first();

                if(!is_null($fleet)){

                    return $fleet->id;
                }

                else{

                    return null;
                }

        }

        static function qrCode($link){
            $qrCode = base64_encode(QrCode::format('svg')->size(120)->errorCorrection('H')->generate($link));
            return $qrCode;
        }

        static function checkStudentInstructor($studentId)
        {
            // Ensure there is an authenticated user
            $user = Auth::user();

            if (!$user || !$user->instructor_id) {
                return false;
            }

            // Retrieve the instructor's fleet
            $instructorFleet = Fleet::where('instructor_id', $user->instructor_id)->first();
            if (!$instructorFleet) {
                return false; // Instructor does not have a fleet assigned
            }

            // Retrieve the student and validate their fleet
            $student = Student::find($studentId);
            return $student && $instructorFleet->id == $student->fleet_id;
        }


        static function checkClassRoom($studentId)
        {
            // Ensure there is an authenticated user and retrieve their classroom IDs
            $user = Auth::user();

            if (!$user || !$user->instructor || !$user->instructor->classrooms()->exists()) {
                return false; // Instructor does not have a classroom assigned
            }

            // Get all classroom IDs associated with the instructor
            $classroomIds = $user->instructor->classrooms->pluck('id');

            // Retrieve the student and validate their classroom
            $student = Student::find($studentId);
            if (!$student) {
                Log::warning("Student not found with ID: $studentId");
                return false;
            }

            // Check if the student's classroom ID exists in the instructor's classrooms
            return $classroomIds->contains($student->classroom_id);
}


        static function invoiceQrCode($id){
            $invoice = Invoice::with('student')->find($id);
            $student = $invoice->student;

            if(!isset($student)){
                abort(404);
            }
            return $student;
        }

        static function docsQrCode($id){
            $student = Student::find($id);
            if(!isset($student)){
                abort(404);
            }
            return $student;
        }

        public function checkInstructorClassFleetAssignment(Request $request)
        {
            $data = $request->all();

            // Validate the incoming request data
            $request->validate([
                'instructor' => 'required|exists:instructors,id'
            ]);

            // Check if the instructor has a fleet assigned
            $fleetAssigned = Fleet::where('instructor_id', $data['instructor'])->exists();

            return response()->json($fleetAssigned);
       }

    }
