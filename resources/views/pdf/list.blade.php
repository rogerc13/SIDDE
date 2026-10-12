{{--
    Shared layout for the index "list" prints. Mirrors the reports PDF
    (single header + green table) so every print looks the same.

    Children extend this and provide:
      @section('title', '…')      heading text
      @section('subtitle', '…')   muted line under the heading
      @section('content')          the <table class="data">…
--}}
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Listado')</title>
    @include('pdf.partials.report-style')
</head>
<body>
    <div class="pdf-header">
        @if(is_file(public_path('assets/images/PDV_S.A._logo.svg')))
            <img src="{{ public_path('assets/images/PDV_S.A._logo.svg') }}" alt="">
        @endif
        <div class="brand">S.I.D.D.E.</div>
        <div class="report-title">@yield('title', 'Listado')</div>
        <div class="meta">@yield('subtitle')</div>
    </div>

    @yield('content')
</body>
</html>
