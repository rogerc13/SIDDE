@push('JS')
<script>
    
        $.ajaxSetup({
            headers:{
                'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')
            }
        });
    $(document).ready(function(){
        $('#asignar-modal').on('hide.bs.modal',function(){
            $('#participante').html('');
            console.log('event');
        })
    })
    

    function asignarParticipanteLista(url,id){
        let data = {"scheduled_id":id};
        $.ajax({
            type: "post",
            url:'af_programadas/assignList',
            data: data,
            dataType: "json",
            success: function(response){
                 let values = [];
                if(response.success){
                    //console.log(response);
                    $('capacity-error-text').hide();
                    response.list.forEach(element => {
                    values.push(element.id);
                    $('#participante').append(`<option personid="${element.id}" value="${element.id}">${element.name} ${element.last_name} C.I: ${element.id_type_id == 1 ? 'V' : 'E'} - ${element.id_number.replace(/\B(?=(\d{3})+(?!\d))/g, ".")}</option>`);
                 })
                }else{
                    //console.log(response);
                    $('capacity-error-text').show();
                    $('.capacity-error-text').html(response.message)
                }
            },
            error: function(response){
            }
            
        });

        document.getElementById("asignar-form").reset(); 
        $('#participante').trigger("change");
        $(".loader").addClass("hidden");
        $("#asignar-form").removeClass("hidden");
        $("[name=_method]").val("POST");
        $("[name=curso_p_id]").val(id);
        $("#asignar-label").html("Asignar participante");
        $("#asignar-form").attr("action", url);  
        $("#asignar-modal").modal();
    }
    
</script>
@endpush

<div class="modal fade" id="asignar-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="asignar-label"></h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-refresh fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
            <form class="form-horizontal hidden" method="POST" id='asignar-form'  enctype="multipart/form-data">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="POST">                
                <input type="hidden" name="curso_p_id" id="curso_p_id" value="">

                <div class="modal-body">                        

                        <div class="form-group" >
                            <div class="col-lg-12 col-md-12" >
                                <label for="participante">Participantes</label>
                                
                                <select name="participante" class="select2 " id="participante" data-allow-clear="true" required="true">
                                    <option></option>

                                    {{-- @foreach($participantes as $participante) 
                                            <option value="{{$participante->person_id}}">{{$participante->person->name}} {{$participante->person->last_name}} C.I: {{$participante->person->id_format()}}</option>
                                    @endforeach --}}
                                </select>
                                <span id="helpBlock" class="has-error help-block capacity-error-text"></span>              
                            </div>                 
                        </div>    





                </div>     

                <div class="modal-footer" style='text-align: center;'>
                    {{ Form::submit('Aceptar', array('class' => 'btn btn-primary', 'id'=>'usuario-aceptar')) }}
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cancelar</button>
                </div>

            </form>
        </div>
    </div>
</div>