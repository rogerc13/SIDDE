<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin: 0; padding: 0; }
        #capture { width: 1200px; height: 650px; display: flex; align-items: center; justify-content: center; }
        canvas { width: 1120px !important; height: 600px !important; }
    </style>
    @php
        $momentPath = public_path('assets/js/Moment.js');
        $momentJs = is_file($momentPath) ? file_get_contents($momentPath) : null;

        $chartJsPath = public_path('assets/js/chartjs/chart.umd.js');
        $chartJs = is_file($chartJsPath) ? file_get_contents($chartJsPath) : null;

        $adapterPath = public_path('assets/js/chartjs/chartjs-adapter-moment.min.js');
        $adapterJs = is_file($adapterPath) ? file_get_contents($adapterPath) : null;
    @endphp
    @if($momentJs)
        <script>{!! $momentJs !!}</script>
    @endif
    @if($chartJs)
        <script>{!! $chartJs !!}</script>
        @if($adapterJs)
            <script>{!! $adapterJs !!}</script>
        @endif
    @else
        <script>
            window.__CHART_READY = false;
            window.__CHART_ERROR = 'No se encontró Chart.js local en public/assets/js/chartjs/chart.umd.js';
        </script>
    @endif
</head>
<body>
<div id="capture">
    <canvas id="chart" width="1120" height="600"></canvas>
</div>

<script>
    window.__CHART_READY = false;
    window.__CHART_ERROR = null;
    (function(){
        try {
            const config = @json($chartConfig);
            config.options = config.options || {};
            config.options.responsive = false;
            config.options.animation = config.options.animation || {};
            config.options.animation.duration = 0;
            config.options.animation.onComplete = function(){
                window.__CHART_READY = true;
            };
            const canvas = document.getElementById('chart');
            const chart = new Chart(canvas, config);
            chart.update();
            setTimeout(function(){ window.__CHART_READY = true; }, 300);
        } catch (e) {
            window.__CHART_ERROR = String(e && e.message ? e.message : e);
        }
    })();
</script>
</body>
</html>
