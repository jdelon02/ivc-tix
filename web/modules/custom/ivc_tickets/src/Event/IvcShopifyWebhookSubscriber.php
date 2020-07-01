<?php

namespace Drupal\ivc_tickets\Event;

use Drupal\shopify\Event\ShopifyWebhookSubscriber as BaseSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class IvcShopifyWebhookSubscriber.
 *
 * Provides the webhook subscriber functionality.
 */
class IvcShopifyWebhookSubscriber  extends BaseSubscriber implements EventSubscriberInterface {
  private function webhook_orders_create(\stdClass $data) {
    \Drupal::logger('ivc_tickets.webhook')->info(t('<strong>Topic:</strong> @topic<br />
    <strong>Data:</strong> @data.', [
      '@topic' => 'Order Created',
      '@data' => var_export($data, TRUE),
    ]));    
  }
}