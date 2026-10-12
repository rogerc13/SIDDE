@extends('pdf.list')

@section('title', 'Usuarios')
@section('subtitle', $users->count().' registros · Generado el '.now()->format('d-m-Y H:i'))

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th scope="col">Nombre</th>
                <th scope="col">Apellido</th>
                <th scope="col">C.I.</th>
                <th scope="col">Correo</th>
                <th scope="col">Rol</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>{{ $user->person->name }}</td>
                    <td>{{ $user->person->last_name }}</td>
                    <td>{{ $user->person->id_type_id == 1 ? 'V' : 'E' }}-{{ $user->person->id_format() }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role->name }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No se han encontrado resultados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
