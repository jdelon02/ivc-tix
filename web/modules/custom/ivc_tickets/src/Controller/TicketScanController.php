<?php

namespace Drupal\ivc_tickets\Controller;

use Drupal\ivc_tickets\Entity\TicketEntity;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class TicketScanController.
 */
class TicketScanController extends ControllerBase {

  /**
   * Ticket_scan.
   *
   * @return string
   *   Return Hello string.
   */
  public function ticket_scan($hash) {

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: ticket_scan with parameter(s): $hash'),
    ];
  }

  public function invalid_ticket($hash) {

    return [
      '#type' => 'markup',
      '#markup' => "Invalid ticket",
    ];
  }

}
