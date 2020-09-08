$(document).on("click", "button#checkBtn", function (e) {
    // Set appconfig lastCheckTime
    $.ajax({
        url: OC.generateUrl('/apps/ndcversionstatus/setTime'),
        type: 'GET'
    }).always(function() {
        var redirect_url = $(e.target).attr('url')
        window.open(redirect_url, "_self")
    })
 });
