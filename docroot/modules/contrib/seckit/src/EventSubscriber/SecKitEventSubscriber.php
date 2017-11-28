<?php

namespace Drupal\seckit\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Utility\Xss;

class SecKitEventSubscriber implements EventSubscriberInterface {

  protected $config;

  /**
   * Request object.
   *
   * @var Request
   */
  protected $request;

  /**
   * Response object.
   *
   * @var Response
   */
  protected $response;

  public function __construct() {
    $this->config = \Drupal::config('seckit.settings');
  }

  public function onKernelRequest(GetResponseEvent $event) {
    $this->request = $event->getRequest();

    // execute necessary functions
    if ($this->config->get('seckit_csrf.origin')) {
      $this->_seckit_origin($event);
    }
  }

  public function onKernelResponse(FilterResponseEvent $event) {
    $this->response = $event->getResponse();

    // execute necessary functions
    if ($this->config->get('seckit_xss.csp.checkbox')) {
      $this->_seckit_csp();
    }
    if ($this->config->get('seckit_xss.x_xss.select')) {
      $this->_seckit_x_xss($this->config->get('seckit_xss.x_xss.select'));
    }
    if ($this->config->get('seckit_clickjacking.js_css_noscript')) {
      $this->_seckit_js_css_noscript();
    }
    if ($this->config->get('seckit_ssl.hsts')) {
      $this->_seckit_hsts();
    }
    if ($this->config->get('seckit_various.from_origin')) {
      $this->_seckit_from_origin();
    }

    $this->_seckit_x_content_type_options($this->config->get('seckit_xss.x_content_type.checkbox'));

    // Always call this (regardless of the setting) since if it's disabled it may
    // be necessary to actively disable the Drupal core clickjacking defense.
    $this->_seckit_x_frame($this->config->get('seckit_clickjacking.x_frame'));
  }

  /**
   * Aborts HTTP request upon invalid 'Origin' HTTP request header.
   *
   * When included in an HTTP request, the Origin header indicates the origin(s)
   * that caused the user agent to issue the request. This helps to protect
   * against CSRF attacks, as we can abort requests with an unapproved origin.
   *
   * Applies to all HTTP request methods except GET and HEAD.
   *
   * Requests which do not include an 'Origin' header must always be allowed,
   * as (a) not all user-agents support the header, and (b) those that do may
   * include it or omit it at their discretion.
   *
   * Note that (a) will become progressively less of a factor over time --
   * CSRF attacks depend upon convincing a user agent to send a request, and
   * there is no particular motivation for users to prevent their web browsers
   * from sending this header; so as people upgrade to browsers which support
   * 'Origin', its effectiveness increases.
   *
   * Implementation of Origin is based on specification draft available at
   * http://tools.ietf.org/html/draft-abarth-origin-09
   */
  public function _seckit_origin($event) {
    // Allow requests without an 'Origin' header, or with a 'null' origin.
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    if (!$origin || $origin === 'null') {
      return;
    }
    // Allow command-line requests.
    if (PHP_SAPI === 'cli') {
      return;
    }
    // Allow GET and HEAD requests.
    $method = $this->request->getMethod();
    if (in_array($method, array('GET', 'HEAD'), TRUE)) {
      return;
    }

    // Allow requests from whitelisted Origins.
    global $base_root;
    global $base_url;

    $whitelist = explode(',', $this->config->get('seckit_csrf.origin_whitelist'));
    // Default origin is always allowed.
    $whitelist[] = $base_url;
    if (in_array($origin, $whitelist, TRUE)) {
      return;
      // n.b. RFC 6454 allows Origins to have more than one value (each
      // separated by a single space).  All values must be on the whitelist
      // (order is not important).  We intentionally do not handle this
      // because the feature has been confirmed as a design mistake which
      // user agents do not utilise in practice.  For details, see
      // http://lists.w3.org/Archives/Public/www-archive/2012Jun/0001.html
      // and https://www.drupal.org/node/2406075
    }
    // The Origin is invalid, so we deny the request.
    // Clean the POST data first, as drupal_access_denied() may render a page
    // with forms which check for their submissions.
    $args = array(
      '@ip'     => $this->request->getClientIp(),
      '@origin' => $origin,
    );

    $message = 'Possible CSRF attack was blocked. IP address: @ip, Origin: @origin.';
    $warning = t($message, $args);
    \Drupal::logger('seckit')->warning($message, $args);

    $event->setResponse(new Response(t('Access denied'), Response::HTTP_FORBIDDEN));
  }

