<p>Your assistance rendered to 
        @if($student->gender == "Male")
            him
        @elseif($student->gender == "Female")
            her
        @else
            him/her
        @endif
     will be highly appreciated.<br><br>

    Yours Faithfully<br>    
    <img src="{{ public_path("media/signatures/{$setting->authorization_signature}") }}" alt="" style="width: auto; height: 20px;"><br>
    <b>Chimwemwe Mboma</b><br>Director<br>Cell: +265 999 532 688/884 511 827<br>Email: chimwemwemboma@darondrivingschool.com</p>
</div>
</div>
</div>

</body>
</html>