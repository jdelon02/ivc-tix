(function ($, Drupal) {
  Drupal.behaviors.IvcTicketsQRCodeScanner = {
    html5QrCode: null,
    attach: function (context, settings) {
      $('#ticket-scanner', context).once('IvcTicketsQRCodeScanner').each(function () {        

        $('#btn-set-camera').on('click.IvcTicketsQRCodeScanner', function(e) {
          e.preventDefault();
  
          if ($('#camera-select').val().length < 1) {
            alert('Please select a camera.');
            return;
          }
  
        $([document.documentElement, document.body]).animate({
          scrollTop: $("#qrcode_scanner").offset().top
        }, 250);

          Drupal.behaviors.IvcTicketsQRCodeScanner.html5QrCode = new Html5Qrcode("qrcode_scanner", true);
          Drupal.behaviors.IvcTicketsQRCodeScanner.html5QrCode.start(
            $('#camera-select').val(), 
            {
              fps: 15,    // Optional frame per seconds for qr code scanning
              qrbox: 250  // Optional if you want bounded box UI
            },
            qrCodeMessage => {
              $('#qrcode_status').html('<span class="status-success">Please wait, redirecting...</span>');
              if (Drupal.behaviors.IvcTicketsQRCodeScanner.validURL(qrCodeMessage)) {
                Drupal.behaviors.IvcTicketsQRCodeScanner.html5QrCode.stop().then(ignore => {
                  window.location.href = qrCodeMessage;
                }).catch(err => {
                  // Stop failed, handle it.
                });                
              }
            },
            errorMessage => {
              $('#qrcode_status').html('<span class="status-error">Invalid QR code, please try again.</span>');
            })
          .catch(err => {
            $('#qrcode_status').html(err);

            // Start failed, handle it.
          });
        });

        Html5Qrcode.getCameras().then(devices => {
          /**
           * devices would be an array of objects of type:
           * { id: "id", label: "label" }
           */
          if (devices && devices.length) {
            for (var i = devices.length - 1; i >= 0; i--) {
              $('#camera-select').append('<option value="' + devices[i].id + '">' + devices[i].label + '</option>');
            }
            
            $('#btn-set-camera').trigger('click.IvcTicketsQRCodeScanner'); 
          }
        }).catch(err => {
          // handle err
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
    },
    isInViewport: function(el) {
      var elementTop = el.offset().top;
      var elementBottom = elementTop + el.outerHeight();
      var viewportTop = $(window).scrollTop();
      var viewportBottom = viewportTop + $(window).height();
      return elementBottom > viewportTop && elementTop < viewportBottom;
    }
  };
})(jQuery, Drupal);