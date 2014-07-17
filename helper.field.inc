<?php

/**
 * @file
 * Field integration for the Helper module.
 */

/**
 * Implements hook_field_create_instance().
 */
function helper_field_create_instance($instance) {
  // Clear the menu cache whenever a field instances is created.
  variable_set('menu_rebuild_needed', TRUE);
}

/**
 * Implements hook_field_delete_instance().
 */
function helper_field_delete_instance($instance) {
  // Clear the menu cache whenever a field instances is deleted.
  variable_set('menu_rebuild_needed', TRUE);
}

/**
 * A clone of field_delete_field() that works for inactive fields.
 */
function _helper_field_delete_field($field) {
  $instances = field_read_instances(array('field_id' => $field['id']), array('include_inactive' => 1));
  foreach ($instances as $instance) {
    _helper_field_delete_instance($instance);
  }

  // Mark field data for deletion.
  module_invoke($field['storage']['module'], 'field_storage_delete_field', $field);

  // Mark the field for deletion.
  db_update('field_config')->fields(array('deleted' => 1))->condition('field_name', $field['field_name'])->execute();

  // Clear the cache.
  field_cache_clear(TRUE);

  module_invoke_all('field_delete_field', $field);
}

/**
 * A clone of field_delete_instance() that works for inactive fields.
 */
function _helper_field_delete_instance($instance) {
  // Mark the field instance for deletion.
  db_update('field_config_instance')->fields(array('deleted' => 1))->condition('field_name', $instance['field_name'])->condition('entity_type', $instance['entity_type'])->condition('bundle', $instance['bundle'])->execute();

  // Clear the cache.
  field_cache_clear();

  // Mark instance data for deletion.
  $field = field_info_field_by_id($instance['field_id']);
  module_invoke($field['storage']['module'], 'field_storage_delete_instance', $instance);

  // Let modules react to the deletion of the instance.
  module_invoke_all('field_delete_instance', $instance);
}

function _helper_purge_inactive_deleted_fields() {
  $instances = field_read_instances(array('deleted' => 1), array('include_deleted' => 1, 'include_inactive' => 1));
  foreach ($instances as $instance) {
    watchdog('helper', 'Purging inactive and deleted field instance @field_name:@entity_type:@bundle.', array('@field_name' => $instance['field_name'], '@entity_type' => $instance['entity_type'], '@bundle' => $instance['bundle']));
    field_purge_instance($instance);
  }
  $fields = field_read_fields(array('deleted' => 1), array('include_deleted' => 1, 'include_inactive' => 1));
  foreach ($fields as $field) {
    watchdog('helper', 'Purging inactive and deleted field @field_name.', array('@field_name' => $field['field_name']));
    field_purge_field($field);
  }
}
