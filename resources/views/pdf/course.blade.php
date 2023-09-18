<html>
<head>
	<link rel="stylesheet" href="{{public_path('assets/css/bootstrap.css')}}">
</head>
<style>
    .ficha-red-line{
    border: 1px solid red;
    }
    td {
        text-align: center;
    }
</style>

<div class="container-fluid">
    <div class="header">
        <img  src="{{public_path('assets/images/PDV_S.A._logo.svg')}}" alt="">
        <hr class = "ficha-red-line">
        <br>
        <h1>S.I.D.D.E.</h1>
    </div>
    <br>
    <br>
    <div>
        <h3>Acciones de Formación</h3>
    </div>
    <br>
    
    <div class="row">  
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Título</th>
                    <th>Area</th>
                    <th>Duración</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($courses as $course)
                    <tr>
                        <td>{{$course->code}}</td>
                        <td>{{$course->title}}</td>
                        <td>{{$course->category->name}}</td>
                        <td>{{$course->duration}}</td>
                    <tr>
                @endforeach
            </tbody>
        </table>       
    </div>
</div>
    <script src="{{public_path('assets/js/jquery-1.11.3.min.js')}}"></script>
    <script src="{{public_path('assets/js/bootstrap.js')}}"></script>
</html>