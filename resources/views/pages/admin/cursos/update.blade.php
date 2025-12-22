@once('JS')
	<script src="{{asset('assets/js/course-content-list.js')}}"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
	<script>
		window.initialContentData = [
			@if(isset($curso) && $curso->content)
				@foreach($curso->content as $item)
					@if($item->text)
						@php $text = trim(str_replace(["\r", "\n"], '', $item->text)); @endphp
						"{{ addslashes($text) }}",
					@endif
				@endforeach
			@endif
		];
		$(function() {
			if (window.initialContentData && window.initialContentData.length > 0 && typeof window.setInitialContentData === 'function') {
				window.setInitialContentData(window.initialContentData);
			}
		});
	</script>
@endonce

<div class="form-group">
	<div class="col-lg-12" >
		<label for="contenido" class="control-label">Contenido</label>
		<div class="input-group mb-2" id="content-input-group">
			<input type="text" class="form-control" id="content-input" placeholder="Ingrese nuevo contenido...">
			<div class="input-group-append">
				<button class="btn btn-success" id="add-content-btn" type="button">
					<span class="glyphicon glyphicon-plus"></span> Añadir
				</button>
			</div>
		</div>
		<ul class="content-list list-group mt-2"></ul>
		<small class="form-text text-muted">Puede agregar, editar, eliminar o reordenar los contenidos antes de guardar el curso.</small>
	</div>
</div>