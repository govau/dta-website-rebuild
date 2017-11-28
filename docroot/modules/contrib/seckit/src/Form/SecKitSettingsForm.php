<?php

namespace Drupal\seckit\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a form to collect security check configuration.
 */
class SecKitSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'seckit_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['seckit.settings'];
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $module_path = drupal_get_path('module', 'seckit');
    $form['#attached']['library'][] = 'seckit/listener';

    $config = \Drupal::config('seckit.settings');

    // main description
    $args = array(
      ':browserscope' => 'http://www.browserscope.org/?category=security',
      '@browserscope' => 'Browserscope',
    );
    $form['seckit_description'] = array(
      '#markup' => t('This module provides your website with various options to mitigate risks of common web application vulnerabilities like Cross-site Scripting, Cross-site Request Forgery and Clickjacking. It also has some options to improve your SSL/TLS security and fixes Drupal 6 core Upload module issue leading to an easy exploitation of an old Internet Explorer MIME sniffer HTML injection vulnerability. Note that some security features are not supported by all browsers. You may find this out at <a href=":browserscope">@browserscope</a>.', $args),
    );

    // main fieldset for XSS
    $form['seckit_xss'] = array(
      '#type' => 'details',
      '#title' => t('Cross-site Scripting'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => TRUE,
      '#description' => t('Configure levels and various techniques of protection from cross-site scripting attacks'),
    );

    // fieldset for Content Security Policy (CSP)
    $args = array(
      ':wiki' => 'https://wiki.mozilla.org/Security/CSP',
      '@wiki' => 'Mozilla Wiki',
    );

    $form['seckit_xss']['csp'] = array(
      '#type' => 'details',
      '#title' => t('Content Security Policy'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_xss.csp.checkbox')),
      '#description' => t('Content Security Policy is a policy framework that allows to specify trustworthy sources of content and to restrict its capabilities. You may read more about it at <a href=":wiki">@wiki</a>.', $args),
    );
    // CSP enable/disable
    $form['seckit_xss']['csp']['checkbox'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('seckit_xss.csp.checkbox'),
      '#title' => t('Send HTTP response header'),
      '#return_value' => 1,
      '#description' => t('Send Content-Security-Policy (official), X-Content-Security-Policy (supported by Mozilla Firefox and IE10) and X-WebKit-CSP (supported by Google Chrome and Safari) HTTP response headers with the list of Content Security Policy directives.'),
    );
    // CSP report-only mode
    $form['seckit_xss']['csp']['report-only'] = array(
      '#type' => 'checkbox',
      '#default_value' => $config->get('seckit_xss.csp.report-only'),
      '#title' => t('Report Only'),
      '#return_value' => 1,
      '#description' => t('Use Content Security Policy in report-only mode. In this case, violations of policies will only be reported, not blocked. Use this while configuring policies. Reports are logged.'),
    );
    // CSP description
    $keywords = array(
      "'none' - block content from any source",
      "'self' - allow content only from your domain",
      "'unsafe-inline' - allow specific inline content (note, that it is supported by a subset of directives)",
      "'unsafe-eval' - allow a set of string-to-code API which is restricted by default (supported by script-src directive)"
    );

    $wildcards = array(
      '* - load content from any source',
      '*.example.com - load content from example.com and all its subdomains',
      'example.com:* - load content from example.com via any port.  Otherwise, it will use your website default port'
    );

    $args = array(
      '@keywords' => $this->_getItemsList($keywords),
      '@wildcards' => $this->_getItemsList($wildcards),
      ':spec' => 'http://www.w3.org/TR/CSP/',
      '@spec' => 'specification page',
    );

    $description = '<strong>' . t('Directives') . '</strong><br />';
    $description .= t('Set up security policy for different types of content. Don\'t use www prefix. Keywords are: @keywords Wildcards (*) are allowed: @wildcards More information is available at <a href=":spec">@spec</a>.', $args);
    $form['seckit_xss']['csp']['description'] = array(
      '#markup' => $description,
    );
    // CSP default-src directive
    $form['seckit_xss']['csp']['default-src'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.default-src'),
      '#title' => 'default-src',
      '#description' => t("Specify security policy for all types of content, which are not specified further (frame-ancestors excepted). Default is 'self'."),
    );
    // CSP script-src directive
    $form['seckit_xss']['csp']['script-src'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.script-src'),
      '#title' => 'script-src',
      '#description' => t('Specify trustworthy sources for &lt;script&gt; elements.'),
    );
    // CSP object-src directive
    $form['seckit_xss']['csp']['object-src'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.object-src'),
      '#title' => 'object-src',
      '#description' => t('Specify trustworthy sources for &lt;object&gt;, &lt;embed&gt; and &lt;applet&gt; elements.'),
    );
    // CSP style-src directive
    $form['seckit_xss']['csp']['style-src'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.style-src'),
      '#title' => 'style-src',
      '#description' => t('Specify trustworthy sources for stylesheets. Note, that inline stylesheets and style attributes of HTML elements are allowed.'),
    );
    // CSP img-src directive
    $form['seckit_xss']['csp']['img-src'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.img-src'),
      '#title' => 'img-src',
      '#description' => t('Specify trustworthy sources for &lt;img&gt; elements.'),
    );
    // CSP media-src directive
    $form['seckit_xss']['csp']['media-src'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.media-src'),
      '#title' => 'media-src',
      '#description' => t('Specify trustworthy sources for &lt;audio&gt; and &lt;video&gt; elements.'),
    );
    // CSP frame-src directive
    $form['seckit_xss']['csp']['frame-src'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.frame-src'),
      '#title' => 'frame-src',
      '#description' => t('Specify trustworthy sources for &lt;iframe&gt; and &lt;frame&gt; elements. This directive is deprecated and will be replaced by child-src. It is recommended to use the both the frame-src and child-src directives until all browsers you support recognize the child-src directive.'),
    );
    // CSP child-src directive
    $form['seckit_xss']['csp']['child-src'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.child-src'),
      '#title' => 'child-src',
      '#description' => t('Specify trustworthy sources for &lt;iframe&gt; and &lt;frame&gt; elements as well as for loading Workers.'),
    );
    // CSP font-src directive
    $form['seckit_xss']['csp']['font-src'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.font-src'),
      '#title' => 'font-src',
      '#description' => t('Specify trustworthy sources for @font-src CSS loads.'),
    );
    // CSP connect-src directive
    $form['seckit_xss']['csp']['connect-src'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.connect-src'),
      '#title' => 'connect-src',
      '#description' => t('Specify trustworthy sources for XMLHttpRequest, WebSocket and EventSource connections.'),
    );

    $report_default = !empty($config->get('seckit_xss.report-uri')) ? $config->get('seckit_xss.report-uri') : SECKIT_CSP_REPORT_URL;
    // CSP report-uri directive
    $form['seckit_xss']['csp']['report-uri'] = array(
      '#type' => 'textfield',
      '#maxlength' => 1024,
      '#default_value' => $report_default,
      '#title' => 'report-uri',
      '#description' => t('Specify a URL (relative to the Drupal root) to which user-agents will report CSP violations. Use the default value, unless you have set up an alternative handler for these reports. Defaults to <code>' . SECKIT_CSP_REPORT_URL . '</code> which logs the report data.'),
    );
    // CSP policy-uri directive
    $form['seckit_xss']['csp']['policy-uri'] = array(
      '#type' => 'textfield',
      '#maxlength'=> 1024,
      '#default_value' => $config->get('seckit_xss.csp.policy-uri'),
      '#title' => 'policy-uri',
      '#description' => t("Specify a URL (relative to the Drupal root) for a file containing the (entire) policy. <strong>All other directives will be omitted</strong> by Security Kit, as <code>policy-uri</code> may only be defined in the <em>absence</em> of other policy definitions in the <code>X-Content-Security-Policy</code> HTTP header. The MIME type for this URI <strong>must</strong> be <code>text/x-content-security-policy</code>, otherwise user-agents will enforce the policy <code>allow 'none'</code>  instead."),
    );

    // fieldset for X-XSS-Protection
    $form['seckit_xss']['x_xss'] = array(
      '#type' => 'details',
      '#title' => t('X-XSS-Protection header'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => $config->get('seckit_xss.x_xss.select')  != SECKIT_X_XSS_DISABLE,
      '#description' => t('X-XSS-Protection HTTP response header controls Microsoft Internet Explorer, Google Chrome and Apple Safari internal XSS filters.'),
    );
    // options for X-XSS-Protection
    $x_xss_protection_options = array(
      SECKIT_X_XSS_DISABLE => $config->get('seckit_xss.x_xss.seckit_x_xss_option_disable', t('Disabled')),
      SECKIT_X_XSS_0 => $config->get('seckit_xss.x_xss.seckit_x_xss_option_0', '0'),
      SECKIT_X_XSS_1 => $config->get('seckit_xss.x_xss.seckit_x_xss_option_1', '1;'),
      SECKIT_X_XSS_1_BLOCK => $config->get('seckit_xss.x_xss.seckit_x_xss_option_1_block', '1; mode=block'),
    );
    // configure X-XSS-Protection
    $args = array(
      ':link' => 'http://hackademix.net/2009/11/21/ies-xss-filter-creates-xss-vulnerabilities',
      '@link' => 'IE\'s XSS filter security flaws in past',
    );
    $items = array(
      array('#markup' => t('Disabled - XSS filter will work in default mode. Enabled by default')),
      array('#markup' => t('0 - XSS filter will be disabled for a website. It may be useful because of <a href=":link">@link</a>', $args)),
      array('#markup' => t('1 - XSS filter will be left enabled, and will modify dangerous content')),
      array('#markup' => t('1; mode=block - XSS filter will be left enabled, but it will block entire page instead of modifying dangerous content'))
    );

    $args = array(
      '@values' => $this->_getItemsList($items),
    );

    $form['seckit_xss']['x_xss']['select'] = array(
      '#type' => 'select',
      '#title' => t('Configure'),
      '#options' => $x_xss_protection_options,
      '#default_value' => $config->get('seckit_xss.x_xss.select'),
      '#description' => t('@values', $args),
    );

    // fieldset for X-Content-Type-Options
    $args = array(
      ':link' => 'http://blogs.msdn.com/b/ie/archive/2010/10/26/mime-handling-changes-in-internet-explorer.aspx',
      '@link' => 'MSDN article',
    );

    // We should leave this one open so users can see immediately when this is
    // disabled, as it is recommended to always be enabled.
    $form['seckit_xss']['x_content_type'] = array(
      '#type' => 'details',
      '#title' => t('X-Content-Type-Options header'),
      '#collapsible' => FALSE,
      '#open' => TRUE,
      '#tree' => TRUE,
      '#description' => t('X-Content-Type-Options HTTP response header prevents browser from upsniffing content and serving files with inappropriate MIME type. More information is available at <a href=":link">@link</a>.', $args),
    );
    // enable/disable X-Content-Type-Options
    $form['seckit_xss']['x_content_type']['checkbox'] = array(
      '#type' => 'checkbox',
      '#title' => t('Send HTTP response header'),
      '#default_value' => $config->get('seckit_xss.x_content_type.checkbox'),
      '#description' => t('Enable X-Content-Type-Options: nosniff HTTP response header.  It is HIGHLY recommended that this value always be be set to "ON" to mitigate security risks, please read the link above.'),
    );

    // main fieldset for CSRF
    $form['seckit_csrf'] = array(
      '#type' => 'details',
      '#title' => t('Cross-site Request Forgery'),
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_csrf.origin')),
      '#collapsible' => TRUE,
      '#description' => t('Configure levels and various techniques of protection from cross-site request forgery attacks'),
    );

    // enable/disable Origin
    $form['seckit_csrf']['origin'] = array(
      '#type' => 'checkbox',
      '#title' => t('HTTP Origin'),
      '#default_value' => $config->get('seckit_csrf.origin'),
      '#description' => t('Check Origin HTTP request header.'),
    );
    // Origin whitelist
    $form['seckit_csrf']['origin_whitelist'] = array(
      '#type' => 'textfield',
      '#title' => t('Allow requests from'),
      '#default_value' => $config->get('seckit_csrf.origin_whitelist'),
      '#size' => 90,
      '#maxlength' => 255,
      '#description' => t('Comma separated list of trustworthy sources. Do not enter your website URL - it is automatically added. Syntax of the source is: [protocol] :// [host] : [port] . E.g, http://example.com, https://example.com, https://www.example.com, http://www.example.com:8080'),
    );

    // main fieldset for Clickjacking
    $form['seckit_clickjacking'] = array(
      '#type' => 'details',
      '#title' => t('Clickjacking'),
      '#collapsible' => TRUE,
      '#tree' => FALSE,
      '#open' => TRUE,
      '#description' => t('Configure levels and various techniques of protection from Clickjacking/UI Redressing attacks'),
    );

    $form['seckit_clickjacking']['x_frame_options'] = array(
      '#type' => 'details',
      '#title' => t('X-Frame-Options header'),
      '#collapsible' => TRUE,
      '#collapsed' => ($config->get('seckit_clickjacking.x_frame') != SECKIT_X_FRAME_DISABLE),
      '#tree' => FALSE,
      '#description' => t('Configure the X-Frame-Options HTTP header'),
    );

    // options for X-Frame-Options
    $x_frame_options = array(
      SECKIT_X_FRAME_DISABLE => t('Disabled'),
      SECKIT_X_FRAME_SAMEORIGIN => 'SameOrigin',
      SECKIT_X_FRAME_DENY => 'Deny',
      SECKIT_X_FRAME_ALLOW_FROM => 'Allow-From',
    );
    // configure X-Frame-Options
    $items = array('Disabled - turn off X-Frame-Options', 'SameOrigin - browser allows all the attempts of framing website within its domain. Enabled by default', 'Deny - browser rejects any attempt of framing website', 'Allow-From - browser allows framing website only from specified source');
    $args = array(
      '@values' => $this->_getItemsList($items),
      ':msdn' => 'http://blogs.msdn.com/b/ie/archive/2009/01/27/ie8-security-part-vii-clickjacking-defenses.aspx',
      '@msdn' => 'MSDN article',
      ':spec' => 'http://tools.ietf.org/html/draft-ietf-websec-x-frame-options-01',
      '@spec' => 'specification',
    );
    $form['seckit_clickjacking']['x_frame_options']['x_frame'] = array(
      '#type' => 'select',
      '#title' => t('X-Frame-Options'),
      '#options' => $x_frame_options,
      '#default_value' => $config->get('seckit_clickjacking.x_frame'),
      '#description' => t('X-Frame-Options HTTP response header controls browser\'s policy of frame rendering. Possible values: @values You may read more about it at <a href=":msdn">@msdn</a> or <a href=":spec">@spec</a>.', $args),
      // Non-tree (we skip a parent).
      '#parents' => array(
        'seckit_clickjacking',
        'x_frame',
      ),
    );

    // Origin value for "Allow-From" option.
    $form['seckit_clickjacking']['x_frame_options']['x_frame_allow_from'] = array(
      '#type' => 'textarea',
      '#title' => t('Allow-From'),
      '#default_value' => $config->get('seckit_clickjacking.x_frame_allow_from'),
      '#description' => t('Origin URIs (as specified by RFC 6454) for the "X-Frame-Options: Allow-From" value. One per line. Example, http://domain.com'),
      '#states' => array(
        'required' => array(
          'select[name="seckit_clickjacking[x_frame]"]' => array('value' => SECKIT_X_FRAME_ALLOW_FROM),
        ),
      ),
      // Non-tree (we skip a parent).
      '#parents' => array(
        'seckit_clickjacking',
        'x_frame_allow_from',
      ),
    );

    $args = array(
      ':noscript' => 'https://noscript.net/',
      '@noscript' => 'NoScript',
    );
    // fieldset for JavaScript settings. non-#tree.
    $form['seckit_clickjacking']['javascript'] = array(
      '#type' => 'details',
      '#title' => t('JavaScript-based protection'),
      '#collapsible' => TRUE,
      '#collapsed' => !empty($config->get('seckit_clickjacking.js_css_noscript')),
      '#tree' => FALSE,
      '#description' => t('Warning: With this enabled, the site <em>will not work at all</em> for users who have JavaScript disabled (e.g. users running the popular <a href=":noscript">@noscript</a> browser extension, if they haven\'t whitelisted your site).', $args),
    );

    // enable/disable JS + CSS + Noscript protection
    $args = array(
      ':eduardovela' => 'http://sirdarckcat.blogspot.com/',
      '@eduardovela' => 'Eduardo Vela',
      '%js' => t('seckit.document_write.js'),
      '%write' => t('document.write()'),
      '%stop' => t('stop SecKit protection'),
      '%css' => t('seckit.no_body.css'),
      '%display' => t('display: none'),
    );
    $form['seckit_clickjacking']['javascript']['js_css_noscript'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable JavaScript + CSS + Noscript protection'),
      '#return_value' => 1,
      '#default_value' => $config->get('seckit_clickjacking.js_css_noscript'),
      '#description' => t('Enable protection via JavaScript, CSS and &lt;noscript&gt; tag. This is the most efficient Clickjacking prevention technique. If website is not being framed, %js starts commenting with <em>document.write()</em> and stops when the comment %stop is reached. Thus %css, which hides the page body, is ignored. If particularly this JavaScript file is being blocked (with XSS filter of Internet Explorer 8 or Safari), %css applies <em>display: none</em> to <em>body</em>, hiding it. If JavaScript is disabled within browser, it shows a special message. Credits for this trick go to <a href=":eduardovela">@eduardovela</a>.', $args),
      '#parents' => array(
        'seckit_clickjacking',
        'js_css_noscript',
      ),
    );

    // custom text for "disabled JavaScript" message
    $form['seckit_clickjacking']['javascript']['noscript_message'] = array(
      '#type' => 'textfield',
      '#title' => t('Custom text for disabled JavaScript message'),
      '#default_value' => $config->get('seckit_clickjacking.noscript_message'),
      '#description' => t('This message will be shown to user when JavaScript is disabled or unsupported in his browser. Default is "Sorry, you need to enable JavaScript to visit this website."'),
      '#states' => array(
        'required' => array(
          'input[name="seckit_clickjacking[js_css_noscript]"]' => array('checked' => TRUE),
        ),
      ),
      '#parents' => array(
        'seckit_clickjacking',
        'noscript_message',
      ),
    );

    // main fieldset for SSL/TLS
    $form['seckit_ssl'] = array(
      '#type' => 'details',
      '#title' => t('SSL/TLS'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_ssl.hsts')),
      '#description' => t('Configure various techniques to improve security of SSL/TLS'),
    );

    // enable/disable HTTP Strict Transport Security (HSTS)
    $args = array(
      ':wiki' => 'http://en.wikipedia.org/wiki/HTTP_Strict_Transport_Security',
      '@wiki' => 'Wikipedia',
    );

    $form['seckit_ssl']['hsts'] = array(
      '#type' => 'checkbox',
      '#title' => t('HTTP Strict Transport Security'),
      '#description' => t('Enable Strict-Transport-Security HTTP response header. HTTP Strict Transport Security (HSTS) header is proposed to prevent eavesdropping and man-in-the-middle attacks like SSLStrip, when a single non-HTTPS request is enough for credential theft or hijacking. It forces browser to connect to the server in HTTPS-mode only and automatically convert HTTP links into secure before sending request. <a href=":wiki">@wiki</a> has more information about HSTS', $args),
      '#default_value' => $config->get('seckit_ssl.hsts'),
    );
    // HSTS max-age directive
    $form['seckit_ssl']['hsts_max_age'] = array(
      '#type' => 'textfield',
      '#title' => t('Max-Age'),
      '#description' => t('Specify Max-Age value in seconds. It sets period when user-agent should remember receipt of this header field from this server. Default is 1000.'),
      '#default_value' => $config->get('seckit_ssl.hsts_max_age'),
      '#states' => array(
        'required' => array(
          'input[name="seckit_ssl[hsts]"]' => array('checked' => TRUE),
        ),
      ),
    );
    // HSTS includeSubDomains directive
    $form['seckit_ssl']['hsts_subdomains'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include Subdomains'),
      '#description' => t('Force HTTP Strict Transport Security for all subdomains. If enabled, HSTS policy will be applied for all subdomains, otherwise only for the main domain.'),
      '#default_value' => $config->get('seckit_ssl.hsts_subdomains'),
    );

    // HSTS preload directive
    $args = array(
      ':hsts_preload_list' => 'https://hstspreload.appspot.com/',
      '@hsts_preload_list' => 'HSTS Preload list',
    );
    $form['seckit_ssl']['hsts_preload'] = array(
      '#type' => 'checkbox',
      '#title' => t('Preload'),
      '#description' => t('If you intend to submit your domain to the <a href=":hsts_preload_list">@hsts_preload_list</a>, you will need to enable the preload flag as confirmation. Don\'t submit your domain unless you\'re sure that you can support HTTPS for the long term, as this action cannot be undone.', $args),
      '#return_value' => 1,
      '#default_value' => $config->get('seckit_ssl.hsts_preload'),
    );

    // main fieldset for various
    $form['seckit_various'] = array(
      '#type' => 'details',
      '#title' => t('Miscellaneous'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => !empty($config->get('seckit_various.from_origin')),
      '#description' => t('Configure miscellaneous unsorted security enhancements'),
    );

    // enable/disable From-Origin
    $args = array(
      ':spec' => 'http://www.w3.org/TR/from-origin/',
      '@spec' => 'specification',
    );

    $form['seckit_various']['from_origin'] = array(
      '#type' => 'checkbox',
      '#title' => t('From-Origin'),
      '#default_value' => $config->get('seckit_various.from_origin'),
      '#description' => t('Enable From-Origin HTTP response header. This forces user-agent to retrieve embedded content from your site only to listed destination. More information is available at <a href=":spec">@spec</a> page.', $args),
    );
    // From-Origin destination
    $items = array(
      'same - allow loading of content only from your site. Default value.',
      'serialized origin - address of trustworthy destination. For example, http://example.com, https://example.com, https://www.example.com, http://www.example.com:8080'
    );
    $args = array(
      '@items' => $this->_getItemsList($items)
    );

    $form['seckit_various']['from_origin_destination'] = array(
      '#type' => 'textfield',
      '#title' => t('Allow loading content to'),
      '#default_value' => $config->get('seckit_various.from_origin_destination'),
      '#size' => 90,
      '#description' => t('Trustworthy destination. Possible variants are: @items', $args),
      '#states' => array(
        'required' => array(
          'input[name="seckit_various[from_origin]"]' => array('checked' => TRUE),
        ),
      ),
    );
    // Disable autocomplete on login and registration forms.
    $form['seckit_various']['disable_autocomplete'] = array(
      '#type' => 'checkbox',
      '#title' => t('Disable autocomplete on login and registration forms'),
      '#default_value' => $config->get('seckit_various.disable_autocomplete'),
      '#description' => t('Prevent the browser from populating login/registration form fields using its autocomplete functionality. This as populated fields may contain sensitive information, facilitating unauthorized access.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // if From-Origin is enabled, it should be explicitly set
    $from_origin_enable = $form_state->getValue(array('seckit_various', 'from_origin'));
    $from_origin_destination = $form_state->getValue(array('seckit_various', 'from_origin_destination'));
    if ($from_origin_enable && !$from_origin_destination) {
      $form_state->setErrorByName('seckit_various][from_origin_destination', t('You have to set up trustworthy destination for From-Origin HTTP response header. Default is same.'));
    }
    // if X-Frame-Options is set to Allow-From, it should be explicitly set
    $x_frame_value = $form_state->getValue(array('seckit_clickjacking', 'x_frame'));
    if ($x_frame_value == SECKIT_X_FRAME_ALLOW_FROM) {
      $x_frame_allow_from = $form_state->getValue(array('seckit_clickjacking', 'x_frame_allow_from'));
      if (!$this->_seckit_explode_value($x_frame_allow_from)) {
        $form_state->setErrorByName('seckit_clickjacking][x_frame_allow_from', t('You must specify a trusted Origin for the Allow-From value of the X-Frame-Options HTTP response header.'));
      }
    }
    // if HTTP Strict Transport Security is enabled, max-age must be specified.
    // HSTS max-age should only contain digits.
    $hsts = $form_state->getValue(array('seckit_ssl', 'hsts'));
    $hsts_max_age = $form_state->getValue(array('seckit_ssl', 'hsts_max_age'));
    if ($hsts && !$hsts_max_age) {
      $form_state->setErrorByName('seckit_ssl][hsts_max_age', t('You have to set up Max-Age value for HTTP Strict Transport Security. Default is 1000.'));
    }
    if (preg_match('/[^0-9]/', $hsts_max_age)) {
      $form_state->setErrorByName('seckit_ssl][hsts_max_age', t('Only digits are allowed in HTTP Strict Transport Security Max-Age field.'));
    }
    // if JS + CSS + Noscript Clickjacking protection is enabled,
    // custom text for disabled JS must be specified
    $js_css_noscript_enable = $form_state->getValue(array('seckit_clickjacking', 'js_css_noscript'));
    $noscript_message = $form_state->getValue(array('seckit_clickjacking', 'noscript_message'));
    if ($js_css_noscript_enable && !$noscript_message) {
      $form_state->setErrorByName('seckit_clickjacking][noscript_message', t('You have to set up Custom text for disabled JavaScript message when JS + CSS + Noscript protection is enabled.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $list = [];
    $this->buildAttributeList($list, $form_state->getValues());
    $config = $this->config('seckit.settings');

    foreach ($list as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();

    $from_origin_enable = $form_state->getValue('seckit_various', 'from_origin');
    $x_content_type_options_enable = $form_state->getValue('seckit_xss', 'x_content_type', 'checkbox');
    $file_system = file_default_scheme();
    if ($from_origin_enable && ($file_system == 'public')) {
      $msg = t('From-Origin HTTP response header will not be served for files because of public file system. It is recommended to enable private file system to ensure provided by From-Origin security.');
      drupal_set_message($msg, 'warning');
    }
    if ($x_content_type_options_enable && ($file_system == 'public')) {
      $msg = t('X-Content-Type-Options HTTP response header will not be served for files because of public file system. It is recommended to enable private file system to ensure provided by X-Content-Type-Options security.');
      drupal_set_message($msg, 'warning');
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Build a list from given items.
   */
  public function _getItemsList($items) {
    $list = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );
    return drupal_render($list);
  }

  /**
   * Build the configuration form value list.
   */
  protected function buildAttributeList(
    array &$list = [],
    array $rawAttributes = [],
    $currentName = '')
  {
    foreach ($rawAttributes as $key => $rawAttribute) {
      $name = $currentName ? $currentName . '.' . $key:$key;
      if (in_array($name,['op','form_id','form_token','form_build_id','submit'])){
        continue;
      }
      if (is_array($rawAttribute)) {
        $this->buildAttributeList($list, $rawAttribute, $name);
      } else {
        $list[$name] = $rawAttribute;
      }
    }
  }

  /**
   * Converts a multi-line configuration option to an array.
   * Sanitises by trimming whitespace, and filtering empty options.
   */
  protected function _seckit_explode_value($string) {
    $values = explode("\n", $string);
    return array_values(array_filter(array_map('trim', $values)));
  }
}
