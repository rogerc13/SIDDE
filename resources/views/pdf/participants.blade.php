@extends('pdf.list')

@section('title', 'Lista de Participantes')
@section('subtitle', $participants->count().' registros · Generado el '.now()->format('d-m-Y H:i'))

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
            @forelse ($participants as $participant)
                <tr>
                    <td>{{ $participant->person->name }}</td>
                    <td>{{ $participant->person->last_name }}</td>
                    <td>{{ $participant->person->id_type_id == 1 ? 'V' : 'E' }}-{{ $participant->person->id_format() }}</td>
                    <td>{{ $participant->email }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No se han encontrado resultados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
