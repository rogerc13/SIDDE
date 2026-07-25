@push('JS')
<script>
    function detallesLocation(url){

        $(".loader").removeClass("hidden");
        $("#location-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#location-label").html("Detalles ubicación");
        $("#location-form").attr("action", url);

        $("#location-form :input").prop('readonly', true);
        $( "#location-form select" ).prop('disabled', true);
        $("#location-aceptar").addClass("hidden");

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
