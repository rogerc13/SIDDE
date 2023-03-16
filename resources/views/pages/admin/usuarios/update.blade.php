@push('JS')
<script>
    function editarUsuario(url){

        $(".loader").removeClass("hidden");
        $("#usuario-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#usuario-label").html("Editar usuario");
        $("#usuario-form").attr("action", url);
        $("#password").val("");
        $("#password_confirmation").val("");        
        $("[id=password]").prop('required',false);
        $("[id=password_confirmation]").prop('required',false);
        

        $.get(url,function(data,status){
                data=JSON.parse(data);
                $('#nombre').val(data.nombre);  
                $('#apellido').val(data.apellido); 
                $('#email').val(data.email);
                $('#ci').val(data.ci); 
                $('#rol').val(data.rol_id);
                $('#user_id').val(data.id);                
                $(".loader").addClass("hidden");
                $("#usuario-form").removeClass("hidden");
                
            });

        $("#usuario-modal").modal();


        $("#usuario-modal").on("hidden.bs.modal", function () {
        	
        	$("[id=password]").prop('required',true );
        	$("[id=password_confirmation]").prop('required',true);
        	
            $("#usuario-form :input").prop('readonly', false);
            $( "#usuario-form select" ).prop('disabled', false);
            $("#usuario-aceptar").removeClass("hidden");
       });

    }
    
</script>
@endpush
