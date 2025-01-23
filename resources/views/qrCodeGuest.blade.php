<x-guest-layout>
    <div class="flex justify-center items-center min-h-screen bg-gray-100">
        <div class="content text-center p-6 bg-white rounded shadow-lg">
            <div class="text-uppercase font-bold text-lg mb-4">
                @if(isset($student))
                    This is a valid document from Daron Driving School for
                    <span class="text-blue-600">{{ $student->fname }} {{ $student->mname }} {{ $student->sname }}</span>!
                @else
                    <span class="text-red-600">Document or URL not valid!</span>
                @endif
            </div>
            <div class="text-sm text-gray-700 mt-4">
                <hr class="my-4">
                For more information, contact us on: <br>
                <strong>Phone:</strong> +265 999 532 688 | +265 887 226 317<br>
                <strong>Email:</strong> <a href="mailto:info@darondrivingschool.com" class="text-blue-500 underline">info@darondrivingschool.com</a>
            </div>
            <p class="text-xs text-gray-500 mt-6">
                System supported by
                <a href="https://www.havenplustechnologies.co.mw" class="text-blue-500 underline">
                    HavenPlus Technologies
                </a>
            </p>
        </div>
    </div>
</x-guest-layout>
