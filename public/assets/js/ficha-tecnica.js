$(document).ready(function (){
    $('.save-ficha').on('click',function (e){
        window.print();
        e.preventDefault();
    });
});
