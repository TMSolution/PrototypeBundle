     (function ($) {

                    methods = {
                        settings: {
                           
                            defaultContainer: null
                        },
                        
                        init: function (settings) {

                            $.extend(this.settings, settings);
                            console.debug(this.settings);
                        },
                        load: function (element, registerAsState, tab)
                        {
                            if (element.data("route-target"))
                            {
                                var context = element.data("route-target");
                            }
                            else if (tab)
                            {
                                var context = element.attr("href");
                            }
                            else if(this.settings.defaultContainer)
                            {

                                var context = $(this.settings.defaultContainer);

                            }

                            var state = element.attr("href").substr(1);

                            $.ajax({
                                url: Routing.generate(element.data("route"), element.data("route-params")),
                            }).done(function (data) {

                                if (element.parents()) {

                                    history.pushState({}, "nowa", window.location.href + "/" + state);
                                }
                                $(context).html(data);

                            });



                        }

                    }

                    $.fn.lazyLoader = function (options) {
                        if (methods[options]) {
                            return methods[ options ].apply(methods, (Array.prototype.slice.call(arguments, 1)));
                        } else if (typeof options === 'object' || !options) {
                            return methods['init'].apply(methods, arguments);
                        } else {
                            $.error('Method ' + options + ' does not exist on jQuery.lazyLoaded');
                        }
                    };




                })(jQuery);


