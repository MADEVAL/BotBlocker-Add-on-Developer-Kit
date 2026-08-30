/* ACME hCaptcha example — CAPTCHA Mode 90 renderer */

function renderMode90Captcha(params) {
    var holder = document.getElementById("content");
    if (!holder) { return; }

    holder.innerHTML = '<div id="acme-hcaptcha-box" style="max-width:302px;margin:0 auto;text-align:center;"></div>';
    var box = document.getElementById("acme-hcaptcha-box");

    var tryRender = function () {
        if (typeof hcaptcha === "undefined") { setTimeout(tryRender, 100); return; }
        hcaptcha.render(box, {
            sitekey: params.sitekey,
            theme: params.theme || "light",
            size: params.size || "normal",
            callback: function (token) {
                window.data += "&h-captcha-response=" + encodeURIComponent(token);
                window[bbcsJsData.checkFunctionName]("post", window.data, params.hash);
            }
        });
    };
    tryRender();
}

window.renderMode90Captcha = renderMode90Captcha;
