@extends('pdf.list')

@section('title', 'Lista de Facilitadores')
@section('subtitle', $facilitators->count().' registros · Generado el '.now()->format('d-m-Y H:i'))

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th scope="col">Nombre</th>
                <th scope="col">Apellido</th>
                <th scope="col">C.I.</th>
                <th scope="col">Correo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($facilitators as $facilitator)
                <tr>
                    <td>{{ $facilitator->person->name }}</td>
                    <td>{{ $facilitator->person->last_name }}</td>
                    <td>{{ $facilitator->person->id_type_id == 1 ? 'V' : 'E' }}-{{ $facilitator->person->id_format() }}</td>
                    <td>{{ $facilitator->email }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No se han encontrado resultados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
