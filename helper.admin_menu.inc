<?php

/**
 * @file
 * Administration menu integration for the Helper module.
 */

/**
 * Implements hook_admin_menu_cache_info().
 */
function helper_admin_menu_cache_info() {
  $caches['bootstrap'] = array(
    'title' => t('Bootstrap'),
    'callback' => 'helper_admin_menu_cache_flush_bootstrap',
  );
  $caches['entity'] = array(
    'title' => t('Entity'),
    'callback' => 'entity_info_cache_clear',
  );
  $caches['field'] = array(
    'title' => t('Field'),
    'callback' => 'field_cache_clear',
  );
  $caches['all'] = array(
    'title' => t('ALL THE THINGS!'),
    'callback' => 'drupal_flush_all_caches',
  );
  return $caches;
}

/**
 * Cache callback to clear the bootstrap cache.
 */
function helper_admin_menu_cache_flush_bootstrap() {
  cache_clear_all('*', 'cache_bootstrap', TRUE);
}

/**
 * Implements hook_admin_menu_output_alter().
 */
function helper_admin_menu_output_alter(array &$content) {
  // @todo Remove when http://drupal.org/node/1899474 is fixed.
  unset($content['icon']['icon']['devel-variables']);
  if (module_exists('devel')) {
    $devel_tree = menu_tree_all_data('devel');
    if ($devel_tree = admin_menu_links_menu($devel_tree)) {
      $content['icon']['icon']['devel'] = array(
        '#title' => t('Devel'),
        '#href' => '#',
        '#options' => array(
          'external' => TRUE,
        ),
      ) + $devel_tree;
    }
  }
}
