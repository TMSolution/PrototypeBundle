$(function () {
//jakas tam klasa tabowa
    $('.lazy-loaded-tab').one("click", function () {


        if ($(this).data("route-target"))
        {

            var context = $(this).data("route-target");

        }
        else
        {
            var context = $(this).attr("href");
        }
       
       console.log(context);
       console.log($(this).data("route"));
       console.log($(this).data("route-params"));
        $.ajax({
            url: Routing.generate($(this).data("route"), $(this).data("route-params")),
        }).done(function (data) {

            $(context).html(data);
       
        });



    })


});


