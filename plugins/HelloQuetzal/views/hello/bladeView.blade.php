@extends('includes.template')

@section('title', $title)

@section('content')
  <h1>@hello($name)</h1>
  <p class="text-muted">Vista renderizada por Blade desde el plugin <code>HelloQuetzal</code>.</p>
  <p>La directiva <code>@hello</code> la registró este mismo plugin en su <code>Init.php</code>.</p>
  <link rel="stylesheet" href="{{ plugin_asset('HelloQuetzal', 'css/hello.css') }}">

  <a href="hello" class="btn btn-outline-secondary">Volver al motor Quetzal</a>
@endsection
