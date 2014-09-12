<?php

class FieldHelper {

  public static function getValues($entity_type, $entity, $field_name, $column = NULL) {
    if (!empty($entity) && $items = field_get_items($entity_type, $entity, $field_name)) {
      if (isset($column)) {
        $value_parents = is_array($column) ? $column : array($column);
        $items = ArrayHelper::extractNestedValuesToArray($items, $value_parents);
      }

      return $items;
    }
  }

  public static function getValue($entity_type, $entity, $field_name, $column = NULL, $delta = 0) {
    $items = static::getValues($entity_type, $entity, $field_name, $column);
    if (isset($items[$delta])) {
      return $items[$delta];
    }
  }


  public static function extractValues($entity_type, $entity, $field, $column) {
    if (!empty($entity) && $items = field_get_items($entity_type, $entity, $field)) {
      $value_parents = is_array($column) ? $column : array($column);
      return ArrayHelper::extractNestedValuesToArray($items, $value_parents);
    }
    return array();
  }

  public static function getValueDelta($entity_type, $entity, $field, $column, $value) {
    if (!empty($entity) && $items = field_get_items($entity_type, $entity, $field)) {
      foreach ($items as $delta => $item) {
        if (isset($item[$column]) && $item[$column] == $value) {
          return $delta;
        }
      }
    }

    return FALSE;
  }

  public static function hasDelta($entity_type, $entity, $field, $delta) {
    if (!empty($entity) && $items = field_get_items($entity_type, $entity, $field)) {
      return !empty($items[$delta]);
    }
  }

  public static function viewValues($entity_type, $entity, $field_name, $display = array(), $langcode = NULL) {
    if (module_exists('render_cache') && function_exists('render_cache_view_field')) {
      return render_cache_view_field($entity_type, $entity, $field_name, $display, $langcode);
    }
    else {
      return field_view_field($entity_type, $entity, $field_name, $display, $langcode);
    }
  }

  public static function viewValue($entity_type, $entity, $field_name, $delta = 0, $display = array(), $langcode = NULL) {
    $output = array();

    if ($item = static::getValue($entity_type, $entity, $field_name, NULL, $delta)) {
      // Determine the langcode that will be used by language fallback.
      $langcode = field_language($entity_type, $entity, $field_name, $langcode);

      // Push the item as the single value for the field, and defer to
      // field_view_field() to build the render array for the whole field.
      $clone = clone $entity;
      $clone->{$field_name}[$langcode] = array($item);
      $elements = static::viewValues($entity_type, $clone, $field_name, $display, $langcode);

      // Extract the part of the render array we need.
      $output = isset($elements[0]) ? $elements[0] : array();
      if (isset($elements['#access'])) {
        $output['#access'] = $elements['#access'];
      }
    }

    return $output;
  }

  /**
   * Return an array of fields with a certain type.
   *
   * @param string $type
   *   The type of field to look for.
   *
   * @return array
   *   An array of fields, keyed by field name.
   */
  public static function getFieldsByType($type) {
    $fields = function_exists('field_info_field_map') ? field_info_field_map() : field_info_fields();
    return array_filter($fields, function ($field) use ($type) {
      return $field['type'] == $type;
    });
  }

