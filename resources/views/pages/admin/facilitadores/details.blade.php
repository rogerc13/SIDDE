@push('JS')
<script>
    function detallesFacilitador(url){

        $(".loader").removeClass("hidden");
        $("#facilitador-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#facilitador-label").html("Detalles facilitador");
        $("#facilitador-form").attr("action", url);

        $("#facilitador-form :input").prop('readonly', true);
        $( "#facilitador-form select" ).prop('disabled', true);
        $("#facilitador-aceptar").addClass("hidden");

        $.get(url,function(data,status){
                data=JSON.parse(data);
                $('#nombre').val(data.person.name);  
                $('#apellido').val(data.person.last_name); 
                $('#email').val(data.email);
                $('#ci').val(data.person.id_number);               
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