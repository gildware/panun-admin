"use Strict";
$("#user_type").on("change", function () {
    if (this.value === "customer") {
        $("#customer").show();
        $("#provider").hide();
        $("#staff").hide();
    } else if (this.value === "provider-admin") {
        $("#customer").hide();
        $("#provider").show();
        $("#staff").hide();
    } else if (this.value === "staff") {
        $("#customer").hide();
        $("#provider").hide();
        $("#staff").show();
    }
});

$(document).ready(function () {
    $(".js-select").select2();
});

$("#chat-search").on("keyup", function () {
    var value = this.value.toLowerCase().trim();
    $(".inbox_chat > div")
        .show()
        .filter(function () {
            return $(this).text().toLowerCase().trim().indexOf(value) == -1;
        })
        .hide();
});
