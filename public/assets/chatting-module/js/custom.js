"use Strict";
$("#user_type").on("change", function () {
    if (this.value === "customer") {
        $("#customer").show();
        $("#provider").hide();
        $("#serviceman").hide();
        $("#staff").hide();
    } else if (this.value === "provider-admin") {
        $("#customer").hide();
        $("#provider").show();
        $("#serviceman").hide();
        $("#staff").hide();
    } else if (this.value === "provider-serviceman") {
        $("#customer").hide();
        $("#provider").hide();
        $("#serviceman").show();
        $("#staff").hide();
    } else if (this.value === "staff") {
        $("#customer").hide();
        $("#provider").hide();
        $("#serviceman").hide();
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
