@extends('layouts.admin')

@section('content')

    <div class="row" >
    </div>

    <div id="byDate"></div>
    <script>
        new Morris.Line({
        element: 'byDate',
        data: [
            { year: '2008', value: 20 },
            { year: '2009', value: 10 },
            { year: '2010', value: 5 },
            { year: '2011', value: 5 },
            { year: '2012', value: 20 }
        ],
        xkey: 'year',
        ykeys: ['value'],
        labels: ['Time']
    });
    </script>
@endsection