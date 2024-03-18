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
        <h3>S.I.D.D.E.</h1>
        <br>
        <h1>{{$scheduled->course->title}}</h1>
        <h3>Lista de Participantes</h3>
        <br>
    </div>
    <div class="container-fluid">
        <table class="participant-list table table-striped table-bordered table-center">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>C.I.</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($scheduled->participants as $participant)
                    <tr>
                        <td>{{$participant->person->name}}</td>
                        <td>{{$participant->person->last_name}}</td>
                        <td>{{$participant->person->id_type_id  == 1 ? 'V' : 'E'}}-{{$participant->person->id_format()}}</td>
                        <td>{{$participant->participantStatus->name}}</td>
                    <tr>
                @endforeach
            </tbody>
        </table>
    </div>
</html>