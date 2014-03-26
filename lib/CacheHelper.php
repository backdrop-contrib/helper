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
    return TRUE;
  }

  /**
   * Perform a cached HTTP request.
   *
   * @param string $url
   *   A string containing a fully qualified URI.
   * @param array $options
   *   (optional) An array of options in the same format as
   *   drupal_http_request().
   *
   * @return mixed
   *   A response object in the same format as drupal_http_request().
   */
  public static function httpRequest($url, array $options = array()) {
    $cid = static::cachedRequestGetCid($url, $options);
    $bin = isset($options['cache']['bin']) ? $options['cache']['bin'] : 'cache';

    if ($cid && $cache = static::get($cid, $bin)) {
      return $cache->data;
    }
    else {
      $response = drupal_http_request($url, $options);
      if ($cid) {
        $expire = isset($options['cache']['expire']) ? $options['cache']['expire'] : CACHE_TEMPORARY;
        cache_set($cid, $response, $bin, $expire);
      }
      return $response;
    }
  }

  public static function cachedRequestGetCid($url, array $options) {
    if (isset($options['cache']) && $options['cache'] === FALSE) {
      return FALSE;
    }
    if (isset($options['cache']['cid'])) {
      return $options['cache']['cid'];
    }
    $cid_parts = array($url, serialize(array_diff_key($options, drupal_map_assoc(array('cache')))));
    return 'http-request:' . drupal_hash_base64(serialize($cid_parts));
  }
}
