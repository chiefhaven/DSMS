<x-guest-layout>
    <x-slot name="logo">
        <h1 style="font-size: 2.7em; text-align:center; color:aliceblue"><strong>{{ env('APP_NAME')}}</strong></h1>
</x-slot>
    <div class="block-content">
    <div class="content">
        <p class="text-uppercase">This is a Daron Driving School document, <br>and contains details of one of our students!</p>
    <p>
        For more information contact us on <br>
        Phone: +265 999 532 688 | +265 887 226 317<br>
        Email: info@darondrivingschool.com
    </p>
    </div>
</div>
<x-slot name="logo">
    <h1 style="font-size: 2.7em; text-align:center; color:aliceblue"><strong>{{ env('APP_NAME')}}</strong></h1>
</x-slot>
</x-guest-layout>
