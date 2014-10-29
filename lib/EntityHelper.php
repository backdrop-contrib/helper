<?php

class EntityHelper {

  /**
   * A wrapper around entity_load() to load a single entity by ID.
   *
   * @param string $entity_type
   *   The entity type of $entity.
   * @param int $entity_id
   *   The ID of the entity to load.
   *
   * @return object
   *   The entity object, or FALSE on failure.
   *
   * @see entity_load()
   */
  public static function loadSingle($entity_type, $entity_id) {
    $entities = entity_load($entity_type, array($entity_id));
    return reset($entities);
  }

  /**
   * Load a single entity revision.
   *
   * @param string $entity_type
   *   The entity type of $entity.
   * @param int $entity_id
   *   The ID of the entity to load.
   * @param int $revision_id
   *   The ID of the revision to load.
   *
   * @return object
   *   The entity object of the specific revision, or FALSE on failure.
   *
   * @see entity_load()
   */
  public static function loadRevision($entity_type, $entity_id, $revision_id) {
    $conditions = array();
    if ($revision_key = static::entityTypeHasProperty($entity_type, array('entity keys', 'revision'))) {
      $conditions[$revision_key] = $revision_id;
    }
    $entities = entity_load($entity_type, array($entity_id), $conditions);
    return reset($entities);
  }

  public static function loadByCondition($entity_type, array $conditions) {
    $entities = entity_load($entity_type, FALSE, $conditions);
    return reset($entities);
  }

  public static function entityTypeHasProperty($entity_type, array $parents) {
    if ($info = entity_get_info($entity_type)) {
      return drupal_array_get_nested_value($info, $parents);
    }
  }

  /**
   * Remove the empty field values from an entity.
   *
   * We run this on migrations because empty field values are only removed when
   * an entity is submitted via the UI and forms, and not programmatically.
   *
   * @param string $entity_type
   *   An entity type.
   * @param object $entity
   *   An entity object.
   */
  public static function removeEmptyFieldValues($entity_type, $entity) {
    // Invoke field_default_submit() which will filter out empty field values.
    $form = $form_state = array();
    _field_invoke_default('submit', $entity_type, $entity, $form, $form_state);
  }

  /**
   * Remove invalid field value deltas from an entity.
   *
   * @param string $entity_type
   *   An entity type.
   * @param object $entity
   *   An entity object.
   */
  public static function removeInvalidFieldDeltas($entity_type, $entity) {
    list(, , $bundle) = entity_extract_ids($entity_type, $entity);
    $instances = field_info_instances($entity_type, $bundle);
    foreach (array_keys($instances) as $field_name) {
      if (!empty($entity->{$field_name})) {
        $field = field_info_field($field_name);
        if ($field['cardinality'] != FIELD_CARDINALITY_UNLIMITED) {
          foreach ($entity->{$field_name} as $langcode => $items) {
            if (count($items) > $field['cardinality']) {
              $entity->{$field_name}[$langcode] = array_slice($items, 0, $field['cardinality']);
            }
          }
        }
      }
    }
  }

  /**
   * A lightweight version of entity save for field values only.
   *
   * @param string $entity_type
   *   The entity type of $entity.
   * @param object $entity
   *   The entity object to update.
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

  /**
   * An lightest-weight version of entity save that invokes field storage.
   *
   * @param string $entity_type
   *   The entity type of $entity.
   * @param object $entity
   *   The entity object to update.
   * @param array $fields
   *   (optional) An optional array of field names that if provided will only
   *   cause those specific fields to be saved, if values are provided.
   */
  public static function updateFieldValuesStorage($entity_type, $entity, array $fields = array()) {
    list($id, , $bundle) = entity_extract_ids($entity_type, $entity);
    if (empty($id)) {
      throw new InvalidArgumentException(t('Cannot call EntityHelper::updateFieldValues() on an unsaved entity.'));
    }

    // Collect the storage backends used by the remaining fields in the entities.
    $storages = array();
    foreach (field_info_instances($entity_type, $bundle) as $instance) {
      $field = field_info_field_by_id($instance['field_id']);
      $field_id = $field['id'];
      $field_name = $field['field_name'];

      // Check if we care about saving this field or not.
      if (!empty($fields) && !in_array($field_name, $fields)) {
        continue;
      }

      // Leave the field untouched if $entity comes with no $field_name property,
      // but empty the field if it comes as a NULL value or an empty array.
      // Function property_exists() is slower, so we catch the more frequent
      // cases where it's an empty array with the faster isset().
      if (isset($entity->$field_name) || property_exists($entity, $field_name)) {
        // Collect the storage backend if the field has not been written yet.
        if (!isset($skip_fields[$field_id])) {
          $storages[$field['storage']['type']][$field_id] = $field_id;
        }
      }
    }

    // Field storage backends save any remaining unsaved fields.
    foreach ($storages as $storage => $storage_fields) {
      $storage_info = field_info_storage_types($storage);
      module_invoke($storage_info['module'], 'field_storage_write', $entity_type, $entity, FIELD_STORAGE_UPDATE, $storage_fields);
    }

    // Clear the cache for this entity now.
    entity_get_controller($entity_type)->resetCache(array($id));
  }

