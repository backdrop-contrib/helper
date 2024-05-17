<?php

class ProfileHelper {
  public static function installVariables(array $variables) {
    // @todo: This should be replaced with a config solution.
    backdrop_set_message(t('ProfileHelper::installVariables() is deprecated. We need to adapt it for config instead. Please file an issue.'), 'warning');
    /*
    foreach ($variables as $name => $value) {
      variable_set($name, $value);
    }
    */
  }

  public static function installBlocks(array $blocks, $theme = NULL) {
    if (!isset($theme)) {
      $theme = config_get('system.core', 'theme_default');
    }

    $query = db_insert('block');
    $query->fields(array('module', 'delta', 'theme', 'status', 'weight', 'region', 'visibility', 'pages', 'title', 'cache'));
    foreach ($blocks as $block) {
      $block += array(
        'theme' => $theme,
        'status' => 1,
        'weight' => 0,
        'visibility' => BLOCK_VISIBILITY_NOTLISTED,
        'pages' => '',
        'title' => '',
        'cache' => BACKDROP_NO_CACHE,
      );
      $query->values($block);
    }

    if (!empty($blocks)) {
      $query->execute();
    }
  }

  public static function installFields(array $fields) {
    foreach ($fields as $index => $field) {
      if (field_info_field($field['field_name'])) {
        continue;
      }
      $fields[$index] = field_create_field($field);
    }
    return $fields;
  }
}
