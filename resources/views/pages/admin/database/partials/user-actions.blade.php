<td class="database-col-actions" style="white-space: nowrap;">
	@php($rowId = data_get($row, 'id'))
	@if($rowId)
		<a
			class="btn btn-default btn-xs js-user-edit"
			title="Editar"
			href="#"
			data-toggle="modal"
			data-target="#userEditModal"
			data-id="{{ $rowId }}"
			data-update-url="{{ route('database.users.update', ['id' => 0]) }}"
			data-email="{{ e((string) data_get($row, 'email')) }}"
			data-role-id="{{ e((string) data_get($row, 'role_id')) }}"
		>
			<i class="entypo-pencil" aria-hidden="true"></i>
			<span class="sr-only">Editar usuario</span>
		</a>
	@endif
</td>
