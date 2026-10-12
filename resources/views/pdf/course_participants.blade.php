@extends('pdf.list')

@section('title', $scheduled->course->title.' — Lista de Participantes')
@section('subtitle')
    Periodo: @date($scheduled->start_date) - @date($scheduled->end_date)
    · {{ count($scheduled->participants) }} registros
    · Generado el {{ now()->format('d-m-Y H:i') }}
@endsection

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th scope="col">Nombre</th>
                <th scope="col">Apellido</th>
                <th scope="col">C.I.</th>
                <th scope="col">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($scheduled->participants as $participant)
                <tr>
                    <td>{{ $participant->person->name }}</td>
                    <td>{{ $participant->person->last_name }}</td>
                    <td>{{ $participant->person->id_type_id == 1 ? 'V' : 'E' }}-{{ $participant->person->id_format() }}</td>
                    <td>{{ $participant->participantStatus->name }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No se han encontrado resultados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
