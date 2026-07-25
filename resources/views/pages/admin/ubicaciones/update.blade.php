@push('JS')
<script>
    function editarLocation(url){

        $(".loader").removeClass("hidden");
        $("#location-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#location-label").html("Editar ubicación");
        $("#location-form").attr("action", url);



        $.get(url,function(data,status){
                data=JSON.parse(data);
                $('#nombre').val(data.name);
                $(".loader").addClass("hidden");
                $("#location-form").removeClass("hidden");
                
            });

        $("#location-modal").modal();


        $("#location-modal").on("hidden.bs.modal", function () {
        	
            $("#location-form :input").prop('readonly', false);
            $( "#location-form select" ).prop('disabled', false);
            $("#location-aceptar").removeClass("hidden");
       });

    }
    
</script>
@endpush
