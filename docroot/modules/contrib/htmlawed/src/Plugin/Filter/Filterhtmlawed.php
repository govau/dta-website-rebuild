<?php

/**
 * @file
 * Contains Drupal\htmlawed\Plugin\Filter\Filterhtmlawed
 */

namespace Drupal\htmlawed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides for use of htmLawed
 * (www.bioinformatics.org/phplabware/internal_utilities/htmLawed)
 * to restrict and purify/correct HTML to make content secure,
 * and standard- and admin. policy-compliant.
 *
 * @Filter(
 *   id = "filter_htmlawed",
 *   title = @Translation("htmLawed HTML filter and purifier"),
 *   description = @Translation("Use the htmLawed filter to restrict and purify HTML for compliance with admin. policy and standards and for security"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   weight = 50,
 *   settings = {
 *     "config" = "'safe' => 1, 'elements' => 'a, em, strong, cite, code, ol, ul, li, dl, dt, dd, br, p', 'deny_attribute' => 'id, style'",
 *     "spec" = "",
 *     "help" = @Translation("Allowed HTML tags: <a>, <em>, <strong>, <cite>, <code>, <ol>, <ul>, <li>, <dl>, <dt>, <dd>, <br>, <p>"),
 *     "helplong" = @Translation("These HTML tags are allowed: <a>, <em>, <strong>, <cite>, <code>, <ol>, <ul>, <li>, <dl>, <dt>, <dd>, <br>, <p>. Javascript and the 'id' and 'style' HTML attributes are not permitted."),
 *   }
 * )
 */

class Filterhtmlawed extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
  
    // Use htmLawed filter settings for the $config and $spec arguments to htmLawed();
    // use default values if needed.
    $htmLawed_settings = $this->settings;
    $config = 0;
    if (!empty($htmLawed_settings['config'])) {
      eval('$config = array(' . $htmLawed_settings['config'] . ');');
    }
    if (!is_array($config)) {
      $config = array('safe'=>1, 'elements'=>'a, em, strong, cite, code, ol, ul, li, dl, dt, dd, br, p', 'deny_attribute'=>'id, style');
    }
    
    // If PHP code blocks are to be preserved, hide the special characters
    // like '<' of '<?php'.The 'save_php' parameter is NOT an htmLawed parameter per se.
    if (!empty($config['save_php'])) {
      $text = preg_replace_callback('`<\?php(.+?)\?>|<\?php(.*?)$`sm', function($m){return "\x83?php". str_replace(array('<', '>', '&'), array('&lt;', '&gt;', '&amp;'), $m[1]). (substr($m[0], -2) == '?>' ? "?\84" : '');}, $text);
    }
    
    // If Libraries module (API 3.x) is enabled, use htmLawed library through it;
    // else use the htmLawed library provided with the htmLawed module.
    $module_path = drupal_get_path('module', 'htmlawed');
    if (function_exists('libraries_load') && ($library = libraries_load('htmLawed')) && !empty($library['loaded']) && function_exists('htmLawed')) {
      $doc_path = libraries_get_path('htmLawed') . '/';
      $Libraries_api_used = 1;
    }
    else {
      include_once ("$module_path/htmLawed/htmLawed.php");
    }

    // htmLawed filtering.
    $text = htmLawed($text, $config, $htmLawed_settings['spec']);
    
    // In case Drupal's teaser-break is in use;
    // since htmLawed corrects HTML comments to use the right format.
    $text = str_replace('<!--break -->', '<!--break-->', $text);
    
    // Handle any PHP code preservation.
    if (!empty($config['save_php'])) {
      $text = preg_replace_callback('`\x83\?php(.+?)\?\x84|\x83\?php(.*?)$`sm', function($m){return "<?php". str_replace(array('&amp;', '&lt;', '&gt;'), array('&', '<', '>'), $m[1]). (substr($m[0], -2) == "?\x84" ? "?>" : '');}, $text);
    }
    
    // Return value.
    $result = new FilterProcessResult($text);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $htmLawed_settings = $this->settings;
    $form['config'] = array(
      '#prefix' => t('<a href=":url">Help</a>', array(':url' => Url::fromUri('base:admin/help/htmlawed')->toString())),
      '#type' => 'textarea',
      '#rows' => '2',
      '#title' => $this->t('Config.'),
      '#default_value' => $htmLawed_settings['config'],
      '#description' => $this->t("Comma-separated, quoted key-value pairs that will be used for htmLawed's %config parameter", array('%config' => 'config')),
    );
    $form['spec'] = array(
      '#type' => 'textarea',
      '#rows' => '2',
      '#title' => $this->t('Spec.'),
      '#default_value' => $htmLawed_settings['spec'],
      '#description' => $this->t("(Optional) Value for htmLawed's %spec parameter", array('%spec' => 'spec')),
    );
    $form['help'] = array(
      '#type' => 'textarea',
      '#rows' => '2',
      '#title' => $this->t('Short tip'),
      '#default_value' => $htmLawed_settings['help'],
      '#description' => $this->t('Brief information for users'),
    );
    $form['helplong'] = array(
      '#type' => 'textarea',
      '#rows' => '4',
      '#title' => $this->t('Long tip'),
      '#default_value' => $htmLawed_settings['helplong'],
      '#description' => $this->t('More detailed information for users'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */  
  public function tips($long = FALSE) {
    $htmLawed_settings = $this->settings;
    $help = !$long ? Html::escape($htmLawed_settings['help']) : Html::escape($htmLawed_settings['helplong']);
    $help = !empty($help) ? $help : (!$long ? t('HTML markup is restricted/corrected with the htmLawed filter.') : t('HTML markup is restricted/corrected with the @htmLawed filter for compliance with admin. policy and standards and for security. More details about the restrictions in effect may be available elsewhere, such as in the text of the filter-tips of text formats that use htmLawed and on the forms for configuring text formats.', array('@htmLawed' => \Drupal::l('htmLawed', Url::fromUri('http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed')))) . (!\Drupal::currentUser()->hasPermission('administer filters') ? '' : t(' For information on configuring the htmLawed filter, visit the htmLawed module @help section.', array('@help' => \Drupal::l(t('help'), Url::fromUri('base:admin/help/htmlawed'))))));
    return $help;
  }
}
