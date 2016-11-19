/**
 * Firmware Selector JS
 */
(function ($, window) {
    'use strict';

    window.firmwareSelect = {
        files: {},
        selects: {},
        downloadButton: {},

        /**
         * Init the selector
         */
        init: function () {
            var self = this;
            this.selects = {
                supplier: $('#supplier'),
                device: $('#device'),
                branch: $('#branch'),
                type: $('#type'),
                revision: $('#revision')
            };
            this.downloadButton = $('#download');

            window.firmwareSelectApi.request({
                success: function (data) {
                    self.files = data;
                    self.updateSelect('supplier', self.files);
                }
            });

            $.each(this.selects, function (name, select) {
                select.on('change', function () {
                    self.onChangeSelect(this);
                })
            });
        },

        /**
         * Update the next dropdown
         *
         * @param select Object
         */
        onChangeSelect: function (select) {
            var next = this.getNextSelect(select);

            if (select.value == '') {
                if (!$.isEmptyObject(next)) {
                    this.clearSelect(next.attr('name'));
                }

                return;
            }

            if ($.isEmptyObject(next)) {
                this.download(select);
                return;
            }
            var nextData = this.getNextData(select.name);

            this.updateSelect(next.attr('name'), nextData);
        },

        /**
         * Set the given data as select values
         *
         * @param selectName string
         * @param data Object
         */
        updateSelect: function (selectName, data) {
            var self = this;

            this.clearSelect(selectName);
            if (!this.selects.revision.parent().is(':visible')) {
                this.selects.revision.parent().show();
            }

            if (
                data.length == 1
                && selectName == 'revision'
            ) {
                this.selects.revision.parent().hide();
                this.download();
                return;
            }

            $.each(data, function (value, data) {
                var name = value;

                if (
                    'revision' in data
                    && typeof data.revision !== 'object'
                ) {
                    name = data.revision;
                }

                self.addSelectOption(selectName, name, name);
            });

            if (
                data.length == 1
                || Object.keys(data).length == 1
            ) {
                this.removeSelectOption(selectName, '');
                this.selects[selectName].change();
            }
        },

        /**
         * Update the download button link
         *
         * @param revisionSelect string
         */
        download: function (revisionSelect) {
            var self = this,
                files = this.getNextData('type');

            this.downloadButton.attr('href', '#');

            if (files.length == 1) {
                self.downloadButton.attr('href', files[0].url);
                return;
            }

            $.each(files, function (key, data) {
                if (data.revision == revisionSelect.value) {
                    self.downloadButton.attr('href', data.url);
                    return false;
                }
            });
        },

        /**
         * Get the data for next select
         *
         * @param currentName
         * @returns Object
         */
        getNextData: function (currentName) {
            var path = this.getCurrentSelection(currentName),
                items = this.files;

            $.each(path, function (key, value) {
                items = items[value];
            });

            return items;
        },

        /**
         *  Get the current selected values
         *
         * @param name
         * @returns Object
         */
        getCurrentSelection: function (name) {
            var path = {}, found = false;

            $.each(this.selects, function (key, value) {
                path[key] = value.val();
                if (key == name) {
                    found = true;
                    return false;
                }
            });

            if (!found) {
                return {};
            }

            return path;
        },

        /**
         * Get the next dropdown element
         *
         * @param select Object
         * @returns Object
         */
        getNextSelect: function (select) {
            var next = false,
                item = {};

            $.each(this.selects, function (name, value) {
                if (next) {
                    item = value;
                    return false;
                }

                if (value.attr('name') == select.name) {
                    next = true;
                }
            });

            return item;
        },

        /**
         * Clear the given select
         *
         * @param name String
         */
        clearSelect: function (name) {
            var select = this.selects[name];
            select.find('option').remove();
            this.addSelectOption(name, 'Bitte auswählen');

            var selectItem = {name: select.attr('name')},
                next = this.getNextSelect(selectItem);

            if (!$.isEmptyObject(next)) {
                this.clearSelect(next.attr('name'))
            }
        },

        /**
         * Add an option to the select
         *
         * @param selectName string
         * @param text string
         * @param value string
         */
        addSelectOption: function (selectName, text, value) {
            value = value || '';

            this.selects[selectName].append($('<option>', {
                value: value,
                text: this.generateName(text)
            }));

            this.downloadButton.attr('href', '#');
        },

        /**
         * Remove a select option by value
         *
         * @param selectName
         * @param value
         */
        removeSelectOption: function (selectName, value) {
            this.selects[selectName].find('option[value=\'' + value + '\']').remove();
        },

        /**
         * Generate display name
         *
         * @param name
         * @returns {string}
         */
        generateName: function (name) {
            if (typeof name != 'string') {
                return name;
            }

            return name.charAt(0).toUpperCase() + name.slice(1);
        }
    }
})(jQuery, window);

jQuery(function () {
    window.firmwareSelect.init();
});
