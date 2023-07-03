	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="description" content="Sistema de Acciones de Formación" />
	<meta name="author" content="" />


	 <meta name="csrf-token" content="{{ Session::token() }}">

	 

	<link rel="icon" href="{{url('assets/images/logo_negro(1).ico')}}">

	<title>SIDDE</title>

	<link rel="stylesheet" href="{{url('assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css')}}">
	<link rel="stylesheet" href="{{url('assets/css/font-icons/entypo/css/entypo.css')}}">
	<!--<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">-->
	<link rel="stylesheet" href="{{url('assets/css/bootstrap.css')}}">
	<link rel="stylesheet" href="{{url('assets/css/neon-core.css')}}">
	<link rel="stylesheet" href="{{url('assets/css/neon-theme.css')}}">
	<link rel="stylesheet" href="{{url('assets/css/neon-forms.css')}}">
	<link rel="stylesheet" href="{{url('assets/css/font-icons/font-awesome/css/font-awesome.min.css')}}">
	<link rel="stylesheet" href="{{url('assets/js/zurb-responsive-tables/responsive-tables.css')}}">
		<!-- <link rel="stylesheet" href="{{url('assets/css/skins/green.css')}}"> -->

	<link href="{{url('css/app1.css')}}" rel="stylesheet" />

	<script src="{{url('assets/js/jquery-1.11.3.min.js')}}"></script>
	<script src="{{url('assets/js/morris.min.js')}}"></script>
	<script src="{{url('assets/js/raphael-min.js')}}"></script>

	<link rel="stylesheet" href="{{url('assets/js/select2/select2-bootstrap.css')}}">
	<link rel="stylesheet" href="{{url('assets/js/select2/select2.css')}}">

	<link rel="stylesheet" href="{{url('assets/js/wysihtml5/bootstrap-wysihtml5.css')}}">
	
	<link rel="stylesheet" href="{{url('assets/css/custom.css')}}">
	
	<!--[if lt IE 9]><script src="assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
		<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->


	<script src="{{asset('assets/js/ficha-tecnica.js')}}"></script>
    @stack('CSS')
