<?php

/**
 * @file
 * Drush integration for the Helper module.
 */

/**
 * Implements hook_drush_cache_clear().
 */
function helper_drush_cache_clear(&$types) {
  if (drush_drupal_major_version() >= 7) {
    $types['bootstrap'] = 'helper_drush_cache_clear_bootstrap';
    // @todo Remove when http://drupal.org/node/1899468 is fixed.
    if (drush_has_boostrapped(DRUSH_BOOTSTRAP_DRUPAL_FULL)) {
      $types['entity'] = 'entity_info_cache_clear';
      $types['field'] = 'field_cache_clear';
    }
  }
}

/**
 * Clears the bootstrap cache.
 */
function helper_drush_cache_clear_bootstrap() {
  cache_clear_all('*', 'cache_bootstrap', TRUE);
}