  /**
   * Sends Content Security Policy HTTP headers.
   *
   * Header specifies Content Security Policy (CSP) for a website,
   * which is used to allow/block content from selected sources.
   *
   * Based on specification available at http://www.w3.org/TR/CSP/
   */
  public function _seckit_csp() {
    // Get default/set options.
    $csp_report_only = $this->config->get('seckit_xss.csp.policy-uri');
    $csp_default_src = $this->config->get('seckit_xss.csp.default-src');
    $csp_script_src  = $this->config->get('seckit_xss.csp.script-src');
    $csp_object_src  = $this->config->get('seckit_xss.csp.object-src');
    $csp_img_src     = $this->config->get('seckit_xss.csp.img-src');
    $csp_media_src   = $this->config->get('seckit_xss.csp.media-src');
    $csp_style_src   = $this->config->get('seckit_xss.csp.style-src');
    $csp_frame_src   = $this->config->get('seckit_xss.csp.frame-src');
    $csp_child_src   = $this->config->get('seckit_xss.csp.child-src');
    $csp_font_src    = $this->config->get('seckit_xss.csp.font-src');
    $csp_connect_src = $this->config->get('seckit_xss.csp.connect-src');
    $csp_report_uri  = $this->config->get('seckit_xss.csp.report-uri');
    $csp_policy_uri  = $this->config->get('seckit_xss.csp.policy-uri');
    // Prepare directives.
    $directives = array();

    // If policy-uri is declared, no other directives are permitted.
    /*if ($csp_report_only) {
      $directives = "policy-uri " . base_path() . $csp_report_only;
    }*/
    // Otherwise prepare directives.
    // else {
    if ($csp_default_src) {
      $directives[] = "default-src $csp_default_src";
    }
    if ($csp_script_src) {
      $directives[] = "script-src $csp_script_src";
    }
    if ($csp_object_src) {
      $directives[] = "object-src $csp_object_src";
    }
    if ($csp_style_src) {
      $directives[] = "style-src $csp_style_src";
    }
    if ($csp_img_src) {
      $directives[] = "img-src $csp_img_src";
    }
    if ($csp_media_src) {
      $directives[] = "media-src $csp_media_src";
    }
    if ($csp_frame_src) {
      $directives[] = "frame-src $csp_frame_src";
    }
    if ($csp_child_src) {
      $directives[] = "child-src $csp_child_src";
    }
    if ($csp_font_src) {
      $directives[] = "font-src $csp_font_src";
    }
    if ($csp_connect_src) {
      $directives[] = "connect-src $csp_connect_src";
    }
    if ($csp_report_uri) {
      $directives[] = "report-uri " . base_path() . $csp_report_uri;
    }
    // Merge directives.
    $directives = implode('; ', $directives);
    // }

    // send HTTP response header if directives were prepared
    if ($directives) {
      if ($csp_report_only) {
        // use report-only mode
        $this->response->headers->set('Content-Security-Policy-Report-Only', $directives); // official name
        $this->response->headers->set('X-Content-Security-Policy-Report-Only', $directives); // Firefox and IE10
        $this->response->headers->set('X-WebKit-CSP-Report-Only', $directives); // Chrome and Safari
      }
      else {
        $this->response->headers->set('Content-Security-Policy', $directives); // official name
        $this->response->headers->set('X-Content-Security-Policy', $directives); // Firefox and IE10
        $this->response->headers->set('X-WebKit-CSP', $directives); // Chrome and Safari
      }
    }
  }

  /**
   * Sends X-XSS-Protection HTTP header.
   *
   * X-XSS-Protection controls IE8/Safari/Chrome internal XSS filter.
   */
  public function _seckit_x_xss($setting) {
    switch ($setting) {
      case SECKIT_X_XSS_0:
        $this->response->headers->set('X-XSS-Protection', '0'); // set X-XSS-Protection header to 0
        break;

      case SECKIT_X_XSS_1:
        $this->response->headers->set('X-XSS-Protection', '1'); // set X-XSS-Protection header to 1;
        break;

      case SECKIT_X_XSS_1_BLOCK:
        $this->response->headers->set('X-XSS-Protection', '1; mode=block'); // set X-XSS-Protection header to 1; mode=block
        break;


      case SECKIT_X_XSS_DISABLE:
      default: // do nothing
        break;
    }
  }

  /**
   * Sends X-Content-Type-Options HTTP response header.
   */
  public function _seckit_x_content_type_options($enabled) {
    // If we disabled this, remove the header set by core.
    if (!$enabled) {
      $this->response->headers->remove('X-Content-Type-Options');
    }

  }

