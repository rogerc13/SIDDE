@push('JS')
<script>
    function editarFacilitador(url){

        $(".loader").removeClass("hidden");
        $("#facilitador-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#facilitador-label").html("Editar facilitador");
        $("#facilitador-form").attr("action", url);

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
                $("#facilitador-form").removeClass("hidden");
                
            });

        $("#facilitador-modal").modal();


        $("#facilitador-modal").on("hidden.bs.modal", function () {
        	
        	$("[id=password]").prop('required',true );
        	$("[id=password_confirmation]").prop('required',true);
        	
            $("#facilitador-form :input").prop('readonly', false);
            $( "#facilitador-form select" ).prop('disabled', false);
            $("#facilitador-aceptar").removeClass("hidden");
       });

    }
    
</script>
@endpush