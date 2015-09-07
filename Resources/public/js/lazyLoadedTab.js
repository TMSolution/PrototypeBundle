$(function () {
//jakas tam klasa tabowa
    $('.lazy-loaded-tab').one("click", function () {

     
      //  window.location.hash = $(this).attr("href");
        if ($(this).data("route-target"))
        {

            var context = $(this).data("route-target");

        }
        else
        {
            var context = $(this).attr("href");
        }
       
        var state=$(this).attr("href").substr(1);
      
        $.ajax({
            url: Routing.generate($(this).data("route"), $(this).data("route-params")),
        }).done(function (data) {

      
        history.pushState({ "aaa": "costam" }, "nowa", window.location.href+"/"+state);    
        
        $(context).html(data);
       
        });

    

    })
    
    
    


});


