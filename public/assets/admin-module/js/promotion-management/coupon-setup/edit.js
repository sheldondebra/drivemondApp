"use strict";

$('.js-select').select2();
$('.js-area-select').select2({placeholder: "Select Areas ..."});

$('#customer').change(function () {
    let value = this.value;
    if (value != 0) {
        $('.user_level').addClass('d-none');

    } else {
        $('.user_level').removeClass('d-none');
    }

});
