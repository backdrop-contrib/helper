<?php

class UtilHelper {

  /**
   * Registers a unique function call for execution on shutdown.
   *
   * Wrapper for drupal_register_shutdown_function() that does not add the
   * function call if it already exists in the shutdown function stack.
   *
   * @param callable $callback
   *   The shutdown function to register.
   * @param ...
   *   Additional arguments to pass to the shutdown function.
   *
   * @return bool
   *   TRUE if the function was added, or FALSE if it was already in the stack.
   *
   * @see drupal_register_shutdown_function()
   */
  public static function registerUniqueShutdownFunction($callback = NULL) {
    $args = func_get_args();
    array_shift($args);

    $existing_callbacks = drupal_register_shutdown_function();
    foreach ($existing_callbacks as $existing_callback) {
      if ($existing_callback['callback'] === $callback && $existing_callback['arguments'] === $args) {
        return FALSE;
      }
    }

    array_unshift($args, $callback);
    call_user_func_array('drupal_register_shutdown_function', $args);
    return TRUE;
  }

  /**
   * Switch the current theme.
   *
   * @param string $theme
   *   The theme name.
   */
  public static function switchTheme($theme) {
    $themes = list_themes();

    if (!isset($themes[$theme]) || $GLOBALS['theme'] == $theme) {
      return;
    }

    $GLOBALS['theme'] = $GLOBALS['theme_key'] = $theme;

    // Find all our ancestor themes and put them in an array.
    $base_theme = array();
    $ancestor = $theme;
    while ($ancestor && isset($themes[$ancestor]->base_theme)) {
      $ancestor = $themes[$ancestor]->base_theme;
      $base_theme[] = $themes[$ancestor];
    }
    _drupal_theme_initialize($themes[$theme], array_reverse($base_theme));

    // Themes can have alter functions, so reset the drupal_alter() cache.
    drupal_static_reset('drupal_alter');
    drupal_static_reset('theme_get_registry');
  }

  /**
   * Runs a batch even if another batch is currently running.
   *
   * This is useful for running a batch inside SimpleTests.
   *
   * @param array $batch
   *   A batch array that would normally get passed to batch_set().
   */
  public function runBatch(array $batch) {
    $existing_batch = batch_get();
    $current_batch = &batch_get();
    if ($existing_batch) {
      $current_batch = NULL;
    }
    batch_set($batch);
    $current_batch['progressive'] = FALSE;
    batch_process();
    if ($existing_batch) {
      $current_batch = $existing_batch;
    }
  }

}
