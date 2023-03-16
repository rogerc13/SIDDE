@push('JS')
<script>
    function editarParticipante(url){

        $(".loader").removeClass("hidden");
        $("#participante-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#participante-label").html("Editar participante");
        $("#participante-form").attr("action", url);

        $("[id=password]").prop('required',false);
        $("[id=password_confirmation]").prop('required',false);

        $.get(url,function(data,status){
                data=JSON.parse(data);
                $('#nombre').val(data.nombre);  
                $('#apellido').val(data.apellido); 
                $('#email').val(data.email);
                $('#ci').val(data.ci);
                $('#user_id').val(data.id);                 
                $(".loader").addClass("hidden");
                $("#participante-form").removeClass("hidden");
                
            });

        $("#participante-modal").modal();


        $("#participante-modal").on("hidden.bs.modal", function () {
        	
        	$("[id=password]").prop('required',true );
        	$("[id=password_confirmation]").prop('required',true);
        	
            $("#participante-form :input").prop('readonly', false);
            $( "#participante-form select" ).prop('disabled', false);
            $("#participante-aceptar").removeClass("hidden");
       });

    }
    
</script>
@endpush