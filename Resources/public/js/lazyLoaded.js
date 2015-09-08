(function ($) {

    methods = {
        settings: {
            basePath: null
        },
        setBasePath: function (basePath) {

            this.settings.basePath = basePath;
            console.debug(this.settings.basePath);
            
        },
        init: function () {
            

        }

    }

    $.fn.lazyLoaded = function (options) {
        if (methods[options]) {
            return methods[ options ].apply(methods, (Array.prototype.slice.call(arguments, 1)));
        } else if (typeof options === 'object' || !options) {
            return methods['init'].apply(methods, arguments);
        } else {
            $.error('Method ' + options + ' does not exist on jQuery.lazyLoaded');
        }
    };




















})(jQuery);
