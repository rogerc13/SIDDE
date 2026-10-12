@extends('pdf.list')

@section('title', 'Lista de Áreas de Conocimiento')
@section('subtitle', $categories->count().' registros · Generado el '.now()->format('d-m-Y H:i'))

@section('content')
    <table class="data">
        <thead>
            <tr>
                <th scope="col">Nombre</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                </tr>
            @empty
                <tr>
                    <td>No se han encontrado resultados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
