@extends('layouts.admin')
@section('content')
	<h3>Administrador <small>Base de datos</small></h3>

	<a class="btn btn-default" href="{{ route('database.index') }}">
		<i class="entypo-left-open"></i>
		Volver
	</a>

	<br>
	<br>

	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-success" data-collapsed="0">
				<div class="panel-heading">
					<div class="panel-title">{{ $table }}</div>
				</div>

				<div class="panel-body with-table table-responsive">
					<table class="table table-striped table-bordered table-center">
						<thead>
							<tr>
								@foreach($columns as $column)
									<th>{{ $column }}</th>
								@endforeach
								<th><i class="fa fa-cogs"></i></th>
							</tr>
						</thead>
						<tbody>
							@if($rows->count() === 0)
								<tr>
									<td colspan="{{ count($columns) + 1 }}">No hay registros para mostrar.</td>
								</tr>
							@endif

							@foreach($rows as $row)
								<tr>
									@foreach($columns as $column)
										<td>{{ data_get($row, $column) }}</td>
									@endforeach
									<td>
										@php($rowId = data_get($row, 'id'))
										@php($deletedAt = data_get($row, 'deleted_at'))
										@if($rowId)
											<a
												class="btn btn-default btn-xs js-course-edit"
												title="Editar"
												href="#"
												data-toggle="modal"
												data-target="#courseEditModal"
												data-id="{{ $rowId }}"
												data-update-url="{{ route('database.courses.update', ['id' => 0]) }}"
												data-code="{{ e((string) data_get($row, 'code')) }}"
												data-title="{{ e((string) data_get($row, 'title')) }}"
												data-objective="{{ e((string) data_get($row, 'objective')) }}"
												data-duration="{{ e((string) data_get($row, 'duration')) }}"
												data-addressed="{{ e((string) data_get($row, 'addressed')) }}"
												data-category-id="{{ e((string) data_get($row, 'category_id')) }}"
												data-modality-id="{{ e((string) data_get($row, 'modality_id')) }}"
												data-deleted="{{ $deletedAt ? 1 : 0 }}"
											>
												<i class="entypo-pencil"></i>
											</a>
											@if($deletedAt)
												<form method="POST" action="{{ route('database.courses.restore', ['id' => $rowId]) }}" style="display:inline" onsubmit="return confirm('¿Deseas restaurar este curso?');">
												@csrf
												@method('PATCH')
												<button type="submit" class="btn btn-success btn-xs" title="Restaurar curso">
													<i class="entypo-ccw"></i>
												</button>
											</form>
												<form method="POST" action="{{ route('database.courses.forceDestroy', ['id' => $rowId]) }}" style="display:inline" onsubmit="return confirm('ELIMINAR PERMANENTEMENTE: esto borrará el curso y todos los datos vinculados (programadas, inscripciones, archivos, contenidos, capacidades, prerrequisitos). ¿Deseas continuar?');">
													@csrf
													@method('DELETE')
													<button type="submit" class="btn btn-danger btn-xs" title="Eliminar permanentemente">
														<i class="entypo-trash"></i>
													</button>
												</form>
											@else
												<form method="POST" action="{{ route('database.courses.destroy', ['id' => $rowId]) }}" style="display:inline" onsubmit="return confirm('¿Deseas eliminar (soft delete) este curso?');">
												@csrf
												@method('DELETE')
												<button type="submit" class="btn btn-danger btn-xs" title="Eliminar curso">
													<i class="entypo-trash"></i>
												</button>
											</form>
											@endif
										@endif
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>

					<div align="center">{!! $rows->links() !!}</div>
				</div>
			</div>
		</div>
	</div>
@stop

@section('modals')
	<div class="modal fade" id="courseEditModal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title" id="courseEditModalTitle">Editar curso</h4>
				</div>
				<div class="modal-body">
					<form id="courseEditForm" method="POST" action="" class="form-horizontal">
						@csrf
						@method('PATCH')

						<div class="form-group">
							<label class="col-sm-2 control-label">Código</label>
							<div class="col-sm-10">
								<input type="text" name="code" class="form-control" required>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label">Título</label>
							<div class="col-sm-10">
								<input type="text" name="title" class="form-control" required>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label">Objetivo</label>
							<div class="col-sm-10">
								<textarea name="objective" class="form-control" rows="4" required></textarea>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label">Duración</label>
							<div class="col-sm-4">
								<input type="number" name="duration" class="form-control" min="0" required>
							</div>
							<label class="col-sm-2 control-label">Área</label>
							<div class="col-sm-4">
								<select name="category_id" class="form-control" required>
									@foreach(($categories ?? []) as $category)
										<option value="{{ $category->id }}">{{ $category->name }}</option>
									@endforeach
								</select>
							</div>
						</div>

						<div class="form-group">
							<label class="col-sm-2 control-label">Modalidad</label>
							<div class="col-sm-4">
								<select name="modality_id" class="form-control" required>
									@foreach(($modalities ?? []) as $modality)
										<option value="{{ $modality->id }}">{{ $modality->name }}</option>
									@endforeach
								</select>
							</div>
							<label class="col-sm-2 control-label">Dirigido</label>
							<div class="col-sm-4">
								<textarea name="addressed" class="form-control" rows="2" required></textarea>
							</div>
						</div>

						<div class="form-group" id="courseRestoreGroup" style="display:none;">
							<div class="col-sm-offset-2 col-sm-10">
								<label>
									<input type="checkbox" name="restore" value="1" checked>
									Restaurar al guardar
								</label>
							</div>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
					<button type="submit" form="courseEditForm" class="btn btn-primary">
						<i class="entypo-check"></i>
						Guardar
					</button>
				</div>
			</div>
		</div>
	</div>
@stop

@push('JS')
	<script>
		(function () {
			function replaceTrailingId(url, id) {
				return url.replace(/\/(0|__ID__)$/, '/' + id);
			}

			$(document).on('click', '.js-course-edit', function (e) {
				e.preventDefault();
				var $btn = $(this);
				var id = $btn.data('id');
				var updateUrl = replaceTrailingId($btn.data('update-url'), id);

				$('#courseEditForm').attr('action', updateUrl);
				$('#courseEditModalTitle').text('Editar curso (ID: ' + id + ')');

				$('#courseEditForm input[name="code"]').val($btn.data('code'));
				$('#courseEditForm input[name="title"]').val($btn.data('title'));
				$('#courseEditForm textarea[name="objective"]').val($btn.data('objective'));
				$('#courseEditForm input[name="duration"]').val($btn.data('duration'));
				$('#courseEditForm textarea[name="addressed"]').val($btn.data('addressed'));
				$('#courseEditForm select[name="category_id"]').val(String($btn.data('category-id')));
				$('#courseEditForm select[name="modality_id"]').val(String($btn.data('modality-id')));

				var isDeleted = String($btn.data('deleted')) === '1';
				if (isDeleted) {
					$('#courseRestoreGroup').show();
					$('#courseEditForm input[name="restore"]').prop('checked', true);
				} else {
					$('#courseRestoreGroup').hide();
					$('#courseEditForm input[name="restore"]').prop('checked', false);
				}
			});
		})();
	</script>
@endpush
