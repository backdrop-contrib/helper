<?php

class FieldHelper {

  public static function getValues($entity_type, $entity, $field_name, $column = NULL) {
    if (!empty($entity) && $items = field_get_items($entity_type, $entity, $field_name)) {
      if (isset($column)) {
        $items = ArrayHelper::extractNestedValuesToArray($items, array($column));
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
      return ArrayHelper::extractNestedValuesToArray($items, array($column));
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

  public static function viewValue($entity_type, $entity, $field_name, $delta = 0, $display = array(), $langcode = NULL) {
    $output = array();

    if ($item = static::getValue($entity_type, $entity, $field_name, NULL, $delta)) {
      // Determine the langcode that will be used by language fallback.
      $langcode = field_language($entity_type, $entity, $field_name, $langcode);

      // Push the item as the single value for the field, and defer to
      // field_view_field() to build the render array for the whole field.
      $clone = clone $entity;
      $clone->{$field_name}[$langcode] = array($item);
      $elements = field_view_field($entity_type, $clone, $field_name, $display, $langcode);

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
}
