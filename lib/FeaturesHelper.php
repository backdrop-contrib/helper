<?php

class FeaturesHelper {

  public static function revertModules(array $modules) {
    module_load_include('inc', 'features', 'features.export');
    features_include(TRUE);

    $items = array();
    $states = features_get_component_states($modules, TRUE, TRUE);
    foreach ($states as $module_name => $components) {
      foreach ($components as $component => $state) {
         if (in_array($state, $restore_states)) {
          if (!isset($items[$module_name])) {
	    $items[$module_name] = array();
	  }
	  $items[$module_name][] = $component;
        }
      }
    }

    if (!empty($items)) {
      return features_revert($items);
    }
  }

}