  /**
   * Return an array of all the revision IDs of a given entity.
   *
   * @param string $entity_type
   *   The entity type of $entity.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return array
   *   An array of revision IDs associated with entity.
   */
  public static function getAllRevisionIDs($entity_type, $entity_id) {
    $info = entity_get_info($entity_type);

    if (empty($info['entity keys']['id']) || empty($info['entity keys']['revision']) || empty($info['revision table'])) {
      return array();
    }

    $id_key = $info['entity keys']['id'];
    $revision_key = $info['entity keys']['revision'];

    $query = db_select($info['revision table'], 'revision');
    $query->addField('revision', $revision_key);
    $query->condition('revision.' . $id_key, $entity_id);
    return $query->execute()->fetchCol();
  }

  /**
   * Return a render API array of all operations associated with an entity.
   *
   * This depends on modules providing operations as contextual links
   * under the 'base path' of the entity.
   *
   * @param string $entity_type
   *   The entity type of $entity.
   * @param object $entity
   *   The entity object.
   * @param string $base_path
   *   (optional) If the base path of this entity type cannot be automatically
   *   derived from entity_uri(), then a manual override can be provided.
   */
  public static function getOperationLinks($entity_type, $entity, $base_path = NULL) {
    $build = array(
      '#theme' => 'links__operations__' . $entity_type,
      '#links' => array(),
      '#attributes' => array('class' => array('links inline')),
    );

    list($entity_id) = entity_extract_ids($entity_type, $entity);

    // Attempt to extract the base path from the entity URI.
    if (!isset($base_path)) {
      $uri = entity_uri($entity_type, $entity);
      if (empty($uri['path'])) {
        return array();
      }
      $base_path = preg_replace('/\/' . preg_quote($entity_id) . '\b.*/', '', $uri['path']);
    }

    $items = menu_contextual_links($entity_type, $base_path, array($entity_id));
    $links = array();
    foreach ($items as $class => $item) {
      $class = drupal_html_class($class);
      $links[$class] = array(
        'title' => $item['title'],
        'href' => $item['href'],
      );
      $item['localized_options'] += array('query' => array());
      $item['localized_options']['query'] += drupal_get_destination();
      $links[$class] += $item['localized_options'];
    }
    $build['#links'] = $links;

    drupal_alter('contextual_links_view', $build, $items);

    if (empty($links)) {
      $build['#printed'] = TRUE;
    }

    return $build;
  }

  public static function getBundleLabel($entity_type, $bundle) {
    $info = entity_get_info($entity_type);
    return !empty($info['bundles'][$bundle]['label']) ? $info['bundles'][$bundle]['label'] : FALSE;
  }

  public static function getBundleOptions($entity_type) {
    $info = entity_get_info($entity_type);
    return !empty($info['bundles']) ? ArrayHelper::extractNestedValuesToArray($info['bundles'], array('label')) : array();
  }

  public static function getViewModeOptions($entity_type, $bundle = NULL, $include_disabled = TRUE) {
    $view_modes = array();
    $info = entity_get_info($entity_type);

    if (!empty($info['fieldable'])) {
      $view_modes['default'] = t('Default');
    }

    if (!empty($info['view modes'])) {
      $view_modes += ArrayHelper::extractNestedValuesToArray($info['view modes'], array('label'));
    }

    // Filter out disabled view modes if requested, and a bundle was provided.
    if (isset($bundle) && !$include_disabled) {
      $view_mode_settings = field_view_mode_settings($entity_type, $bundle);
      foreach ($view_modes as $view_mode => $label) {
        if (empty($view_mode_settings[$view_mode]['custom_settings'])) {
          unset($view_modes[$view_mode]);
        }
      }
    }

    return $view_modes;
  }

  public static function view($entity_type, $entity, $view_mode = 'default', $langcode = NULL, $page = NULL) {
    if ($output = static::viewMultiple($entity_type, array($entity), $view_mode, $langcode, $page)) {
      return reset($output);
    }
    else {
      return array();
    }
  }

  public static function viewMultiple($entity_type, array $entities, $view_mode = 'default', $langcode = NULL, $page = NULL) {
    if (empty($entities)) {
      return array();
    }

    if (!function_exists('entity_view')) {
      throw new Exception("Cannot use EntityHelper::viewMultiple() without the Entity API module enabled.");
    }

    $output = entity_view($entity_type, $entities, $view_mode, $langcode, $page);

    // Workaround for file_entity module that does not have the patch in
    // https://www.drupal.org/node/2365821 applied yet.
    if ($entity_type === 'file' && isset($output['files'])) {
      $output = array('file' => reset($output));
    }

    return !empty($output[$entity_type]) ? $output[$entity_type] : array();
  }
}
