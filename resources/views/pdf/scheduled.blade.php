<html>    
    <head>
        <link rel="stylesheet" href="{{public_path('assets/css/bootstrap.css')}}">
    </head>
    <style>
        .ficha-red-line{
        border: 1px solid red;
        }
        th, td {
            text-align: center;
            
        }
        .table > tbody > tr > td {
            vertical-align: middle !important;
            font-size: 14px;
        }
        th{   
            background-color: #EF3E36 !important;
            color: white;

        }
    </style>
    <div class="header">
        <img  src="{{public_path('assets/images/PDV_S.A._logo.svg')}}" alt="">
        <hr class = "ficha-red-line">
        <br>
        <h1>S.I.D.D.E.</h1>
        <h3>Accciones de Formación Programadas</h3>
        <br>
    </div>
    <div class="container-fluid">
        <table class="table table-bordered">
        <thead>
            <tr>
                <th>Título</th>
                <th>Facilitador</th>
                <th>Fecha Inicio</th>
                <th>Fecha Culminación</th>
                <th>Registrados</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($scheduled as $course)
                <tr>
                    <td>{{$course->course->title}}</td>
                    <td>{{$course->facilitator->person->name}} {{$course->facilitator->person->last_name}}</td>
                    <td>{{$course->start_date}}</td>
                    <td>{{$course->end_date}}</td>
                    <td>{{count($course->participants)}}/{{$course->course->capacity[0]->max}}</td>
                </tr>
            @endforeach
        </tbody>
        </table>
        
    </div>
    
    <script src="{{public_path('assets/js/jquery-1.11.3.min.js')}}"></script>
    <script src="{{public_path('assets/js/bootstrap.js')}}"></script>

</html>