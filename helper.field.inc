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
