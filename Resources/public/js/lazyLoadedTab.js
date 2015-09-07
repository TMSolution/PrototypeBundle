$(function () {
//jakas tam klasa tabowa


    //@todo plugin needed
     lazyLoad = function (element,registerAsState)
    {
        if (element.data("route-target"))
        {

            var context = element.data("route-target");

        }
        else
        {
            var context = element.attr("href");
        }

        var state = element.attr("href").substr(1);

        $.ajax({
            url: Routing.generate(element.data("route"), element.data("route-params")),
        }).done(function (data) {

            if(element.parents()){
                    
            history.pushState({}, "nowa", window.location.href + "/" + state);
        }
            $(context).html(data);

        });



    }


 
    $('.lazy-loaded-tab').one("click", function () {

            lazyLoad($(this),true);

    })





});


