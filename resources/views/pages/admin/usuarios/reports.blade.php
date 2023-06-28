@extends('layouts.admin')

@section('content')
    <ul>
        @foreach ($cursosProgramados as $cursoProgramado)
            <li>{{$cursoProgramado->curso}}</li>   
        @endforeach
    </ul>
    


@endsection