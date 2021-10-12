$(document).ready(function () {
    function update() {
        let fromData = $('#converter_from').find(":selected"),
            toData = $('#converter_to').find(":selected"),
            amountData = $('#converter_amount').val();

        fromData = {'code': fromData.text(), 'value': fromData.val()};
        toData = {'code': toData.text(), 'value': toData.val()};
        $.ajax({
            url: '/converter/result',
            data: { from : fromData, to : toData, amount : amountData },
            success: function (data) {
                if (data.emptyData === false) {
                    $("#result b").text(data.result);
                    $("#result").show();
                } else {
                    $("#result").hide();
                }
            },
            error: function(data) {
                $("#result").hide();
            },
            dataType: 'json'
        });
    }

    $('#converter_amount').bind('keyup', function (e) {
        e.preventDefault();
        update();
    });

    $('#converter_from, #converter_to').change(function(e) {
        e.preventDefault();
        update();
    });
});