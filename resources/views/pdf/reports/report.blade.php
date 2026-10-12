<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle ?? 'Reporte' }}</title>
    @include('pdf.partials.report-style')
</head>
<body>
    <div class="pdf-header">
        @if(is_file(public_path('assets/images/PDV_S.A._logo.svg')))
            <img src="{{ public_path('assets/images/PDV_S.A._logo.svg') }}" alt="">
        @endif
        <div class="brand">S.I.D.D.E.</div>
        <div class="report-title">{{ $reportTitle ?? 'Reporte' }}</div>
        <div class="meta">Generado el {{ now()->format('d-m-Y H:i') }}</div>
    </div>

    @foreach($pages as $page)
        <section class="pdf-section pdf-section--{{ $page['kind'] ?? 'table' }}">
            @switch($page['kind'] ?? '')
                @case('chart')
                    @if(!empty($page['title']))
                        <h2 class="section-title">{{ $page['title'] }}</h2>
                    @endif
                    <div class="chart">
                        <img src="{{ $page['image'] ?? '' }}" alt="{{ $page['title'] ?? 'Gráfico del reporte' }}">
                    </div>
                    @break

                @case('summary')
                    <h2 class="section-title">{{ $page['title'] ?? 'Resumen Ejecutivo' }}</h2>
                    <div class="executive-summary">
                        @foreach(($page['lines'] ?? []) as $line)
                            <p>{{ $line }}</p>
                        @endforeach
                    </div>
                    @break

                @case('table')
                    @if(!empty($page['title']))
                        <h2 class="section-title">{{ $page['title'] }}</h2>
                    @endif
                    <table class="data">
                        <thead>
                        <tr>
                            @foreach(($page['columns'] ?? []) as $col)
                                <th scope="col">{{ $col }}</th>
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
                    @break
            @endswitch
        </section>
    @endforeach
</body>
</html>
