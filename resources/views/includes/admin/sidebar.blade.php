@push('JS')
	<script>
		 $("#selectbusqueda").select2({
    matcher: function(term, text, opt){
        const matcher = opt.parent('select').select2.defaults.matcher;
        return matcher(term, text) || (opt.parent('optgroup').length && matcher(term, opt.parent('optgroup').attr("label")));
    }
});
	</script>
@endpush
	<div class="sidebar-menu fixed hide-print">

		<div class="sidebar-menu-inner">

			<header class="logo-env">

				<!-- logo -->
				<div class="logo">
					<a href="{{url('home')}}">
						<img src="{{url('assets/images/sidde-short-logo.png')}}" width="150" alt="" />
					</a>
				</div>

				<!-- logo collapse icon -->
				<div class="sidebar-collapse">
					<a href="#" class="sidebar-collapse-icon with-animation"><!-- add class "with-animation" if you want sidebar to have animation during expanding/collapsing transition -->
						<i class="entypo-menu"></i>
					</a>
				</div>


				<!-- open/close menu icon (do not remove if you want to enable menu on mobile devices) -->
				<div class="sidebar-mobile-menu visible-xs">
					<a href="#" class="with-animation"><!-- add class "with-animation" to support animation -->
						<i class="entypo-menu"></i>
					</a>
				</div>

			</header>

			<div class="sidebar-user-info">

				<div class="sui-normal">
					<a href="#" class="user-link">

					@if(!$logeado->imagen)
                    	<img src="{{url('assets/images/photo.jpg')}}" width="55" alt="" class="img-circle">
                  	@else
                  		<img src="{{url('uploads/perfiles/'.$logeado->imagen)}}" width="55" alt="" class="img-circle">
                  	@endif

						<strong style="text-transform: capitalize;">{{$logeado->nombre}} {{$logeado->apellido}}</strong>
						<span>{{$logeado->rol->nombre}} </span>

					</a>
				</div>

				<div class="sui-hover inline-links animate-in"><!-- You can remove "inline-links" class to make links appear vertically, class "animate-in" will make A elements animateable when click on user profile -->
					<a href="{{ route('mis_datos') }}">
						<i class="entypo-pencil"></i>
						Editar Perfil
					</a>



	                    <a href="{{ route('logout') }}"
	                        onclick="event.preventDefault();
	                                 document.getElementById('logout-form').submit();">
	                       	<i class="entypo-lock"></i>
	                        Cerrar sesion
	                    </a>

	                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
	                        {{ csrf_field() }}
	                    </form>

					<span class="close-sui-popup">×</span><!-- this is mandatory -->				</div>
			</div>

			<ul id="main-menu" class="main-menu">
				<!-- add class "multiple-expanded" to allow multiple submenus to open -->
				<!-- class "auto-inherit-active-class" will automatically add "active" class for parent elements who are marked already with class "active" -->

				<!-- <li class="opened active has-sub root-level"> -->

                @can('getAccionFormacion','App/Curso')
                    <li class="sidebar-selector " title="" id="sidebar-busqueda">
                        <a>
                            <i class="entypo-search" id="siderbar-buscar"></i>
                            <span class="title buscar"> Buscar acciones de formación</span>

                            <label for="selectbusqueda"></label><select id="selectbusqueda" data-allow-clear="true">
                                    <option></option>
                                    @foreach($categoriasAcciones as $categ)
                                        <optgroup label="{{$categ->nombre}}">
                                        @foreach($categ->cursos as $af)
                                            <option value="{{url("acciones_formacion/$af->id")}}">{{$af->titulo}}</option>
                                        @endforeach
                                        </optgroup>
                                    @endforeach --}}
                                </select>
                        </a>
                    </li>
                @endcan
			{{--<li class="has-sub">
					<a href="index.html">
						<i class="entypo-book"></i>
						<span class="title">Acciones de Formación</span>
					</a>

					<ul>

						@foreach($categoriasAcciones as $categ)
							<li class="has-sub">
								<a href="">
									<span class="title">{{$categ->nombre}}</span>
								</a>
								<ul>
									@foreach($categ->cursos as $af)
										<li>
											<a href="{{url("acciones_formacion/$af->id")}}">
												<span class="title">{{$af->titulo}}</span>
											</a>
										</li>
									@endforeach
								</ul>
							</li>
						@endforeach
					</ul>
				</li> --}}

			@can('getAll','App\Categoria')
				<li class="{{ Request::is('u/areas') ? 'active' : '' }} " title="Gestión de Áreas de Conocimiento">
					<a href="{{url("u/areas")}}">
						<i class="entypo-tag"></i>
						<span class="title">Áreas de Conocimiento</span>
					</a>
				</li>
			@endcan
			@can('getAll','App\Curso')
				<li class="{{ Request::is('u/acciones_formacion') ? 'active' : '' }}  " title="Gestión de Acciones de Formación">
					<a href="{{url("u/acciones_formacion")}}">
						<i class="entypo-book-open"></i>
						<span class="title">Gestión de A.F</span>
					</a>
				</li>
			@endcan
			@can('getAll','App\CursoProgramado')
				<li class="@if(Request::is('u/af_programadas') || Request::is('u/af_programadas/*')) active @endif" title="Acciones de Formación Programadas">
					<a href="{{url("u/af_programadas")}}">
						<i class="entypo-calendar"></i>
						<span class="title">A.F Programadas</span>
					</a>
				</li>
			@endcan
			@can('getAll','App\User')
				<li class="{{ Request::is('u/usuarios') ? 'active' : '' }}" title="Gestión de Usuarios">
					<a href="{{url("u/usuarios")}}">
						<i class="entypo-users"></i>
						<span class="title">Usuarios</span>
					</a>
				</li>
			@endcan
			@can('getAllFacilitador','App\User')
				<li class=" {{ Request::is('u/facilitadores') ? 'active' : '' }}" title="Gestión de Facilitadores">
					<a href="{{url("u/facilitadores")}}">
						<i class="entypo-users"></i>
						<span class="title">Facilitadores</span>
					</a>
				</li>
			@endcan
			@can('getAllParticipante','App\User')
				<li class="{{ Request::is('u/participantes') ? 'active' : '' }} " title="Gestión de Participantes">
					<a href="{{url("u/participantes")}}">
						<i class="entypo-users"></i>
						<span class="title">Participantes</span>
					</a>
				</li>
			@endcan

			@can('misCursos','App\CursoProgramado')
				<li class="{{ Request::is('u/mis_acciones') ? 'active' : '' }} " title="Mis acciones de formación">
					<a href="{{url("u/mis_acciones")}}">
						<i class="entypo-list"></i>
						<span class="title">Mis acciones de formación</span>
					</a>
				</li>
			@endcan



			</ul>

		</div>

	</div>
