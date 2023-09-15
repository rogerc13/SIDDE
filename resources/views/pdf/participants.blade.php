<head>
	<link rel="stylesheet" href="{{public_path('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css')}}">
	<link rel="stylesheet" href="{{public_path('assets/css/font-icons/entypo/css/entypo.css')}}">
	
	<link rel="stylesheet" href="{{public_path('assets/css/bootstrap.css')}}">
	<link rel="stylesheet" href="{{public_path('assets/css/neon-core.css')}}">
	<link rel="stylesheet" href="{{public_path('assets/css/neon-theme.css')}}">
	<link rel="stylesheet" href="{{public_path('assets/css/neon-forms.css')}}">
	<link rel="stylesheet" href="{{public_path('assets/css/font-icons/font-awesome/css/font-awesome.min.css')}}">
	<link rel="stylesheet" href="{{public_path('assets/js/zurb-responsive-tables/responsive-tables.css')}}">

	<link href="{{public_path('css/app1.css')}}" rel="stylesheet" />
	
	{{-- for morris graphs --}}
	<script src="{{public_path('assets/js/jquery-1.11.3.min.js')}}"></script>

	<link rel="stylesheet" href="{{public_path('assets/js/select2/select2-bootstrap.css')}}">
	<link rel="stylesheet" href="{{public_path('assets/js/select2/select2.css')}}">

	<link rel="stylesheet" href="{{public_path('assets/js/wysihtml5/bootstrap-wysihtml5.css')}}">
	
	<link rel="stylesheet" href="{{public_path('assets/css/custom.css')}}">
</head>

<div class="panel panel-success course-list">
    <div class="panel-heading">
        <div class="panel-title">Lista de Participantes</div>
    </div>         
    <div class="panel-body with-table table-responsive">   
    <table class="participant-list table table-striped table-bordered table-center">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>C.I.</th>
                <th>Correo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($participants as $participant)
                <tr>
                    <td>{{$participant->person->name}}</td>
                    <td>{{$participant->person->last_name}}</td>
                    <td>{{$participant->person->id_type_id  == 1 ? 'V' : 'E'}}-{{$participant->person->id_format()}}</td>
                    <td>{{$participant->email}}</td>
                <tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>

<footer>
<script src="{{public_path('assets/js/select2/select2.min.js')}}"></script>
<!-- Bottom scripts (common) -->
<script src="{{public_path('assets/js/gsap/TweenMax.min.js')}}"></script>
<script src="{{public_path('assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js')}}"></script>
<script src="{{public_path('assets/js/bootstrap.js')}}"></script>
<script src="{{public_path('assets/js/joinable.js')}}"></script>
<script src="{{public_path('assets/js/resizeable.js')}}"></script>
<script src="{{public_path('assets/js/neon-api.js')}}"></script>

<!-- Imported scripts on this page -->

<script src="{{public_path('assets/js/jquery.sparkline.min.js')}}"></script>

<script src="{{public_path('assets/js/toastr.js')}}"></script>
<script src="{{public_path('assets/js/fullcalendar/fullcalendar.min.js')}}"></script>
<script src="{{public_path('assets/js/neon-chat.js')}}"></script>
<script src="{{public_path('assets/js/zurb-responsive-tables/responsive-tables.js')}}"></script>
<!-- JavaScripts initializations and stuff -->
<script src="{{public_path('assets/js/neon-custom.js')}}"></script>
<!-- Demo Settings -->
<script src="{{public_path('assets/js/neon-demo.js')}}"></script>
</footer>