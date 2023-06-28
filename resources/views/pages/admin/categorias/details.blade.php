@push('JS')
<script>
    function detallesCategoria(url){

        $(".loader").removeClass("hidden");
        $("#categoria-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#categoria-label").html("Detalles facilitador");
        $("#categoria-form").attr("action", url);

        $("#categoria-form :input").prop('readonly', true);
        $( "#categoria-form select" ).prop('disabled', true);
        $("#categoria-aceptar").addClass("hidden");

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