  public static function getReferencingFields($field_name = NULL) {
    $results = array();

    if ($cache = cache_get('helper:referencing-field-info', 'cache_field')) {
      $results = $cache->data;
    }
    else {
      $entity_info = entity_get_info();
      $base_tables = array();
      foreach ($entity_info as $type => $type_info) {
        if (!empty($type_info['base table']) && !empty($type_info['entity keys']['id'])) {
          $base_tables[$type_info['base table']] = array('type' => $type, 'column' => $type_info['entity keys']['id']);
        }
      }

      $fields = field_info_fields();
      foreach ($fields as $field_name => $field) {
        // Cannot rely on entityreference fields having correct foreign key info.
        // @todo Remove when http://drupal.org/node/1969018 is fixed.
        if ($field['type'] != 'entityreference') {
          foreach ($field['foreign keys'] as $foreign_key) {
            if (isset($base_tables[$foreign_key['table']])) {
              $base_table = $base_tables[$foreign_key['table']];
              if ($column = array_search($base_table['column'], $foreign_key['columns'])) {
                $results[$field_name] = $base_table;
              }
            }
          }
        }
        else {
          // Special handling for entity reference fields.
          $type = $field['settings']['target_type'];
          if (!empty($entity_info[$type]['base table']) && !empty($entity_info[$type]['entity keys']['id'])) {
            $results[$field_name] = array('type' => $type, 'column' => 'target_id');
          }
        }
      }

      cache_set('helper:referencing-field-info', $results, 'cache_field');
    }

    if (isset($field_name)) {
      return !empty($results[$field_name]) ? $results[$field_name] : FALSE;
    }
    else {
      return $results;
    }
  }

  public static function readFieldByID($id, $include_deleted = TRUE, $include_inactive = TRUE) {
    $fields = field_read_fields(array('id' => $id), array('include_deleted' => $include_deleted, 'include_inactive' => $include_inactive));
    return !empty($fields) ? reset($fields) : FALSE;
  }

  public static function readInstanceByID($id, $include_deleted = TRUE, $include_inactive = TRUE) {
    $fields = field_read_instances(array('id' => $id), array('include_deleted' => $include_deleted, 'include_inactive' => $include_inactive));
    return !empty($fields) ? reset($fields) : FALSE;
  }

  /**
   * Delete a field instance.
   *
   * This is a clone of field_delete_instance() that works for inactive fields.
   *
   * @param array $instance
   *   The field instance definition. This may be deleted or inactive.
   * @param bool $purge
   * @param bool $field_cleanup
   *
   * @throws FieldException
   */
  public static function deleteInstance(array $instance, $purge = TRUE, $field_cleanup = TRUE) {
    $field = static::readFieldById($instance['field_id']);
    $instance = static::readInstanceById($instance['id']);

    if (empty($instance)) {
      throw new FieldException();
    }
    if (!module_exists($field['storage']['module'])) {
      throw new FieldException("The {$field['storage']['module']} module needs to be enabled in order to delete an instance ID {$instance['id']} from field ID {$field['id']}.");
    }

    if (empty($instance['deleted'])) {
      // Mark the field instance for deletion.
      db_update('field_config_instance')->fields(array('deleted' => 1))->condition('field_name', $instance['field_name'])->condition('entity_type', $instance['entity_type'])->condition('bundle', $instance['bundle'])->execute();

      // Clear the cache.
      field_cache_clear();

      // Mark instance data for deletion.
      module_invoke($field['storage']['module'], 'field_storage_delete_instance', $instance);

      // Let modules react to the deletion of the instance.
      module_invoke_all('field_delete_instance', $instance);

      watchdog('helper', "Marked field instance ID {$instance['id']} for deletion.");
    }

    if ($purge) {
      static::purgeInstanceData($instance);
      static::purgeInstance($instance);
    }

    if ($field_cleanup && !field_read_instances(array('field_id' => $field['id']), array('include_deleted' => TRUE, 'include_inactive' => TRUE))) {
      static::deleteField($field);
    }
  }

  /**
   * Purge all data from a field instance.
   *
   * @param array $instance
   *   The field instance definition. This may be deleted or inactive.
   */
  public static function purgeInstanceData(array $instance) {
    $field = static::readFieldByID($instance['field_id']);
    $data_table = _field_sql_storage_tablename($field);

    // Ensure the entity caches are cleared for the changed entities.
    if ($ids = db_query("SELECT entity_id FROM {$data_table} WHERE entity_type = :type AND bundle = :bundle", array(':type' => $instance['entity_type'], ':bundle' => $instance['bundle']))->fetchCol()) {
      entity_get_controller($instance['entity_type'])->resetCache($ids);
      db_delete($data_table)
        ->condition('entity_type', $instance['entity_type'])
        ->condition('bundle', $instance['bundle'])
        ->execute();
    }

    $revision_table = _field_sql_storage_revision_tablename($field);
    if (db_table_exists($revision_table)) {
      db_delete($revision_table)
        ->condition('entity_type', $instance['entity_type'])
        ->condition('bundle', $instance['bundle'])
        ->execute();
    }

    watchdog('helper', "Purged data for field instance ID {$instance['id']}.");
  }

