@extends('layouts.admin')

@section('content')

	<div class="titulo-ficha">
    <h3>Acción de formación</h3>
  </div>
  <br>
  <br>
<div class="row">
	<div class="col-md-12">
    <div class="panel panel-primary">
            <div class="panel-heading">
              <div class="panel-title titulo-ficha"><h3>{{$curso->titulo}}</h3></div>
            </div>

            <div class="panel-body with-table"><table class="table table-bordered">

              <tbody>
                <tr>
                  <td class="middle-align subtitulos-ficha" width="30%">Codigo</td>
                  <td class="ficha-contenido">{{$curso->codigo }}</td>
                </tr>

                <tr>
                  <td class="middle-align subtitulos-ficha" width="30%">Área de conocimiento</td>
                  <td class="ficha-contenido">{{$curso->categoria->nombre }}</td>
                </tr>

                <tr>
                  <td class="middle-align subtitulos-ficha" width="30%">Modalidad</td>
                  <td class="ficha-contenido">{{$curso->modalidad }}</td>
                </tr>

                <tr>
                  <td class="middle-align subtitulos-ficha" width="30%">Objetivo</td>
                  <td class="ficha-contenido">{!! $curso->objetivo !!}</td>
                </tr>

                <tr>
                  <td class="middle-align subtitulos-ficha" width="30%">Contenido</td>
                  <td class="ficha-contenido">{{$curso->contenido }}</td>
                </tr>

                <tr>
                  <td class="middle-align subtitulos-ficha" width="30%">Duración</td>
                  <td class="ficha-contenido">{{$curso->duracion }}</td>
                </tr>

                <tr>
                  <td class="middle-align subtitulos-ficha" width="30%">Dirigido a</td>
                  <td class="ficha-contenido">{{$curso->dirigido }}</td>
                </tr>

                <tr>
                  <td class="middle-align subtitulos-ficha" width="30%">Mínimo número de participantes</td>
                  <td class="ficha-contenido">{{$curso->min }}</td>
                </tr>

                <tr>
                  <td class="middle-align subtitulos-ficha" width="30%">Máximo número de participantes</td>
                  <td class="ficha-contenido">{{$curso->max }}</td>
                </tr>
              </tbody>
            </table></div>
          </div>

  	</div>
</div>
@stop

@section('modals')

@stop
