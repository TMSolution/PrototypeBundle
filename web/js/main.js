
$(document).ready(function () {


    var facebookAjax = function () {
        
        $.ajax({
            url: $(this).attr('href'),
            type: 'POST',
            data: $('form').serialize(),
            success: function (url) {
                console.debug(url);
            }
        });
        return false;
    }
    

    $('.btn-facebook').on('click',facebookAjax);
    
    



});