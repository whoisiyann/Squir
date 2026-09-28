// Save the phone model for activity logs
// Android Chromium; HTTPS or localhost only
// Other browsers use user-agent fallback
(function () {
    'use strict';

    var uad = navigator.userAgentData;
    if (!uad || typeof uad.getHighEntropyValues !== 'function') {
        return;
    }

    uad.getHighEntropyValues(['model'])
        .then(function (data) {
            var model = (data && data.model ? String(data.model) : '').trim();
            if (model === '' || model.toLowerCase() === 'k') {
                return;
            }

            // Skip unchanged model cookies
            if (document.cookie.indexOf('sq_model=' + encodeURIComponent(model)) !== -1) {
                return;
            }

            var cookie = 'sq_model=' + encodeURIComponent(model.slice(0, 60)) +
                '; path=/; max-age=31536000; SameSite=Lax';
            if (location.protocol === 'https:') {
                cookie += '; Secure';
            }
            document.cookie = cookie;
        })
        .catch(function () { /* not supported or blocked: ignore */ });
})();