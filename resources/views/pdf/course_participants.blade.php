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
        <h2>S.I.D.D.E.</h2>
        <br>
        <h2>{{$scheduled->course->title}}</h2>
        <h4>Periodo: {{ \Carbon\Carbon::parse($scheduled->start_date)->format('d-m-Y') }} - {{ \Carbon\Carbon::parse($scheduled->end_date)->format('d-m-Y') }}</h4>
        <br>
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
                @if(count($scheduled->participants) == 0)
                    <tr>
                        <td colspan="4">No se han encontrado resultados.</td>
                    </tr>
                @else
                    @foreach ($scheduled->participants as $participant)
                        <tr>
                            <td>{{$participant->person->name}}</td>
                            <td>{{$participant->person->last_name}}</td>
                            <td>{{$participant->person->id_type_id  == 1 ? 'V' : 'E'}}-{{$participant->person->id_format()}}</td>
                            <td>{{$participant->participantStatus->name}}</td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</html>