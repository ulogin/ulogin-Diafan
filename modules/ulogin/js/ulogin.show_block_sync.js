$(document).ready(function () {
    var uloginNetwork = $('#ulogin_accounts').find('.ulogin_network');
    uloginNetwork.click(function () {
        var network = $(this).attr('data-ulogin-network');
        var identity = $(this).attr('data-ulogin-identity');
        $.ajax({
            type: 'POST',
            url: window.location.href,
            dataType: 'json',
            data: {
                action: 'delete',
                module: 'ulogin',
                identity: identity,
                network: network
            },
            error: function (response) {
                alert(response.msg);
            },
            success: function (response) {
                var accounts = $('#ulogin_accounts');
                nw = accounts.find('[data-ulogin-network=' + network + ']');
                if (nw.length > 0) nw.hide();
                alert(response.msg);
            }
        });
    });
});