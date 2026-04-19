{{-- template.blade.php --}}
@include('includes.header')
@include('includes.navbar')

<main class="container py-5">
  @yield('content')
</main>

@include('includes.footer')
