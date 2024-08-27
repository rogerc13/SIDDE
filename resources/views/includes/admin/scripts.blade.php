	<!-- Imported styles on this page -->
	{{-- <link rel="stylesheet" href="{{url('assets/js/jvectormap/jquery-jvectormap-1.2.2.css')}}">
	<link rel="stylesheet" href="{{url('assets/js/rickshaw/rickshaw.min.css')}}"> --}}
	<script src="{{url('assets/js/select2/select2.min.js')}}"></script>

	<!-- Bottom scripts (common) -->
	<script src="{{url('assets/js/gsap/TweenMax.min.js')}}"></script>
	<script src="{{url('assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js')}}"></script>
	<script src="{{url('assets/js/bootstrap.js')}}"></script>
	<script src="{{url('assets/js/joinable.js')}}"></script>
	<script src="{{url('assets/js/resizeable.js')}}"></script>
	<script src="{{url('assets/js/neon-api.js')}}"></script>
	{{-- <script src="{{url('assets/js/jvectormap/jquery-jvectormap-1.2.2.min.js')}}"></script> --}}


	<!-- Imported scripts on this page -->
	{{-- <script src="{{url('assets/js/jvectormap/jquery-jvectormap-europe-merc-en.js')}}"></script> --}}
	{{--<script src="{{url('assets/js/jquery.sparkline.min.js')}}"></script>--}}
	{{-- <script src="{{url('assets/js/rickshaw/vendor/d3.v3.js')}}"></script>
	<script src="{{url('assets/js/rickshaw/rickshaw.min.js')}}"></script>
	<script src="{{url('assets/js/raphael-min.js')}}"></script>
	<script src="{{url('assets/js/morris.min.js')}}"></script> --}}
	<script src="{{url('assets/js/toastr.js')}}"></script>
	<script src="{{url('assets/js/fullcalendar/fullcalendar.min.js')}}"></script>
	<script src="{{url('assets/js/neon-chat.js')}}"></script>
	<script src="{{url('assets/js/zurb-responsive-tables/responsive-tables.js')}}"></script>


	<!-- JavaScripts initializations and stuff -->
	<script src="{{url('assets/js/neon-custom.js')}}"></script>


	<!-- Demo Settings -->
	<script src="{{url('assets/js/neon-demo.js')}}"></script>

	<script>
		
	    /*function redireccion(){  //Never used       
	    	console.log("asd");
    	}*/ 

    	$( document ).ready(function() {
    		$('#selectbusqueda').on("change", function(e) {     
    			 
    			if(e.added){
    				location.href = e.val;
    			}
          		
        	})
		});

		$("#siderbar-buscar").on("click", function (ev) { //sidebar-buscar refers to search icon, this on-click event collapses the sidebar

			ev.preventDefault();

			var with_animation = $(this).hasClass('with-animation');

        	
            toggle_sidebar_menu(true);
       });

	</script>

	@stack('JS')