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

    $valid = TRUE;

    $query = \Drupal::entityQuery('ticket_entity')
    ->condition('field_sha256', $hash)
    ->sort('created', 'DESC')
    ->range(0,1);

    $tids = $query->execute();

    if ($tids) {
      $tid = array_keys($tids)[0];
      $ticket = TicketEntity::load($tid);


      if ($ticket->get('field_redeemed')->value) {
        \Drupal::messenger()->addMessage(t('This ticket has been redeemed.'), 'error');
        $valid = FALSE;
      }

      if ($ticket->get('field_admission_date_and_time')->value && strlen($ticket->get('field_admission_date_and_time')->value) > 0) {
        $admission_date_parts = explode(' to ', $ticket->get('field_admission_date_and_time')->value);
        $date_start_parts = explode(' at ', substr($admission_date_parts[0], 0, strpos($admission_date_parts[0], ' EDT') + 1));
        $date_start = strtotime($date_start_parts[0] . ' ' . $date_start_parts[1]);

        $date_end_parts = explode(' at ', substr($admission_date_parts[1], 0, strpos($admission_date_parts[1], ' EDT') + 1));
        $date_end = strtotime($date_end_parts[0] . ' ' . $date_end_parts[1]);

        $today = date("m/d/Y");
        $now = time();

        if ($today != date('m/d/Y', $date_start)) {
          \Drupal::messenger()->addMessage(t('Wrong day - check admission date'), 'error');
          $valid = FALSE;
      } else if ($date_start - $now > 0 && $date_start - $now > 36000) {
          \Drupal::messenger()->addMessage(t('Wrong time - check admission time, more than 10 minutes early'), 'error');
          $valid = FALSE;
      } else if ($date_start - $now < 0 && $date_start - $now < 36000) {
          \Drupal::messenger()->addMessage(t('Wrong time - check admission time, more than 10 minutes late'), 'error');
          $valid = FALSE;
        }
      }

      if ($valid) {
        \Drupal::messenger()->addMessage(t('Ticket is valid'), 'success');
      }

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
