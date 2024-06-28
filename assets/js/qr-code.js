jQuery(document).ready(function ($) {
    // get value after entering account Number
    $('#woocommerce_qrcode_accountNo').on('input', function () {
        var accountNo = $(this).val();
        var bank = $('#woocommerce_qrcode_bank').val();
        var client_id = $('#woocommerce_qrcode_clientID').val();
        var api_key = $('#woocommerce_qrcode_apiKey').val();
        var data = {
            bin: bank,
            accountNumber: accountNo
        };

        // call API get account name from bin and account number
        $.ajax({
            url: 'https://api.vietqr.io/v2/lookup',
            type: 'POST',
            headers:{
                'Content-Type': 'application/json',
                'x-client-id' : client_id,
                'x-api-key' : api_key,
            },
            data: JSON.stringify(data),
            success: function (response) {
                if (response.desc) {
                    // update accountName
                    $('#woocommerce_qrcode_accountName').val(response.data.accountName);
                } else {
                    alert('Unable to fetch account name. Please check the account number and bank.');
                }
            }
        });
    });

    // view details
    $('#qr-code-details-link').on('click', function(event) {
        event.preventDefault();
        $('#qr-code-details-dialog').dialog({
            modal: true,
            width: 600,
            title: 'Usage Guide for QR Code Payment Plugin'
        });
    });
});

