@extends('pdf.list')

@section('title', 'Lista de Aulas')
@section('subtitle', $locations->count().' registros · Generado el '.now()->format('d-m-Y H:i'))

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th scope="col">Aula</th>
                <th scope="col">Piso</th>
                <th scope="col">Edificio</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($locations as $location)
                <tr>
                    <td>{{ $location->name }}</td>
                    <td>{{ $location->floor->name ?? '—' }}</td>
                    <td>{{ $location->floor->building->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No se han encontrado resultados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
