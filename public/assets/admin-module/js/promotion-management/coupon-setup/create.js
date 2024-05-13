"use strict";

$('.js-select').select2();
$('.js-area-select').select2({placeholder: "Select Areas ..."});

$('#coupon_rules').on('change',function () {
    if ($(this).val() == 'vehicle_category_wise') {
        $('.vehicle_category').removeClass('d-none');
    } else {
        $('.vehicle_category').addClass('d-none');
    }
});

$('#customer').change(function () {
    let value = $(this).val();
    let $userLevel = $('.user_level');

    if (value !== 'all') {
        $userLevel.addClass('d-none');
    } else {
        $userLevel.removeClass('d-none');
    }
});