  public static function purgeInstance(array $instance) {
    $field = static::readFieldByID($instance['field_id']);
    $instance = static::readInstanceById($instance['id']);

    if (empty($instance['deleted'])) {
      throw new FieldException("Field instance not yet marked as deleted.");
    }
    if (!module_exists($field['storage']['module'])) {
      throw new FieldException("The {$field['storage']['module']} module needs to be enabled in order to delete an instance ID {$instance['id']} from field ID {$field['id']}.");
    }

    db_delete('field_config_instance')->condition('id', $instance['id'])->execute();

    // Notify the storage engine.
    module_invoke($field['storage']['module'], 'field_storage_purge_instance', $instance);

    // Clear the cache.
    field_info_cache_clear();

    // Invoke external hooks after the cache is cleared for API consistency.
    module_invoke_all('field_purge_instance', $instance);

    watchdog('helper', "Field instance ID {$instance['id']} completely removed.");
  }

  /**
   * Delete a field.
   *
   * This is a clone of field_delete_field() that works for inactive fields.
   *
   * @param array $field
   *   The field definition. This may be deleted or inactive.
   * @param bool $purge
   *
   * @throws FieldException
   */
  public static function deleteField(array $field, $purge = TRUE) {
    $field = static::readFieldById($field['id']);

    if (empty($fields)) {
      throw new FieldException();
    }
    if (!module_exists($field['storage']['module'])) {
      throw new FieldException("The {$field['storage']['module']} module needs to be enabled in order to delete field ID {$field['id']}.");
    }

    if ($instances = field_read_instances(array('field_id' => $field['id']), array('include_deleted' => TRUE, 'include_inactive' => TRUE))) {
      foreach ($instances as $instance) {
        static::deleteInstance($instance, $purge, FALSE);
      }
    }

    if (empty($field['deleted'])) {
      // Mark field data for deletion.
      module_invoke($field['storage']['module'], 'field_storage_delete_field', $field);

      // Mark the field for deletion.
      db_update('field_config')->fields(array('deleted' => 1))->condition('field_name', $field['field_name'])->execute();

      // Clear the cache.
      field_cache_clear(TRUE);

      module_invoke_all('field_delete_field', $field);

      watchdog('helper', "Marked field ID {$field['id']} for deletion.");
    }

    if ($purge) {
      static::purgeField($field);
    }
  }

  public static function purgeField(array $field) {
    $field = static::readFieldById($field['id']);

    if (empty($field['deleted'])) {
      throw new FieldException("Field not yet marked as deleted.");
    }
    if (!module_exists($field['storage']['module'])) {
      throw new FieldException("The {$field['storage']['module']} module needs to be enabled in order to delete field ID {$field['id']}.");
    }

    $instances = field_read_instances(array('field_id' => $field['id']), array('include_deleted' => TRUE, 'include_inactive' => TRUE));
    if (count($instances) > 0) {
      throw new FieldException(t('Attempt to purge a field @field_name that still has instances.', array('@field_name' => $field['field_name'])));
    }

    db_delete('field_config')->condition('id', $field['id'])->execute();

    // Notify the storage engine.
    module_invoke($field['storage']['module'], 'field_storage_purge_field', $field);

    // Clear the cache.
    field_info_cache_clear();

    // Invoke external hooks after the cache is cleared for API consistency.
    module_invoke_all('field_purge_field', $field);

    watchdog('helper', "Field ID {$field['id']} completely removed.");
  }
}
