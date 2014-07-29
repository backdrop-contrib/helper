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
        $expire = static::cachedRequestGetExpire($response, $options);
        if ($expire !== FALSE) {
          cache_set($cid, $response, $bin, $expire);
        }
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
    $cid_parts = array($url, serialize(array_diff_key($options, array('cache' => ''))));
    return 'http-request:' . drupal_hash_base64(serialize($cid_parts));
  }

  public static function cachedRequestGetExpire($response, $options) {
    if (isset($options['cache']['expire'])) {
      return $options['cache']['expire'];
    }
    elseif (!empty($response->headers['cache-control']) && strpos($response->headers['cache-control'], 'no-cache') !== FALSE) {
      // Respect the Cache-Control: no-cache header.
      return FALSE;
    }
    elseif (!empty($response->headers['expires']) && $expire = strtotime($response->headers['expires'])) {
      return $expire;
    }
    else {
      return CACHE_TEMPORARY;
    }
  }
}
