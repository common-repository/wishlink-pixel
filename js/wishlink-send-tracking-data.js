let wishlinkSessionId = localStorage.getItem('atgSessionId');
if(wishlinkSessionId || wishlinkAjax.couponCode){
    localStorage.removeItem("atgSessionId");
    data_string = `atgSessionId=${encodeURIComponent(wishlinkSessionId)}&saleAmount=${encodeURIComponent(wishlinkAjax.saleAmount)}&platform=${encodeURIComponent(wishlinkAjax.platform)}&currency=${encodeURIComponent(wishlinkAjax.currency)}&orderId=${encodeURIComponent(wishlinkAjax.orderId)}&items=${encodeURIComponent(wishlinkAjax.items)}&couponCode=${encodeURIComponent(wishlinkAjax.couponCode)}&numOrders=${encodeURIComponent(wishlinkAjax.numOrders)}`;
    url = `${wishlinkAjax.url}${data_string}`;
    var img = document.createElement('img')
    img.setAttribute("src", url);
    img.setAttribute("id", "wishlink-pixel");
    img.setAttribute("width", "1");
    img.setAttribute("height", "1");
    document.body.appendChild(img);
}