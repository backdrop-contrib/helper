<?php

class XmlHelper {

  /**
   * Convert a string of XML to an associative array.
   *
   * The converse of format_xml_elements().
   *
   * @param string|SimpleXmlIterator $xml
   *   The XML data to parse.
   * @param bool $simplify
   *   If simple XML elements should be converted to just an array key and value
   *   pair where possible.
   * @param array $namespaces
   *   Internal parameter used for recursion.
   *
   * @return array|bool
   *   An array representing the XML data, or FALSE if there was a failure.
   */
  public static function parseElements($xml, $simplify = TRUE, array $namespaces = NULL) {
    if (is_string($xml)) {
      $xml = new SimpleXmlIterator($xml);
      if (!$xml) {
        return FALSE;
      }
      $namespaces = $xml->getNamespaces(TRUE);
      return static::parseElements($xml, $simplify, $namespaces);
    }

    $result = array();
    $name_indexes = array();

    for ($xml->rewind(), $index = 0; $xml->valid(); $xml->next(), $index++) {
      $element = array();
      $element['name'] = $xml->key();

      foreach ($xml->current()->attributes() as $attribute_key => $attribute_value) {
        $element['attributes'][$attribute_key] = (string) $attribute_value;
      }
      if ($namespaces) {
        foreach (array_keys($namespaces) as $namespace) {
          foreach ($xml->current()->attributes($namespace, TRUE) as $attribute_key => $attribute_value) {
            $element['attributes'][$namespace . ':' . $attribute_key]= (string) $attribute_value;
          }
        }
      }

      $element['value'] = $xml->hasChildren() ? static::parseElements($xml->current(), $simplify, $namespaces) : trim((string) $xml->current());
      $result[] = $element;

      if ($simplify) {
        $name_indexes[$element['name']][] = $index;
      }
    }

    if ($simplify) {
      foreach ($name_indexes as $name => $indexes) {
        if (count($indexes) === 1) {
          $index = $indexes[0];

          // Simplify the element.
          $element = $result[$index];
          if (!isset($element['attributes']) && !is_array($element['value'])) {
            $element = $element['value'];
          }
          else {
            $element = array_diff_key($element, array('name' => NULL));
          }

          // Replace it in the array.
          $result = ArrayHelper::insertAssociativeValues($result, array($name => $element), $index);
          unset($result[$index]);
        }
      }
    }

    return $result;
  }

}
