<div class="row ">
    <div class="col-md-12">
        <table class="table ficha-table">
        <thead>
            <tr scope="row" colspan="4">
                <img src="{{asset('assets/images/PDV_S.A._logo.svg')}}" alt="">
            </tr>
        </thead>
        <hr class = "ficha-red-line">
        <tbody>
            <tr>
                <th scope="row" class="no-border-top"><p class="ficha-course-code">Código:<p></th>
                <td colspan="4" class="no-border-top"><p class="ficha-course-code">{{$curso->code}}</p></td>
            </tr>
            <tr>
                <th scope="row" colspan="5" class="no-border-top"><p class="ficha-course-title">{{$curso->title}}</p></th>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Área de conocimiento</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-categoria-name">{{$curso->category->name}}</p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Sub-área de conocimiento</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-sub-area ficha-course-categoria-name">{{$curso->category->name}}</p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Modalidad</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-modalidad ficha-course-categoria-name">{{$curso->modality->name}}</p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Objetivo</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-objective">{{$curso->objective}}</p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Contenido</p></td>
                <td colspan="4" rowspan="1" class="ficha-course-content">
                    {{--needs style--}}
                    @if($curso->content->count() >= 1)
                        <ul>
                            @foreach ($curso->content as $content)
                                <li>
                                    <span class="ficha-course-objective">{{$content->text}}</span>
                                </li>    
                            @endforeach
                        </ul>        
                    @else
                        <p class="list-content-span">Este Curso no posee lista de contenidos</p>
                    @endif
                </td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Duración</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-duration">{{$curso->duration}} @if($curso->duration > 1)Horas @else Hora @endif
                </p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Dirigido a</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitle-name-td"><p class="ficha-course-participant">{{$curso->addressed}}</p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-subtitle-td"><p class="ficha-course-subtitle">Acciones de formación previa</p></td>
                <td colspan="4" rowspan="1" class="ficha-subtitile-name-td"><p class="ficha-course-requirement">
                    @if (isset($curso->prerequisite[0]) && ($curso->prerequisite[0]->prerequisite_id != null)) 
                        {{$curso->prerequisite[0]->courseName()}}
                    @else 
                        Ninguna                    
                    @endif
                </p></td>
            </tr>
            <tr>
                <td colspan="1" rowspan="1" class="ficha-course-participant-capacity-subtitle-td">
                    <p class="ficha-course-subtitle">Número de participantes</p>
                </td>
                <td colspan="1" rowspan="1" class="ficha-course-participant-capacity-minimum-td">
                    <p class="ficha-course-capacity">Mínimo</p>
                </td>
                <td colspan="1" rowspan="1">
                    <p class="ficha-course-capacity-content">{{ $curso->capacity->first()->min ?? '-' }}</p>
                </td>
                <td colspan="1" rowspan="1">
                    <p class="ficha-course-capacity">Máximo</p>
                </td>
                <td colspan="1" rowspan="1">
                    <p class="ficha-course-capacity-content">{{ $curso->capacity->first()->max ?? '-' }}</p>
                </td>
            </tr>
            
        </tbody>
    </table>
    </div>  
</div>
