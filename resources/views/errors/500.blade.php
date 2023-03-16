@extends('layouts.error')

@section('content')

			<div class="page-error-404">
			
			
			<div class="error-symbol">
				<i class="entypo-attention"></i>
			</div>
			
			<div class="error-text">
				<h2>500</h2>
				<p>El servidor está teniendo problemas en este momento, vuelve a intentarlo.</p>
			</div>
			
			<hr />
			
			<div class="error-text">
				<a href="javascript:history.back()" class="btn btn-info btn-lg"><span> Regresar</span></a>				
			</div>
			
		</div>

@stop