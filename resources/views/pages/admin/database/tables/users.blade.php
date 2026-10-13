@extends('layouts.admin')
@section('content')
	@include('includes.admin.page-heading', ['icon' => 'entypo-database', 'title' => 'Base de datos'])

	<a class="btn btn-default" href="{{ route('database.index') }}">
		<i class="entypo-left-open"></i>
		Volver
	</a>

	<br>
	<br>

	@include('pages.admin.database.partials.table', $tableProps)
@endsection

@section('modals')
	<div class="modal fade" id="userEditModal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title" id="userEditModalTitle">Editar usuario</h4>
				</div>
				<div class="modal-body">
					<form id="userEditForm" method="POST" action="" class="form-horizontal">
						@csrf
						@method('PATCH')

						<div class="form-group">
							<label class="col-sm-3 control-label">Email</label>
							<div class="col-sm-9">
								<input type="email" name="email" class="form-control" required>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-3 control-label">Rol</label>
							<div class="col-sm-9">
								<select name="role_id" class="form-control" required>
									@foreach(($roles ?? []) as $role)
										<option value="{{ $role->id }}">{{ $role->name }}</option>
									@endforeach
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-3 control-label">Contraseña</label>
							<div class="col-sm-9">
								<input type="password" name="password" class="form-control" placeholder="Dejar en blanco para no cambiar">
							</div>
						</div>

						<div class="form-group">
							<div class="col-sm-offset-3 col-sm-9">
								<button type="submit" class="btn btn-primary">Guardar</button>
								<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
@endsection

@push('JS')
	<script>
		(function () {
			function replaceTrailingId(url, id) {
				return String(url).replace(/\/(0|__ID__)$/, '/' + id);
			}

			$(document).on('click', '.js-user-edit', function (e) {
				e.preventDefault();

				var $btn = $(this);
				var id = $btn.data('id');
				var updateUrl = replaceTrailingId($btn.data('update-url'), id);

				var email = $btn.data('email') || '';
				var roleId = $btn.data('role-id');

				var $form = $('#userEditForm');
				$form.attr('action', updateUrl);
				$('#userEditModalTitle').text('Editar usuario (ID: ' + id + ')');
				$form.find('input[name="email"]').val(email);
				$form.find('input[name="password"]').val('');
				$form.find('select[name="role_id"]').val(String(roleId));
			});
		})();
	</script>
@endpush
