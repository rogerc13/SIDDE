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
        <h3>Usuarios</h3>
        <br>
    </div>
<div class="containter-fluid">   
    <table class="table table-striped table-bordered table-center">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>C.I.</th>
                <th>Correo</th>
                <th>Rol</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <td>{{$user->person->name}}</td>
                    <td>{{$user->person->last_name}}</td>
                    <td>{{$user->person->id_type_id  == 1 ? 'V' : 'E'}}-{{$user->person->id_format()}}</td>
                    <td>{{$user->email}}</td>
                    <td>{{$user->role->name}}</td>
                <tr>
            @endforeach
        </tbody>
    </table>
</div>
</html>