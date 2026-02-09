<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte</title>
    <link rel="stylesheet" href="{{ public_path('assets/css/bootstrap.css') }}">
    <style>
        @page { margin: 18px 18px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .ficha-red-line { border: 1px solid red; }
        h2 { font-size: 14px; margin: 0 0 10px 0; }
        .chart img { width: 94%; height: auto; display: block; margin: 0 auto; }
        /* Match existing index PDF table styling */
        th, td {
            text-align: center;
        }
        .table > tbody > tr > td {
            vertical-align: middle !important;
            font-size: 14px;
        }
        th {
            background-color: #EF3E36 !important;
            color: white;
        }
        tr { page-break-inside: avoid; }
    </style>
</head>
<body>
@foreach($pages as $page)
    <div class="page">
        <div class="header">
            <img src="{{ public_path('assets/images/PDV_S.A._logo.svg') }}" alt="">
            <hr class="ficha-red-line">
            <br>
            <h1>S.I.D.D.E.</h1>
            <h3>{{ $page['title'] ?? ($reportTitle ?? 'Reportes') }}</h3>
            <br>
        </div>
        @if(($page['kind'] ?? '') === 'chart')
            <div class="chart">
                <img src="{{ $page['image'] ?? '' }}" alt="chart">
            </div>
        @elseif(($page['kind'] ?? '') === 'table')
            <table class="table table-striped table-bordered table-center">
                <thead>
                <tr>
                    @foreach(($page['columns'] ?? []) as $col)
                        <th>{{ $col }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                @foreach(($page['rows'] ?? []) as $row)
                    <tr>
                        @foreach($row as $cell)
                            @if(is_array($cell))
                                <td @if(!empty($cell['colspan'])) colspan="{{ (int) $cell['colspan'] }}" @endif>
                                    {{ $cell['text'] ?? '' }}
                                </td>
                            @else
                                <td>{{ $cell }}</td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endforeach
</body>
</html>
