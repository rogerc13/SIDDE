<!DOCTYPE html>
<html>
<head>

<div class="login-headbar">
	<img class="" src="{{url('assets/images/pdvsa-logo.png')}}" height="45px">
</div>


	@include('includes.admin.head')

</head>
<body class="page-body login-page login-form-fall" data-url="">
	
				
	<div class="login-container">

		<div class="login-header">
			<img class="img-responsive" src="{{url('assets/images/sidde-logo-4.png')}}" style="width: 560px; margin: auto;" alt="" />
			<div class="login-content">
			
					
				
				<p class="description">Estimado usuario, debe iniciar sesión para acceder al panel de control!</p>
				
			</div>			
		</div>

		<div class="login-form2">
			<div class="login-content">
				@yield('content')
				
					
			</div>
		</div>
	</div>

	@include('includes.admin.scripts')

</body>
<footer class="main">
			<p align="center">
			&copy; 2022 <strong>SIDDE</strong>
			</p>
		</footer>
</html>