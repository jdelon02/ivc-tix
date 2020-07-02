<?php

namespace Drupal\ivc_tickets\Controller;

use Drupal\ivc_tickets\Entity\TicketEntity;

use Drupal\views\Views;
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

    $query = \Drupal::entityQuery('ticket_entity')
    ->condition('field_sha256', $hash)
    ->sort('created', 'DESC')
    ->range(0,1);

    $tids = $query->execute();

    if ($tids) {
      $tid = array_keys($tids)[0];

      $content = views_embed_view('ticket_scan', 'block_ticket_scan', $tid);
    } else {
      $args = [];
      $content = views_embed_view('ticket_scan', 'block_ticket_scan');
    }

    $block_manager = \Drupal::service('plugin.manager.block');
    // You can hard code configuration or you load from settings.
    $config = [];
    $plugin_block = $block_manager->createInstance('ticket_scanner_block', $config);

    $render = $plugin_block->build();    

    return [
      'ticket_scan_view' => $content,
      'scan_another' => [
        '#markup' => '<h2>Scan Another Ticket</h2>',
      ],
      'ticket_scanner_block' => $render,
    ];
  }

  public function invalid_ticket($hash) {

    return [
      '#type' => 'markup',
      '#markup' => "Invalid ticket",
    ];
  }

  public function scanner() {
    $block_manager = \Drupal::service('plugin.manager.block');
    // You can hard code configuration or you load from settings.
    $config = [];
    $plugin_block = $block_manager->createInstance('ticket_scanner_block', $config);

    $render = $plugin_block->build();

    return [
      'ticket_scanner_block' => $render,
    ];
  }

}
