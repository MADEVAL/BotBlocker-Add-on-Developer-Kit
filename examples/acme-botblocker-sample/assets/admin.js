(function () {
    'use strict';

    var data = window.acmeBotBlockerSampleAdmin || {};
    if (!data.headerName) {
        return;
    }

    document.documentElement.setAttribute('data-acme-bbcs-sample-admin', data.headerName);
}());
