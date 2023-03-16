<!DOCTYPE html>
<html>
<head>
	@include('includes.admin.head')
</head>
<body class="page-body " data-url="">

	<div class="page-container"><!-- add class "sidebar-collapsed" to close sidebar by default, "chat-visible" to make chat appear always -->

		<div class="main-content">
			@yield('content')


		</div>
	</div>
	@include('includes.admin.scripts')

</body>
</html>
