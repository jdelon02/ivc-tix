<?php

namespace Drupal\ivc_tickets\Event\EventSubscriber;

use Drupal\taxonomy\Entity\Term;

use Drupal\shopify\Entity\ShopifyProduct;
use Drupal\shopify\Event\ShopifyWebhookEvent;
use Drupal\shopify\Event\ShopifyWebhookSubscriber as BaseSubscriber;

use Drupal\ivc_tickets\Entity\TicketEntity;
use Drupal\ivc_tickets\Entity\CustomerEntity;

use Endroid\QrCode\QrCode;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class IvcShopifyWebhookSubscriber.
 *
 * Provides the webhook subscriber functionality.
 */
class IvcShopifyWebhookSubscriber extends BaseSubscriber implements EventSubscriberInterface {

  /**
   * Process an incoming webhook. Override ShopifyWebhookSubscriber's method
   *
   * @param \Drupal\shopify\Event\ShopifyWebhookEvent $event
   *   Logs an incoming webhook of the setting is on.
   */
  public function onIncomingWebhook(ShopifyWebhookEvent $event) {
    $config = \Drupal::config('shopify.webhooks');
    if ($config->get('log_webhooks')) {
      // Log this incoming webhook data.
      \Drupal::logger('ivc_tickets.webhook')->info(t('<strong>Topic:</strong> @topic<br /> 
      <strong>Data:</strong> @data.', [
        '@topic' => $event->topic,
        '@data' => var_export($event->data, TRUE),
      ]));
    }
    $method = 'webhook_' . str_replace('/', '_', $event->topic);
    if (method_exists($this, $method)) {
      $this->{$method}($event->data);
    }
  }

  private function webhook_orders_create(\stdClass $data) {
    $customer_entity_id = $this->createCustomer($data);

    $this->createTickets($data, $customer_entity_id);

    \Drupal::logger('ivc_tickets.webhook')->info(t('<strong>Topic:</strong> @topic<br />
    <strong>Data:</strong> @data.', [
      '@topic' => 'Order Created',
      '@data' => var_export($data, TRUE),
    ]));
  }

  private function webhook_orders_updated(\stdClass $data) {
    $customer_entity_id = $this->createCustomer($data);

    $this->createTickets($data, $customer_entity_id);

    \Drupal::logger('ivc_tickets.webhook')->info(t('<strong>Topic:</strong> @topic<br />
    <strong>line items:</strong> @data<br/>', [
      '@topic' => 'Order Updated',
      '@data' => var_export($data->line_items, TRUE),
    ]));    
  }  

  private function createCustomer(\stdClass $data) {
    if (isset($data->customer->id)) {
      $query = \Drupal::entityQuery('customer_entity')
      ->condition('field_customer_id', (string) $data->customer->id)
      ->sort('created', 'DESC')
      ->range(0,1);
      
      $cids = $query->execute();
  
      $info = [
        'field_customer_id' => (string) $data->customer->id,
        'name' => isset($data->customer->default_address->first_name) ? ($data->customer->default_address->first_name . ' ' . $data->customer->default_address->last_name) : $data->customer->email,
        'field_email' => isset($data->customer->email) ? $data->customer->email : 'None',
        'field_phone' => isset($data->customer->phone) ? $data->customer->phone : '',
      ];

      if (isset($data->customer->default_address->id)) {
        $full_state_list = \Drupal::service('address.subdivision_repository')->getList(['US']);
        $state = array_search($data->customer->default_address->province, $full_state_list);

        if (!$state) {
          $state = '';
        }

        $info['field_address'] = [
          'given_name' => $data->customer->default_address->first_name,
          'family_name' => $data->customer->default_address->last_name,
          'address_line1' => $data->customer->default_address->address1,
          'address_line2' => $data->customer->default_address->address2,
          'locality' => $data->customer->default_address->city,
          'administrative_area' => $state,
          'postal_code' => $data->customer->default_address->zip,
          'country_code' => 'US',
        ];
      } else {
        $info['field_address'] = [];
      }

      if (count($cids) > 0) {
        $customer = CustomerEntity::load(array_keys($cids)[0]);

        foreach ($info as $key => $val) {
          $customer->set($key, $val);
        }
        $customer->save();

        return $customer->id();
      } else {
        $customer = CustomerEntity::create($info);
        $customer->save();

        return $customer->id();
      }

    } else {
      return FALSE;
    }
  }

  private function createTickets(\stdClass $data, $customer_entity_id = 0) {
    $processed_tickets = [];

    if (isset($data->line_items) && count($data->line_items)) {
      foreach ($data->line_items as $line_item) {

        for ($i = 1; $i <= $line_item->quantity; ++$i) {
          $query = \Drupal::entityQuery('ticket_entity')
          ->condition('field_ticket_number', $i)
          ->condition('field_inventory_line_item_id', (string) $line_item->id)
          ->condition('field_order_id', (string) $data->id)
          ->condition('field_source', (string) 'Shopify')
          ->sort('created', 'DESC')
          ->range(0,1);
          
          $tids = $query->execute();
          
          $name = "Shopify-{$data->id}-{$line_item->id}-{$i}.{$line_item->quantity}";
          $hash = hash('sha256', $name);

          $info = [
            'name' => $name,
            'field_source' => 'Shopify',
            'field_order_id' => (string) $data->id,
            'field_inventory_line_item_id' => (string) $line_item->id,
            'field_ticket_number' => $i,
            'field_amount_paid' => (double) $line_item->price,
            'field_customer_info' => $customer_entity_id ? $customer_entity_id: null,
            'field_issued_date_time' => date("Y-m-d\TH:i:s", strtotime($data->updated_at)),
            'field_ticket_characteristics' => $line_item->title . ' - ' . $line_item->variant_title,
            'field_status' => ($i <= $line_item->fulfillable_quantity) ? 'issued' : 'cancelled',
            'field_sha256' => $hash,
          ];

          if ($line_item->properties) {
            //Look for timed ticket properties
            foreach ($line_item->properties as $prop) {
              if ($prop->name == 'When') {
                $info['field_admission_date_and_time'] = $prop->value;
              }
            }
          }
  
          if (count($tids) > 0) {
            $ticket = TicketEntity::load(array_keys($tids)[0]);
    
            foreach ($info as $key => $val) {
              $ticket->set($key, $val);
            }
            $ticket->save();
            $processed_tickets[] = $ticket->id();
          } else {
            $ticket = TicketEntity::create($info);
            $ticket->save();
            $processed_tickets[] = $ticket->id();
          }  

        }         
      }  
    }    

    /* shouldn't need this
    //Set status to delete for tickets not present in line items anymore
    if ($customer_entity_id) {
      $query = \Drupal::entityQuery('ticket_entity')
      ->condition('field_customer_info', $customer_entity_id)
      ->condition('field_source', (string) 'Shopify')
      ->sort('created', 'DESC');

      if ($processed_tickets) {
        $query->condition('id', $processed_tickets, 'NOT IN');
      }
      $tids = $query->execute();
      if ($tids) {
        $ticket_entities = TicketEntity::loadMultiple($tids);
        foreach ($ticket_entities as $ticket_entity) {
          $ticket_entity->set('field_status', 'deleted');
        }
      }
    }
    */
  }

  /**
   * Handle updating of products.
   *
   * Overriding coding standards because current functionality depends on
   * current method names.
   */
  //@codingStandardsIgnoreLine
  private function webhook_products_update(\stdClass $data) {
    $entity = ShopifyProduct::loadByProductId($data->id);
    if ($entity instanceof ShopifyProduct) {
      $entity->update((array) $data);
      $entity->save();
    }
  }

  /**
   * Handle creating of products.
   *
   * Overriding coding standards because current functionality depends on
   * current method names.
   */
  //@codingStandardsIgnoreLine
  private function webhook_products_create(\stdClass $data) {
    $entity = ShopifyProduct::create((array) $data);
    $entity->save();
  }

  /**
   * Handle deleting of products.
   *
   * Overriding coding standards because current functionality depends on
   * current method names.
   */
  //@codingStandardsIgnoreLine
  private function webhook_products_delete(\stdClass $data) {
    $entity = ShopifyProduct::loadByProductId($data->id);
    if ($entity instanceof ShopifyProduct) {
      $entity->delete();
    }
  }

  /**
   * Handle creating of collections.
   *
   * Overriding coding standards because current functionality depends on
   * current method names.
   */
  //@codingStandardsIgnoreLine
  private function webhook_collections_create(\stdClass $data) {
    shopify_collection_create($data, TRUE);
  }

  /**
   * Handle updating of collections.
   *
   * Overriding coding standards because current functionality depends on
   * current method names.
   */
  //@codingStandardsIgnoreLine
  private function webhook_collections_update(\stdClass $data) {
    // Note: This does not currently get hit because of a bug in Shopify.
    // See this issue for updates: https://www.drupal.org/node/2481105
    shopify_collection_update($data, TRUE);
  }

  /**
   * Handle deleting of collections.
   *
   * Overriding coding standards because current functionality depends on
   * current method names.
   */
  //@codingStandardsIgnoreLine
  private function webhook_collections_delete(\stdClass $data) {
    $entity = shopify_collection_load($data->id);
    if ($entity instanceof Term) {
      $entity->delete();
    }
  }

}