(function ($) {

    methods = {
        settings: {
            defaultPath: null,
            defaultContainer: null
        },
        defaultPath: function (value) {
            if (value) {
                this.settings.defaultPath = value;
            }
            else {
                return this.settings.defaultPath
            }
        },
        load: function (id, url, target, operation, callback) {

            var object = this;

            $.ajax({
                url: url,
                method: "POST",
                data: {elementId: id}
            }).done(function (data) {

                if (target) {
                    $(target).html(data, target);
                } else
                {
                    if (object.settings.defaultContainer) {
                        $(object.settings.defaultContainer).html(data, target);
                    }
                }

                //    object[operation](id, url, target);


                /* if (callback)
                 {
                 callback(id, url, target, data);
                 }*/

            });
        },
        reload: function (url, target) {

            var object = this;
            $.ajax({
                method: "POST",
                url: url

            }).done(function (data) {


                if (target) {

                    $(target).html(data, target);
                } else
                {

                    $(object.settings.defaultContainer).html(data, target);
                }

            });
        },
        replaceUrl: function (id, url, target) {

            console.debug(this.settings);
            history.pushState({"url": url, "target": target, "id": id}, id, this.settings.defaultPath + '/' + id.replace(/_/g, "/"));

            /*var pat = /^https?:\/\//i;
             if (!pat.test(url)) {
             if (url.substr(0, 1) == '.') {
             url = url.substr(1);
             }
             
             if (url.substr(0, 1) != '/') {
             url = '/' + url;
             }
             
             url = window.location.href + url;
             
             }
             console.debug('dfsdfsdf');*/


            /*if (window.location.href != this.settings.defaultPath) {
             var urlArray = window.location.href.split("/");
             var parentId = urlArray[urlArray.length - 1];
             
             
             if (parentId && $(id).parents(parentId).length) {
             
             history.pushState({"url": url, "target": target, "id": id}, id, window.location.href + '/' + id);
             }
             else {
             
             urlArray[urlArray.length - 1] = id;
             var path = urlArray.join('/');
             history.pushState({"url": url, "target": target, "id": id}, id, path);
             }
             }
             else {
             history.pushState({"url": url, "target": target, "id": id}, id, this.settings.defaultPath + '/' + id);
             }*/






        },
        appendUrl: function (id, url, target) {

            history.pushState({"url": url, "target": target, "id": id}, id, window.location.href + '/' + id);

        },
        init: function (settings) {

            $.extend(this.settings, settings);
            console.debug(this.settings);
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
