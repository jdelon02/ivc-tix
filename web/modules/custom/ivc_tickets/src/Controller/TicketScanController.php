<?php

namespace Drupal\ivc_tickets\Controller;

use Drupal\ivc_tickets\Entity\TicketEntity;
use Drupal\user\Entity\User;
use Drupal\shopify\Entity\ShopifyProduct;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

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

    $user = User::load($this->currentUser()->id());

    $valid = TRUE;
    $shopify_product_title = '';
    $date_admit = '';
    $time_admit = '';    
    $date_redeemed = '';
    $ret = [];

    $query = \Drupal::entityQuery('ticket_entity')
    ->condition('field_sha256', $hash)
    ->sort('created', 'DESC')
    ->range(0,1);

    $tids = $query->execute();

    if ($tids) {
      $tid = array_keys($tids)[0];
      $ticket = TicketEntity::load($tid);

      if ($user->get('field_venue')) {
        $user_venue = $user->get('field_venue')->entity->get('product_id')->value;
        $ticket_venue = (string) $ticket->get('field_product_id')->value;

        $shopify_product = ShopifyProduct::loadByProductId($ticket_venue);
        $shopify_product_title = $shopify_product->get('title')->value;

        if ($ticket_venue != $user_venue) {
          \Drupal::messenger()->addMessage(t('Incorrect Venue'), 'error');
          $valid = FALSE;
        }   
      } else {
        \Drupal::messenger()->addMessage(t('Current user is missing venue'), 'error');
        $valid = FALSE;
      }      

      if ($ticket->get('field_redeemed')->value) {
        \Drupal::messenger()->addMessage(t('Previously Used', [
          '@date' => $ticket->get('field_redemption_date')->value,
        ]), 'error');
        $valid = FALSE;

        $date_redeemed = $ticket->get('field_redemption_date')->date->format('m/d/Y g:ia');

        $ret[] = ['previously_used' => $date_redeemed ? [
          '#markup' => t('<div class="ticket-attr-detail"><span class="ticket-attr-label"></span> <span class="ticket-attr-value">@value</span>', ['@value' => $date_redeemed]),
        ] : ''];
      }

      if ($ticket->get('field_admission_date_and_time')->value && strlen($ticket->get('field_admission_date_and_time')->value) > 0) {
        $admission_date_parts = explode(' to ', $ticket->get('field_admission_date_and_time')->value);
        $date_start_parts = explode(' at ', $admission_date_parts[0]);
        $date_start = strtotime($date_start_parts[0] . ' ' . $date_start_parts[1]);

        $date_admit = date('m/d/Y', strtotime($date_start_parts[0]));
        $time_admit = date('g:i a', strtotime($date_start_parts[1]));

        $date_end_parts = explode(' at ', $admission_date_parts[1]);
        $date_end = strtotime($date_end_parts[0] . ' ' . $date_end_parts[1]);

        //\Drupal::messenger()->addMessage(date('m/d/Y h:i:s', $date_start) . ' - ' . date('m/d/Y h:i:s', $date_end), 'error');

        $today = date("m/d/Y");
        $now = time();

        if ($today != date('m/d/Y', $date_start)) {
          \Drupal::messenger()->addMessage(t('Incorrect Date'), 'error');
          $valid = FALSE;

          $ret[] = ['date_admit' => [
            '#markup' => t('<div class="ticket-attr-detail"><span class="ticket-attr-label">Admit:</span> <span class="ticket-attr-value">@value</span>', ['@value' => $date_admit]),
          ],];
        } else if (( $now < $date_start ) && (abs($date_start - $now) > 36000)) {
          \Drupal::messenger()->addMessage(t('Incorrect Time'), 'error');
          $valid = FALSE;

          $ret[] = ['time_admit' => [
            '#markup' => t('<div class="ticket-attr-detail"><span class="ticket-attr-label">Admit:</span> <span class="ticket-attr-value">@value</span>', ['@value' => ($date_admit . ' ' . $time_admit)]),
          ],];          
        } else if (( $now > $date_start ) && (abs($date_start - $now) > 36000)) {
          \Drupal::messenger()->addMessage(t('Incorrect Time'), 'error');
          $valid = FALSE;

          $ret[] = ['time_admit' => [
            '#markup' => t('<div class="ticket-attr-detail"><span class="ticket-attr-label">Admit:</span> <span class="ticket-attr-value">@value</span>', ['@value' => ($date_admit . ' ' . $time_admit)]),
          ],];          
        }
      }

      if ($ticket->get('field_status')->value == 'cancelled') {
        \Drupal::messenger()->addMessage(t('Canceled/Refunded.'), 'error');
        $valid = FALSE;
      }

      if ($valid) {
        \Drupal::messenger()->addMessage(t('Ticket is valid'), 'status');

        $ticket_characteristics = $ticket->get('field_ticket_characteristics')->value;
        $type = substr($ticket_characteristics, strrpos($ticket_characteristics, ' - ') + 3);

        $ret[] = ['venue' => [
          '#markup' => t('<div class="ticket-attr-detail"><span class="ticket-attr-label">Venue:</span> <span class="ticket-attr-value">@value</span>', ['@value' => $shopify_product_title]),
        ],];

        if ($type) {
          $ret[] = ['type' => [
            '#markup' => t('<div class="ticket-attr-detail"><span class="ticket-attr-label">Type:</span> <span class="ticket-attr-value">@value</span>', ['@value' => $type]),
          ],];  
        }

        if ($date_admit) {
          $ret[] = ['date_admit' => [
            '#markup' => t('<div class="ticket-attr-detail"><span class="ticket-attr-label">Admit:</span> <span class="ticket-attr-value">@value</span>', ['@value' => $date_admit]),
          ],];
        }

        if ($time_admit) {
          $ret[] = ['time_admit' => [
            '#markup' => t('<div class="ticket-attr-detail"><span class="ticket-attr-label">Admit:</span> <span class="ticket-attr-value">@value</span>', ['@value' => ($date_admit . ' ' . $time_admit)]),
          ],];         
        }

        // Get the default timezone
        $default_timezone = new \DateTimeZone(date_default_timezone_get());
        // Get the storage timezone
        $storage_timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);        
        // Set some date/time objects
        $now = new \DateTime('now', $default_timezone);

        $ticket->set('field_redemption_date', 
          $now
            ->setTimezone($storage_timezone)
            ->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT)
        );
        $ticket->set('field_redeemed', true);
        //$ticket->save();
      }

      $content = views_embed_view('ticket_scan', 'block_ticket_scan', $tid);
    } else {
      \Drupal::messenger()->addMessage(t('Ticket Unknown'), 'error');
      $valid = FALSE;      
    }

    $block_manager = \Drupal::service('plugin.manager.block');
    // You can hard code configuration or you load from settings.
    $config = [];
    $plugin_block = $block_manager->createInstance('ticket_scanner_block', $config);

    $render = $plugin_block->build();

    $ret[] = [
      'scan_another' => [
        '#markup' => '<h2>Scan Another Ticket</h2>',
      ],
      'ticket_scanner_block' => $render,
    ];

    return $ret;
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
      'scan' => [
        '#markup' => '<h2>Scan a Ticket</h2>',
      ],      
      'ticket_scanner_block' => $render,
    ];
  }

}
