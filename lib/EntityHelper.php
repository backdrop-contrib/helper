<?php

class EntityHelper {

  /**
   * A lightweight version of entity save for field values only.
   */
  public static function updateFieldValues($entity_type, $entity) {
    list($id) = entity_extract_ids($entity_type, $entity);
    if (empty($id)) {
      throw new InvalidArgumentException(t('Cannot call EntityHelper::updateFieldValues() on an unsaved entity.'));
    }

    // Some modules use the original property.
    if (!isset($entity->original)) {
      $entity->original = $entity;
    }

    // Ensure that file_field_update() will not trigger additional usage.
    unset($entity->revision);

    // Invoke the field presave and update hooks.
    field_attach_presave($entity_type, $entity);
    field_attach_update($entity_type, $entity);

    // Clear the cache for this entity now.
    entity_get_controller($entity_type)->resetCache(array($id));
  }
}
