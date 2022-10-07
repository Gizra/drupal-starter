<?php

namespace Drupal\server_general;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Helper trait to extract a timestamp from an entity's date field.
 *
 * Or falls back on the entity's created date's timestamp.
 */
trait EntityDateTrait {

  /**
   * Retrieves the date field timestamp of the entity.
   *
   * If the given date field is empty, or holds an invalid date, then the
   * entity's created date's timestamp is returned instead.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $custom_date_field
   *   Custom date field. Empty string will get the entity's created time.
   *
   * @return int
   *   UNIX timestamp.
   */
  public function getFieldOrCreatedTimestamp(FieldableEntityInterface $entity, string $custom_date_field): int {
    if (!method_exists($entity, 'getCreatedTime')) {
      throw new \Exception('Not a valid entity type');
    }
    if (!$entity->hasField($custom_date_field) || $entity->{$custom_date_field}->isEmpty()) {
      return (int) $entity->getCreatedTime();
    }

    // Date field value will be in UTC as it's saved in the Database as such.
    $date_field_value = $entity->{$custom_date_field}->value;
    if ($this->isValidTimeStamp($date_field_value)) {
      return (int) $date_field_value;
    }

    $date = new DrupalDateTime($date_field_value, DateTimeItemInterface::STORAGE_TIMEZONE);
    return $date->getTimestamp();
  }

  /**
   * Check whether a string is a valid timestamp or not.
   *
   * @param string $timestamp
   *   A string which might be a timestamp.
   *
   * @return bool
   *   Whether the string is a UNIX timestamp or not.
   */
  protected function isValidTimeStamp(string $timestamp): bool {
    return ((string) (int) $timestamp === $timestamp)
      && ($timestamp <= PHP_INT_MAX)
      && ($timestamp >= ~PHP_INT_MAX);
  }

}
