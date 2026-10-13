<td class="database-col-actions" style="white-space: nowrap;">
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
			<i class="entypo-pencil" aria-hidden="true"></i>
			<span class="sr-only">Editar acción de formación</span>
		</a>
		@if($deletedAt)
			<form method="POST" action="{{ route('database.courses.restore', ['id' => $rowId]) }}" style="display:inline-block; margin:0;" onsubmit="return confirm('¿Deseas restaurar este curso?');">
				@csrf
				@method('PATCH')
				<button type="submit" class="btn btn-success btn-xs" title="Restaurar curso" aria-label="Restaurar curso">
					<i class="entypo-ccw" aria-hidden="true"></i>
				</button>
			</form>
			<form method="POST" action="{{ route('database.courses.forceDestroy', ['id' => $rowId]) }}" style="display:inline-block; margin:0;" onsubmit="return confirm('ELIMINAR PERMANENTEMENTE: esto borrará el curso y todos los datos vinculados (programadas, inscripciones, archivos, contenidos, capacidades, prerrequisitos). ¿Deseas continuar?');">
				@csrf
				@method('DELETE')
				<button type="submit" class="btn btn-danger btn-xs" title="Eliminar permanentemente" aria-label="Eliminar permanentemente">
					<i class="entypo-trash" aria-hidden="true"></i>
				</button>
			</form>
		@else
			<form method="POST" action="{{ route('database.courses.destroy', ['id' => $rowId]) }}" style="display:inline-block; margin:0;" onsubmit="return confirm('¿Deseas eliminar (soft delete) este curso?');">
				@csrf
				@method('DELETE')
				<button type="submit" class="btn btn-danger btn-xs" title="Eliminar curso" aria-label="Eliminar curso">
					<i class="entypo-trash" aria-hidden="true"></i>
				</button>
			</form>
		@endif
	@endif
</td>
