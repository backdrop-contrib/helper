<?php

class HttpHelper {

  public static function cachedRequest($url, array $options = array(), $cache_errors = FALSE) {
    $cid = static::cachedRequestGetCid($url, $options);
    $bin = isset($options['cache']['bin']) ? $options['cache']['bin'] : 'cache';

    if ($cid && $cache = CacheHelper::get($cid, $bin)) {
      return $cache->data;
    }
    else {
      $response = drupal_http_request($url, $options);
      if (!$cache_errors && !empty($response->error)) {
        $cid = FALSE;
      }
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
