@extends('pdf.list')

@section('title', 'Acciones de Formación Programadas')
@section('subtitle', $scheduled->count().' registros · Generado el '.now()->format('d-m-Y H:i'))

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th scope="col">Título</th>
                <th scope="col">Facilitador</th>
                <th scope="col">Fecha Inicio</th>
                <th scope="col">Fecha Culminación</th>
                <th scope="col">Registrados</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($scheduled as $course)
                <tr>
                    <td>{{ $course->course->title }}</td>
                    <td>{{ $course->facilitator->person->name }} {{ $course->facilitator->person->last_name }}</td>
                    <td>@date($course->start_date)</td>
                    <td>@date($course->end_date)</td>
                    <td>{{ count($course->participants) }}/{{ $course->course->capacity[0]->max ?? 0 }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No se han encontrado resultados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
