<?php

/**
 * @file
 * Contains ticket_entity.page.inc.
 *
 * Page callback for Ticket entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Ticket templates.
 *
 * Default template: ticket_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ticket_entity(array &$variables) {
  // Fetch TicketEntity Entity Object.
  $ticket_entity = $variables['elements']['#ticket_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
