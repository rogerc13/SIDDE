<!DOCTYPE html>
<html>
<head>

	@include('includes.admin.head')
</head>
<body class="page-body page-fade-only skin-red">

	<div class="page-container"><!-- add class "sidebar-collapsed" to close sidebar by default, "chat-visible" to make chat appear always -->

		@include('includes.admin.sidebar')
		<div class="main-content">

			<div class="row">
				@if (session('alert'))
					<div class="col-md-12">
						<div class="alert alert-dismissable alert-{{session('alert')["tipo"]}} fade in">
							<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
							<h4 style="font-weight: bolder;">{{session('alert')["titulo"]}}</h4>
							<p>{{session('alert')["mensaje"]}}</p>
						</div>
					</div>
				@endif

				@if (count($errors) > 0)
	                <div class="alert alert-danger col-xs-12 no-padding" style="margin-top: 15px;">
	                   	<div class="callout callout-danger">
	                        <h4>Error al procesar los datos</h4>
                            <p>
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </p>
	                    </div>
                    </div>
                @endif
			</div>

			@yield('content')

			@include('includes.admin.footer')
		</div>
	</div>
	@include('includes.admin.scripts')

	@yield('modals')


</body>
</html>
