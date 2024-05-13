"use strict";

(function ($) {
    $(document).ready(function () {
        $('.js-select').select2();
        auto_grow();

    });
    $('.js-select2').select2({
        dropdownParent: $("#activityLogModal")
    });


    function auto_grow() {
        let element = document.getElementById("coordinates");
        element.style.height = "5px";
        element.style.height = (element.scrollHeight) + "px";
    }

    function ajax_get(route,id){
        $.get({
            url: route,
            dataType: 'json',
            data: {},
            beforeSend: function () {
            },
            success: function (response) {
                $('#'+id).html(response.template);
            },
            complete: function () {
            },
        });
    }
})(jQuery);


