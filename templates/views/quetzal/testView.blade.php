@extends('includes.template')

@section('title', $title)

@section('content')
  <h1>¡Hola, {{ $name }}!</h1>
  <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Pariatur, maxime.</p>
  <p>{!! insert_inputs() !!}</p>
  <p>{{ money(123) }}</p>
  <p>{{ md5('Bienvenido') }}</p>
  <a href="{{ get_base_url() }}">Función en Blade</a>
@endsection
