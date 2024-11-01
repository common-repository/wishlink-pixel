const SESSION_DURATION = 1000 * 60 * 30;
function getParameterByName(name, url = window.location.href) {
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'), results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}

function sendPixel(url){
    var img = document.createElement('img')
    img.setAttribute("src", url);
    img.setAttribute("id", "atg-landing");
    img.setAttribute("width", "1");
    img.setAttribute("height", "1");
    document.head.appendChild(img);
}

function getCookie(name) {
    return (document.cookie.match('(?:^|;)\\s*'+name.trim()+'\\s*=\\s*([^;]*?)\\s*(?:;|$)')||[])[1];
}

let atgId = getParameterByName('atgSessionId');

let sessionIdChanged = false;
const currentTime = + new Date();
if(atgId) {
    if(atgId !== localStorage.getItem('atgSessionId')){
        sessionIdChanged = true;
    }
    localStorage.setItem('atgSessionId', atgId);
}
atgId = localStorage.getItem('atgSessionId');
if(atgId){
    let g = encodeURIComponent(getParameterByName('gclid'));
    let f = encodeURIComponent(getParameterByName('fbclid'));
    let us = encodeURIComponent(getParameterByName('utm_source'));
    let um = encodeURIComponent(getParameterByName('utm_medium'));
    let uc = encodeURIComponent(getParameterByName('utm_campaign'));
    let platform = encodeURIComponent(wishlinkLanding.platform);
    let gaId = encodeURIComponent(getCookie('_ga'));
    let url = `https://api.wishlink.com/api/brandUserLanding?atgSessionId=${atgId}&ga=${gaId}&platform=${platform}&gclid=${g}&fbclid=${f}&utm_source=${us}&utm_medium=${um}&utm_campaign=${uc}`;
    let atgLastSession = localStorage.getItem('atgLastSession');
    if(g != 'null' || f != 'null' || us != 'null' || um != 'null' || uc != 'null' || !atgLastSession || ((currentTime - atgLastSession) > SESSION_DURATION) || sessionIdChanged ){
        sendPixel(url);
    }
}
localStorage.setItem('atgLastSession', currentTime);