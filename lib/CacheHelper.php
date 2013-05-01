<?php

class CacheHelper {

  /**
   * A copy of cache_get() that respects expiration.
   *
   * @see http://drupal.org/node/534092
   */
  public static function get($cid, $bin = 'cache') {
    if ($cache = cache_get($cid, $bin)) {
      if (!static::isCacheUnexpired($cache)) {
        return FALSE;
      }
    }
    return $cache;
  }

  /**
   * A copy of cache_get_multiple() that respects expiration.
   *
   * @see http://drupal.org/node/534092
   */
  public static function getMultiple(array &$cids, $bin = 'cache') {
    $cache = cache_get_multiple($cids, $bin);
    return array_filter($cache, array(get_called_class(), 'isCacheUnexpired'));
  }

  /**
   * Check if a cache record is expired or not.
   *
   * Callback for array_filter() within CacheHelper::get() and
   * CacheHelper::getMultiple().
   *
   * @param object $cache
   *   A cache object from cache_get().
   *
   * @return bool
   *   TRUE if the cache record has not yet expired, or FALSE otherwise.
   */
  public static function isCacheUnexpired($cache) {
    if ($cache->expire > 0 && $cache->expire < REQUEST_TIME) {
      return FALSE;
    }
  }
}
