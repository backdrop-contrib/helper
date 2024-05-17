<?php

class DeliveryHelper {

  public static function deliverMinimalHtmlPage($result) {
    if (is_int($result)) {
      backdrop_deliver_html_page($result);
      return;
    }

    // Emit the correct charset HTTP header, but not if the page callback
    // result is NULL, since that likely indicates that it printed something
    // in which case, no further headers may be sent, and not if code running
    // for this page request has already set the content type header.
    if (isset($result) && is_null(backdrop_get_http_header('Content-Type'))) {
      backdrop_add_http_header('Content-Type', 'text/html; charset=utf-8');
    }

    // Send appropriate HTTP-Header for browsers and search engines.
    global $language;
    backdrop_add_http_header('Content-Language', $language->language);

    if (isset($result)) {
      print '<html><head><title>' . backdrop_get_title() . '</title>' . backdrop_get_css() . backdrop_get_js() . '</head>';
      print '<body>' . render($result) . '</body></html>';
    }

    backdrop_page_footer();
  }

  public static function deliverRawHtmlPage($result) {
    if (is_int($result)) {
      backdrop_deliver_html_page($result);
      return;
    }

    // Emit the correct charset HTTP header, but not if the page callback
    // result is NULL, since that likely indicates that it printed something
    // in which case, no further headers may be sent, and not if code running
    // for this page request has already set the content type header.
    if (isset($result) && is_null(backdrop_get_http_header('Content-Type'))) {
      backdrop_add_http_header('Content-Type', 'text/html; charset=utf-8');
    }

    // Send appropriate HTTP-Header for browsers and search engines.
    global $language;
    backdrop_add_http_header('Content-Language', $language->language);

    if (isset($result)) {
      print render($result);
    }

    backdrop_page_footer();
  }

  public static function deliverRedirect($result) {
    if (is_int($result)) {
      backdrop_deliver_html_page($result);
      return;
    }

    if (module_exists('redirect')) {
      // Using the redirect module instead of backdrop_goto() may allow this
      // redirect to be stored in the page cache.
      $redirect = new stdClass();
      $redirect->redirect = $result;
      redirect_redirect($redirect);
    }
    else {
      backdrop_goto($result, array(), 301);
    }
  }

  public static function deliverFileRedirect($result) {
    if (is_int($result)) {
      backdrop_deliver_html_page($result);
      return;
    }

    $uri = !empty($result->uri) ? $result->uri : $result;
    $url = file_create_url($uri);
    static::deliverRedirect($url);
  }

}
