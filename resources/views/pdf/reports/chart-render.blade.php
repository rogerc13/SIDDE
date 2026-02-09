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
        $chartJsPath = public_path('assets/js/chartjs/Chart.js');
        $chartJs = is_file($chartJsPath) ? file_get_contents($chartJsPath) : null;
    @endphp
    @if($chartJs)
        <script>{!! $chartJs !!}</script>
    @else
        <script>
            window.__CHART_READY = false;
            window.__CHART_ERROR = 'No se encontró Chart.js local en public/assets/js/chartjs/Chart.js';
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
