<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle ?? 'Reporte' }}</title>
    <style>
        @page { margin: 18px 22px; }

        * { box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2733;
            margin: 0;
        }

        /* Header is rendered once, before the page blocks. */
        .pdf-header {
            border-bottom: 2px solid #EF3E36;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .pdf-header img { max-height: 40px; }
        .pdf-header .brand {
            margin: 4px 0 0;
            font-size: 18px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #EF3E36;
        }
        .pdf-header .report-title {
            margin: 4px 0 0;
            font-size: 13px;
            font-weight: bold;
        }
        .pdf-header .meta {
            margin-top: 3px;
            font-size: 9px;
            color: #6b7684;
        }

        /* Sections flow naturally down the page; only split when needed. */
        .pdf-section { margin: 0 0 16px; }

        /* Keep a heading with its content and small blocks intact. */
        .pdf-section--chart,
        .pdf-section--summary { page-break-inside: avoid; }

        .section-title {
            page-break-after: avoid;
            margin: 0 0 8px;
            font-size: 12px;
            color: #1f2733;
        }

        .chart { text-align: center; margin: 0; }
        .chart img { max-width: 100%; height: auto; }

        .executive-summary { font-size: 11px; line-height: 1.6; }
        .executive-summary p { margin: 0 0 8px; }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }
        table.data th,
        table.data td {
            border: 1px solid #d9dde3;
            padding: 4px 6px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }
        table.data thead { display: table-header-group; }
        table.data th {
            background-color: #bdedbc;
            color: #045702;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }
        table.data tbody td:first-child { text-align: left; }
        table.data tbody tr:nth-child(even) td { background-color: #f6f8f9; }
    </style>
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
