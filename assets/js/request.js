/**
 * Send api requests
 */
(function ($, window) {
    "use strict";

    window.firmwareSelectApi = {
        /**
         * Default options
         */
        default_options: {
            url: '/router/firmware/get',
            success: function () {
            }
        },

        /**
         * Send a request to the api
         *
         * @param options object
         */
        request: function (options) {
            var settings = $.extend({}, this.default_options, options);

            return $.ajax({
                url: settings.url,
                success: settings.success
            });
        }
    };
}(jQuery, window));
