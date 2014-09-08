<?php

class ArrayHelper {

  public static function getNestedValue($item, array $parents, &$key_exists = NULL) {
    $ref = $item;
    foreach ($parents as $parent) {
      if (is_array($ref) && array_key_exists($parent, $ref)) {
        $ref = &$ref[$parent];
      }
      elseif (is_object($ref) && property_exists($ref, $parent)) {
        $ref = $ref->$parent;
      }
      elseif (is_object($ref) && method_exists($ref, '__get') && $ref->__get($parent) !== NULL) {
        // Support objects that override the __get magic method.
        // This also doesn't support if $ref->$parent exists but is set to NULL.
        $ref = $ref->$parent;
      }
      else {
        $key_exists = FALSE;
        return NULL;
      }
    }
    $key_exists = TRUE;
    return $ref;
  }

  public static function filterByNestedValue(array $items, array $parents, $value) {
    $return = array();
    foreach ($items as $key => $item) {
      $key_exists = FALSE;
      $found_value = static::getNestedValue($item, $parents, $key_exists);
      if ($key_exists) {
        if (is_array($value) && in_array($found_value, $value)) {
          $return[$key] = $item;
        }
        elseif ($found_value == $value) {
          $return[$key] = $item;
        }
      }
    }
    return $return;
  }

  public static function extractNestedValuesToArray(array $items, array $value_parents, array $key_parents = NULL) {
    $return = array();
    foreach ($items as $index => $item) {
      $key_exists = FALSE;
      $value = static::getNestedValue($item, $value_parents, $key_exists);
      if ($key_exists) {
        $key = isset($key_parents) ? static::getNestedValue($item, $key_parents, $key_exists) : $index;
        if (!$key_exists || !isset($key)) {
          $key = $index;
        }
        $return[$key] = $value;
      }
    }
    return $return;
  }

  /**
   * Applies the callback to the keys of the given array.
   *
   * @param callable $callback
   *   Callback function to run for each key in the array.
   * @param array $array
   *   An array to run through the callback function.
   *
   * @return array
   *   Returns an array containing all the elements of $array after applying
   *   the callback function to each key.
   */
  public static function mapKeys($callback, array $array) {
    return array_combine(array_map($callback, array_combine(array_keys($array), array_keys($array))), $array);
  }

  /**
   * Filters keys of an array using a callback function.
   *
   * @param callable $callback
   *   The callback function to use. If no callback is supplied, all keys of
   *   array equal to FALSE will be removed.
   * @param array $array
   *   The array to iterate over.
   *
   * @return array
   *   Returns the filtered array.
   */
  public static function filterKeys(array $array, $callback) {
    return array_intersect_key($array, array_flip(array_filter(array_keys($array), $callback)));
  }
}
