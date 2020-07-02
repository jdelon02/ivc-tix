(function ($, Drupal) {
  Drupal.behaviors.IvcTicketsQRCodeScanner = {
    attach: function (context, settings) {
      $('#qrcode_scanner', context).once('IvcTicketsQRCodeScanner').each(function () {
        const html5QrCode = new Html5Qrcode(/* element id */ "qrcode_scanner");
        // File based scanning
        const fileinput = document.getElementById('qr-input-file');
        fileinput.addEventListener('change', e => {
          if (e.target.files.length == 0) {
            // No file selected, ignore 
            return;
          }
        
          const imageFile = e.target.files[0];
          // Scan QR Code
          html5QrCode.scanFile(imageFile, true)
          .then(qrCodeMessage => {
            if (Drupal.behaviors.IvcTicketsQRCodeScanner.validURL(qrCodeMessage)) {
              html5QrCode.stop();
              window.location.href = qrCodeMessage;
            }
          })
          .catch(err => {
            $('#qrcode_status').html('Invalid QR code, please try again.');
            html5QrCode.clear();

            html5QrCode = new Html5Qrcode(/* element id */ "qrcode_scanner");
            // failure, handle it.
            console.log(`Error scanning file. Reason: ${err}`)
          });
        });    
      });
    },
    validURL: function(str) {
      var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name
        '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
        '(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
        '(\\#[-a-z\\d_]*)?$','i'); // fragment locator
      return !!pattern.test(str);
    }
  };
})(jQuery, Drupal);