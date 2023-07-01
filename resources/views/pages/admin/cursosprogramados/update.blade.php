@push('JS')
<script>
    function editarPrograma(url){
        $(".loader").removeClass("hidden");
        $("#programar-form").addClass("hidden");
        $("[name=_method]").val("PUT");
        $("#programar-form").attr("action", url);
        $("#programar-label").html("Editar programación");
        $('#titulo').prop('disabled',true); 
       

        

        $.get(url,function(data,status){
                data=JSON.parse(data);
                console.log(data);
                $('#titulo').val(data.course_id).trigger("change");
                $('#facilitador').val(data.facilitator_id).trigger("change");  
                
                var myDate = new Date(data.start_date);
                 var myDate2 = new Date(data.end_date);
                $('#fecha_i').val((myDate.getDate() + 1) + "-" + (myDate.getMonth()+1) + "-" + myDate.getFullYear()); 
                $('#fecha_f').val((myDate2.getDate() + 1) + "-" + (myDate2.getMonth()+1) + "-" + myDate2.getFullYear()); 
                $(".loader").addClass("hidden");
                $("#programar-form").removeClass("hidden");
                
            });
        $("#programar-modal").modal(); 

        $("#programar-modal").on("hidden.bs.modal", function () {
            $('#titulo').prop('disabled',false);
           
       });

        $('.dat2').datepicker({
            format: "dd-mm-yyyy"                
        });


    }
</script>
@endpush