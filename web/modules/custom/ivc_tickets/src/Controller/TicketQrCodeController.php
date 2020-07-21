<?php

namespace Drupal\ivc_tickets\Controller;

use Drupal\ivc_tickets\Entity\TicketEntity;
use Drupal\Core\Url ;

use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Response\QrCodeResponse;

use Symfony\Component\HttpFoundation\Response;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class TicketQrCodeController.
 */
class TicketQrCodeController extends ControllerBase {

  /**
   * Ticket_qr_code.
   *
   * @return string
   *   Return Hello string.
   */
  public function ticket_qr_code($hash) {

    $response = new Response();
    $response->headers->set('Content-Type', 'image/svg+xml');

    $url = Url::fromRoute('ivc_tickets.ticket_scan_controller_ticket_scan', ['hash' => $hash], ['absolute' => TRUE]);

    $qrCode_image = $this->generateQrCode($url->toString());
    $response->setContent($qrCode_image);

    return $response;
  }

  private function generateQrCode($url = '') {
    // Create a basic QR code
    $qrCode = new QrCode($url);
    $qrCode->setSize(512);

    // Set advanced options
    $qrCode->setWriterByName('svg');
    $qrCode->setEncoding('UTF-8');
    $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH());
    $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
    $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);

    $qrCode->setWriterOptions(['exclude_xml_declaration' => true]);

    return $qrCode->writeString();      
  }

}
