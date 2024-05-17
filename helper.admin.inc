<?php

/**
 *
 */
function helper_settings_form($form) {
  $tweaks = helper_get_tweaks();
  $options = ArrayHelper::extractNestedValuesToArray($tweaks, array('label'));
  $enabled = ArrayHelper::extractNestedValuesToArray($tweaks, array('enabled'));
  $enabled = backdrop_map_assoc(array_keys(array_filter($enabled)));
  $form['#config'] = 'helper.settings';
  $form['tweaks'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Enabled tweaks'),
    '#options' => $options,
    '#default_value' => $enabled,
  );

  $form['#submit'][] = 'helper_settings_form_submit';
  return system_settings_form($form);
}

function helper_settings_form_submit() {
  backdrop_flush_all_caches();
}
