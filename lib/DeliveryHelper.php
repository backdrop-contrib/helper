<?php

class DeliveryHelper {

  public static function deliverMinimalHtmlPage($result) {
    if (is_int($result)) {
      return drupal_deliver_html_page($result);
    }

    // Emit the correct charset HTTP header, but not if the page callback
    // result is NULL, since that likely indicates that it printed something
    // in which case, no further headers may be sent, and not if code running
    // for this page request has already set the content type header.
    if (isset($result) && is_null(drupal_get_http_header('Content-Type'))) {
      drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
    }

    // Send appropriate HTTP-Header for browsers and search engines.
    global $language;
    drupal_add_http_header('Content-Language', $language->language);

    if (isset($result)) {
      print '<html><head><title>' . drupal_get_title() . '</title>' . drupal_get_css() . drupal_get_js() . '</head>';
      print '<body>' . render($result) . '</body></html>';
    }

    drupal_page_footer();
  }

  public static function deliverRawHtmlPage($result) {
    if (is_int($result)) {
      return drupal_deliver_html_page($result);
    }

    // Emit the correct charset HTTP header, but not if the page callback
    // result is NULL, since that likely indicates that it printed something
    // in which case, no further headers may be sent, and not if code running
    // for this page request has already set the content type header.
    if (isset($result) && is_null(drupal_get_http_header('Content-Type'))) {
      drupal_add_http_header('Content-Type', 'text/html; charset=utf-8');
    }

    // Send appropriate HTTP-Header for browsers and search engines.
    global $language;
    drupal_add_http_header('Content-Language', $language->language);

    if (isset($result)) {
      print render($result);
    }

    drupal_page_footer();
  }

}
