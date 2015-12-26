(function ($) {
    $.validator.setDefaults({
        errorElement: 'span',
        errorClass: 'help-block',
        errorPlacement: function (error, element) {

            //warunek dla chcekboxów
            var serverError = $('#' + error.attr('id'), element.parent());
            if (serverError.length > 0) {
                serverError.remove();
            }

            if (element.parent('.input-group').length) {
                error.insertAfter(element.parent());
               
            }
            if (element.parent('.fg-line').length) {
                error.insertAfter(element.parent('.fg-line'));
                
            
            }
            if (element.parent().parent('.linklabeledcheckbox').length) {
                error.insertAfter(element.parent('.linklabeledcheckbox').next());
                
                
            }
            else {
                
                error.insertAfter(element);
            }
          


        },
        highlight: function (element) {
            //logika
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
        },
        unhighlight: function (element) {
            //logika
            $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
        },
        ignore: function (idx, elt) {
            // We don't validate hidden fields expect if they have rules attached.
            return $(elt).is(':hidden') && $.isEmptyObject($(this).rules());
        }
    });
})(jQuery);