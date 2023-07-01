@push('JS')
<script>
    function detallesParticipante(url){

        $(".loader").removeClass("hidden");
        $("#participante-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#participante-label").html("Editar participante");
        $("#participante-form").attr("action", url);

        $("#participante-form :input").prop('readonly', true);
        $( "#participante-form select" ).prop('disabled', true);
        $("#participante-aceptar").addClass("hidden");

        $.get(url,function(data,status){
                data=JSON.parse(data);
                $('#nombre').val(data.person.name);  
                $('#apellido').val(data.person.last_name); 
                $('#email').val(data.email);
                $('#ci').val(data.person.id_number);               
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