(function () {
    'use strict';

    var data = window.acmeBotBlockerSample || {};
    if (!data.enabled) {
        return;
    }

    document.documentElement.setAttribute('data-acme-bbcs-sample', 'active');
}());
