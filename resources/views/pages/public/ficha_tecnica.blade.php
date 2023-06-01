@extends('layouts.admin')

@section('content')

<style>
        .ficha-tecnica {
            width: 210mm;
            height:297mm;

            margin-left:25mm !important;
            margin-right:25mm !important;
        }
</style>

<div class="ficha-tecnica">
        
        <table class="table">
            <thead>
                <tr scope="row" colspan="4">
                    <img src="C:\xampp\htdocs\SIDDE\public\assets\images\PDV_S.A._logo.svg" alt="">
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th scope="row">Código:{{$curso->codigo}}</th>
                    <td colspan="4"></td>
                </tr>
                <tr>
                    <th scope="row" colspan="4">{{$curso->titulo}}</th>
                </tr>
                <tr>
                    <th scope="row">Área de conocimiento</th>
                    <td colspan="4">Sociopolítica</td>
                </tr>
                <tr>
                    <th scope="row">Sub-área de conocimiento</th>
                    <td colspan="4">Sociopolítica</td>
                </tr>
                <tr>
                    <th scope="row">Modalidad</th>
                    <td colspan="4">{{$curso->modalidad}}</td>
                </tr>
                <tr>
                    <th scope="row">Objetivo</th>
                    <td colspan="4">{{$curso->objetivo}}</td>
                </tr>
                <tr>
                    <th scope="row">Contenido</th>
                    <td colspan="4">{{$curso->contenido}}</td>
                </tr>
                <tr>
                    <th scope="row">Duración</th>
                    <td colspan="4">{{$curso->duracion}}</td>
                </tr>
                <tr>
                    <th scope="row">Dirigido a</th>
                    <td colspan="4">{{$curso->dirigido}}</td>
                </tr>
                <tr>
                    <th scope="row">Acciones de formación previa</th>
                    <td colspan="4"><p>Ninguna</p></td>
                </tr>
                <tr>
                    <th scope="row">Número de participantes</th>
                    <td><p>Mínimo</p></td>
                    <td><p>{{$curso->min}}</p></td>
                    <td><p>Máximo</p></td>
                    <td><p>{{$curso->max}}</p></td>
                </tr>
                
            </tbody>
        </table>
    </div>

@stop

@section('modals')

@stop
