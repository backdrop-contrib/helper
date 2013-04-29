<?php

function _helper_hook_info_groups() {
  // Add modulename.group.inc support for most core hooks.
  $groups['block'] = array(
    'block_configure', 'block_info', 'block_info_alter', 'block_list_alter',
    'block_save', 'block_view', 'block_view_alter',
  );
  $groups['comment'] = array(
    'comment_delete', 'comment_insert', 'comment_load', 'comment_presave',
    'comment_publish', 'comment_unpublish', 'comment_update', 'comment_view',
    'comment_view_alter',
  );
  $groups['cron'] = array(
    'cron', 'cron_queue_info', 'cron_queue_info_alter',
  );
  $groups['date'] = array(
    'date_formats', 'date_formats_alter', 'date_format_types',
    'date_format_types_alter',
  );
  $groups['entity'] = array(
    'entity_delete', 'entity_info', 'entity_info_alter', 'entity_insert',
    'entity_load', 'entity_prepare_view', 'entity_presave',
    'entity_query_alter', 'entity_update', 'entity_view', 'entity_view_alter',
  );
  $groups['file'] = array(
    'file_copy', 'file_delete', 'file_download', 'file_download_access',
    'file_download_access_alter', 'file_insert', 'file_load',
    'file_mimetype_mapping_alter', 'file_move', 'file_presave', 'file_update',
    'file_url_alter', 'file_validate',
  );
  $groups['image'] = array(
    'image_default_styles', 'image_effect_info', 'image_effect_info_alter',
    'image_styles_alter', 'image_style_delete', 'image_style_flush',
    'image_style_save', 'image_toolkits',
  );
  $groups['menu'] = array(
    'menu', 'menu_alter', 'menu_breadcrumb_alter',
    'menu_contextual_links_alter', 'menu_delete', 'menu_get_item_alter',
    'menu_insert', 'menu_link_alter', 'menu_link_delete', 'menu_link_insert',
    'menu_link_update', 'menu_local_tasks_alter', 'menu_site_status_alter',
    'menu_update',
  );
  $groups['node'] = array(
    'node_access', 'node_access_records', 'node_access_records_alter',
    'node_delete', 'node_grants', 'node_grants_alter', 'node_info',
    'node_insert', 'node_load', 'node_operations', 'node_prepare',
    'node_presave', 'node_revision_delete', 'node_search_result',
    'node_submit', 'node_type_delete', 'node_type_insert', 'node_type_update',
    'node_update', 'node_update_index', 'node_validate', 'node_view',
    'node_view_alter',
  );

  // Contrib modules.
  $groups['admin_menu'] = array(
    'admin_menu_replacements', 'admin_menu_output_build',
    'admin_menu_output_alter', 'admin_menu_map', 'admin_menu_map_alter',
    'admin_menu_cache_info',
  );

  return $groups;
}
