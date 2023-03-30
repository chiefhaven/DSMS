
                  
                  <form method="POST" action="/print-reference-letter/{{$student->id}}">
                      {{ csrf_field() }}
                      <button class="dropdown-item" type="submit">Download Traffic Register Card-Reference</button>
                  </form>
                  <form method="POST" action="/print-reference-letter/{{$student->id}}">
                      {{ csrf_field() }}
                      <button class="dropdown-item" type="submit">Download Highway code 1 Aptitude-Reference</button>
                  </form>
                  <form method="POST" action="/print-reference-letter/{{$student->id}}">
                      {{ csrf_field() }}
                      <button class="dropdown-item" type="submit">Download Highway code 2 Aptitude-Reference</button>
                  </form> 