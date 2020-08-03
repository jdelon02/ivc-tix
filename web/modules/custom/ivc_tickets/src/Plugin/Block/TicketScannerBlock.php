<?php

namespace Drupal\ivc_tickets\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'TicketScannerBlock' block.
 *
 * @Block(
 *  id = "ticket_scanner_block",
 *  admin_label = @Translation("Ticket Scanner Block"),
 * )
 */
class TicketScannerBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'ticket_scanner_block';
    $build['#attached']['library'][] = 'ivc_tickets/ticket-scanner';
    //$build['ticket_scanner_block']['#markup'] = 'Implement TicketScannerBlock.';

    return $build;
  }

}