  /**
   * Sends X-Frame-Options HTTP header.
   *
   * X-Frame-Options controls should browser show frames or not.
   * More information can be found at initial article about it at
   * http://blogs.msdn.com/ie/archive/2009/01/27/ie8-security-part-vii-clickjacking-defenses.aspx
   *
   * Implementation of X-Frame-Options is based on specification draft availabe at
   * http://tools.ietf.org/html/draft-ietf-websec-x-frame-options-01
   */
  public function _seckit_x_frame($setting) {
    switch ($setting) {
      case SECKIT_X_FRAME_SAMEORIGIN:
        $this->response->headers->set('X-Frame-Options', 'SameOrigin'); // set X-Frame-Options to SameOrigin
        break;

      case SECKIT_X_FRAME_DENY:
        $this->response->headers->set('X-Frame-Options', 'Deny'); // set X-Frame-Options to Deny
        break;

      case SECKIT_X_FRAME_ALLOW_FROM:
        $allowed = $this->config->get('seckit_clickjacking.x_frame_allow_from');
        $allowed = explode(',', $allowed);
        if (count($allowed) == 1) {
          $value = array_pop($allowed);
          $this->response->headers->set('X-Frame-Options', "Allow-From: $value");
        }
        // If there were multiple values, then seckit_boot() took care of it.
        break;

      case SECKIT_X_FRAME_DISABLE:
        // Make sure Drupal core does not set the header either.
        // See Drupal\Core\EventSubscriber\FinishResponseSubscriber.
        $this->response->headers->remove('X-Frame-Options');
        break;
    }
  }

  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequest', 100);
    $events[KernelEvents::RESPONSE][] = array('onKernelResponse');
    return $events;
  }


  /**
   * Enables JavaScript + CSS + Noscript Clickjacking defense.
   *
   * Closes inline JavaScript and allows loading of any inline HTML elements.
   * After, it starts new inline JavaScript to avoid breaking syntax.
   * We need it, because Drupal API doesn't allow to init HTML elements in desired sequence.
   */
  public function _seckit_js_css_noscript() {
    //@todo Consider batter solution?
    $content = $this->response->getContent();
    $head_close_position = strpos($content, '</head>');
    if ($head_close_position) {
      $content = substr_replace($content, $this->_seckit_get_js_css_noscript_code(), $head_close_position, 0);
      $this->response->setContent($content);
    }
  }

  /**
   * Gets JavaScript and CSS code.
   *
   * @return string
   */
  public function _seckit_get_js_css_noscript_code($noscript_message = NULL) {
    // Allows noscript automated testing.
    $noscript_message = $noscript_message ?
      $noscript_message :
      $this->config->get('seckit_clickjacking.noscript_message');

    $message = Xss::filter($noscript_message);
    $path = base_path() . drupal_get_path('module', 'seckit');
    return <<< EOT
        <script type="text/javascript" src="$path/js/seckit.document_write.js"></script>
        <link type="text/css" rel="stylesheet" id="seckit-clickjacking-no-body" media="all" href="$path/css/seckit.no_body.css" />
        <!-- stop SecKit protection -->
        <noscript>
        <link type="text/css" rel="stylesheet" id="seckit-clickjacking-noscript-tag" media="all" href="$path/css/seckit.noscript_tag.css" />
        <div id="seckit-noscript-tag">
          $message
        </div>
        </noscript>
EOT;
  }

  /**
   * Sends HTTP Strict-Transport-Security header (HSTS).
   *
   * The HSTS header prevents certain eavesdropping and MITM attacks like
   * SSLStrip. It forces the user-agent to send requests in HTTPS-only mode.
   * e.g.: http:// links are treated as https://
   *
   * Implementation of HSTS is based on the specification draft available at
   * http://tools.ietf.org/html/draft-hodges-strict-transport-sec-02
   */
  public function _seckit_hsts() {
    // prepare HSTS header value
    $header[] = sprintf("max-age=%d", $this->config->get('seckit_ssl.hsts_max_age'));
    if ($this->config->get('seckit_ssl.hsts_subdomains')) {
      $header[] = 'includeSubDomains';
    }

    if ($this->config->get('seckit_ssl.hsts_preload')) {
      $header[] = 'preload';
    }

    $header = implode('; ', $header);

    $this->response->headers->set('Strict-Transport-Security', $header);
  }


  /**
   * Sends From-Origin HTTP response header.
   *
   * Implementation is based on specification draft
   * available at http://www.w3.org/TR/from-origin.
   */
  public function _seckit_from_origin() {
    $value = $this->config->get('seckit_various.from_origin_destination');
    $this->response->headers->set('From-Origin', $value);
  }
}
