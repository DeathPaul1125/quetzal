{{-- header.blade.php --}}
<!DOCTYPE html>
<html>

<head>
  <base href="{{ get_base_url() }}">
  <title>@yield('title', $title ?? '')</title>

  @include('includes.styles')
</head>
<body>
