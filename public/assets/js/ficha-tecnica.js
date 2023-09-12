function goBack() {
    window.history.back();
}

$(document).ready(function () {
    $(".save-ficha").on("click", function (e) {
        window.print();
        e.preventDefault();
    });
});
