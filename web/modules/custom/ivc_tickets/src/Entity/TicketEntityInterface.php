<?php

namespace Drupal\ivc_tickets\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Ticket entities.
 *
 * @ingroup ivc_tickets
 */
interface TicketEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Ticket name.
   *
   * @return string
   *   Name of the Ticket.
   */
  public function getName();

  /**
   * Sets the Ticket name.
   *
   * @param string $name
   *   The Ticket name.
   *
   * @return \Drupal\ivc_tickets\Entity\TicketEntityInterface
   *   The called Ticket entity.
   */
  public function setName($name);

  /**
   * Gets the Ticket creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Ticket.
   */
  public function getCreatedTime();

  /**
   * Sets the Ticket creation timestamp.
   *
   * @param int $timestamp
   *   The Ticket creation timestamp.
   *
   * @return \Drupal\ivc_tickets\Entity\TicketEntityInterface
   *   The called Ticket entity.
   */
  public function setCreatedTime($timestamp);

}
