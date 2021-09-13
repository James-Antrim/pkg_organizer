jQuery(document).ready(function () {
    'use strict';

    document.formvalidator.setHandler('address',
        function (value) {
            return (/^([A-ZÀ-ÖØ-Þa-zß-ÿ0-9 .\-]+ *)+$/).test(value);
        }
    );

    document.formvalidator.setHandler('event-code',
        function (value) {
            return (/^[a-f\d]{4}-[a-f\d]{4}$/).test(value);
        }
    );

    document.formvalidator.setHandler('gps',
        function (value) {
            return (/^-?[\d]?[\d].[\d]{6}, ?-?[01]?[\d]{1,2}.[\d]{6}$/).test(value);
        }
    );

    document.formvalidator.setHandler('name',
        function (value) {
            return (/^[A-ZÀ-ÖØ-Þa-zß-ÿ \-']+$/).test(value);
        }
    );

    document.formvalidator.setHandler('ip',
        function (value) {
            return (/^[0-2]*[0-9]*[0-9].[0-2]*[0-9]*[0-9].[0-2]*[0-9]*[0-9].[0-2]*[0-9]*[0-9]$/).test(value);
        }
    );

    document.formvalidator.setHandler('select', function (value) {
        return (value !== 0);
    });

    document.formvalidator.setHandler('telephone',
        function (value) {
            return (/^(\+[\d]+ ?)?( ?((\(0?[\d]*\))|(0?[\d]+(\/| \/)?)))?(([ \-]|[\d]+)+)$/).test(value);
        }
    );

    document.formvalidator.setHandler('text',
        function (value) {
            return (/^[A-ZÀ-ÖØ-Þa-zß-ÿ \-0-9:\/']+$/).test(value);
        }
    );

    document.formvalidator.setHandler('time',
        function (value) {
            return (/^(([01]?[0-9]|2[0-3]):?[0-5][0-9])$/).test(value);
        }
    );
});