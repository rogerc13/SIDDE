function goBack() {
    window.history.back();
}

$(document).ready(function () {
    $(".save-ficha").on("click", function (e) {
        window.print();
        e.preventDefault();
    });

    /* $('.course-file-btn a').on('click', function(e){
       e.preventDefault();
    }); */
});
