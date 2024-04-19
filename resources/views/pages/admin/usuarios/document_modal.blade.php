<script>
    function documentModal(url){
        
        $("#document-modal").modal();
        
        
        $.get(url,function(data){

        })
        .done(function(data){
            data = JSON.parse(data);
            let courseId = data.files[0].course_id;
            
            
            let files = {
                type1 : false,
                type2 : false,
                type3 : false,
                type4 : false
            };

            data.files.forEach(element => {
                if(element.type_id == '1'){
                    files.type1 = true;
                }

                if(element.type_id == '2'){
                    files.type2 = true;
                }

                if(element.type_id == '3'){
                    files.type3 = true;
                }

                if(element.type_id == '4'){
                    files.type4 = true;
                }
            });

            $('.loader').removeClass('hidden');
            $('.loader').addClass('hidden');
            $('.modal-body').html(`
                <div class="btn-group row course-file-btn" >
                        <a class="btn btn-default ${(files.type1 ? '' : 'disabled')}" style='margin-right:5px' href="{{url('download/${courseId}/1')}}" role="button"><i class="fa fa-file-pdf-o"></i> Manual de Facilitador</a>
                        <a class="btn btn-default ${(files.type2 ? '' : 'disabled')}" style='margin-right:5px' href="{{url('download/${courseId}/2')}}" role="button"><i class="fa fa-file-pdf-o"></i> Manual de Usuario</a>
                        <a class="btn btn-default ${(files.type3 ? '' : 'disabled')}" style='margin-right:5px' href="{{url('download/${courseId}/3')}}" role="button"><i class="fa fa-file-pdf-o"></i> Guia</a>
                        <a class="btn btn-default ${(files.type4 ? '' : 'disabled')}" href="{{url('download/${courseId}/4')}}" role="button"><i class="fa fa-file-pdf-o"></i> Presentacion</a>    
                </div>
            `);
        })
        .fail(function(data){
            $('.loader').removeClass('hidden');
            $('.loader').addClass('hidden');

            $('.modal-body').html('Error Al Cargar Los Documentos');
        });
            
    }
</script>

<div class="modal fade" id="document-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="document-label">Documentos del Curso</h4>
            </div>
            <div class="loader text-center">
                <i class="fa fa-spinner fa-spin fa-3x fa-fw"></i>
                <span class="sr-only">Cargando...</span>
            </div>
                <div class="modal-body">                        

                </div>     
                <div class="modal-footer" style='text-align: center;'>
                    <button type="button" class="btn btn-default" data-dismiss="modal" title="Cancelar">Cerrar</button>
                </div>
        </div>
    </div>
</div>