<?php

/**
 *
 */
function helper_settings_form($form) {
  $tweaks = module_invoke_all('helper_tweak_info');
  drupal_alter('helper_tweak_info', $tweaks);

  $options = ArrayHelper::extractNestedValuesToArray($tweaks, array('label'));
  $form['helper_tweaks'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Enabled tweaks'),
    '#options' => $options,
    '#default_value' => variable_get('helper_tweaks', array()) + drupal_map_assoc(array_keys($tweaks)),
  );

  $form['#submit'][] = 'helper_settings_form_submit';
  return system_settings_form($form);
}

function helper_settings_form_submit() {
  drupal_flush_all_caches();
}
