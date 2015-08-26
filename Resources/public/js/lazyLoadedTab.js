$(function () {
//jakas tam klasa tabowa
    $('.lazy-loaded-tab').one("click", function () {


        if ($(this).data("url-target"))
        {

            var context = $(this).data("url-target");

        }
        else
        {
            var context = $(this).attr("href");
        }
        console.log(context);
        //HouseOfCode.blockui(context).block();

        $.ajax({
            url: Routing.generate($(this).data("url"), $(this).data("url-params")),
        }).done(function (data) {

            console.log(context);
            $(context).html(data);
            //  HouseOfCode.blockui('.tab-pane').unblock();

        });



    })


});


