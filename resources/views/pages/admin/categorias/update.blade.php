@push('JS')
<script>
    function editarCategoria(url){

        $(".loader").removeClass("hidden");
        $("#categoria-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#categoria-label").html("Editar área");
        $("#categoria-form").attr("action", url);



        $.get(url,function(data,status){
                data=JSON.parse(data);
                $('#nombre').val(data.name);
                $(".loader").addClass("hidden");
                $("#categoria-form").removeClass("hidden");
                
            });

        $("#categoria-modal").modal();


        $("#categoria-modal").on("hidden.bs.modal", function () {
        	
            $("#categoria-form :input").prop('readonly', false);
            $( "#categoria-form select" ).prop('disabled', false);
            $("#categoria-aceptar").removeClass("hidden");
       });

    }
    
</script>
@endpush