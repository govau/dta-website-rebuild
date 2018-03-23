<?php

namespace Drupal\responsive_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;

/**
 * Form builder for the responsive_menu admin settings page.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'responsive_menu_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['responsive_menu.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['responsive_menu'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Responsive menu'),
    ];
    $form['responsive_menu']['horizontal_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose which Drupal menu will be rendered as a horizontal menu at the breakpoint width'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('horizontal_menu'),
      '#options' => $this->getMenuOptions(),
    ];
    $form['responsive_menu']['horizontal_depth'] = [
      '#type' => 'select',
      '#title' => $this->t('A maximum menu depth that the horizontal menu should display'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('horizontal_depth'),
      '#options' => array_combine(
        [1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 5, 6, 7, 8, 9]
      ),
      '#description' => $this->t('The mobile menu will always allow all depths to be navigated to. This only controls what menu depth you want to display on the horizontal menu. It can be useful if you want a single row of items because you are handling secondary level and lower in a separate block.'),
    ];
    $form['responsive_menu']['off_canvas'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Off canvas'),
    ];
    $form['responsive_menu']['off_canvas']['off_canvas_menus'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter the name(s) of Drupal menus to be rendered in an off-canvas menu'),
      '#description' => $this->t('Enter the names of menus in a comma delimited format. If more than one menu is entered the menu items will be merged together. This is useful if you have a main menu and a utility menu that display separately at wider screen sizes but should be merged into a single menu at smaller screen sizes. Note that the menus will be merged in the entered order.'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('off_canvas_menus'),
    ];
    $form['responsive_menu']['horizontal_wrapping_element'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose the HTML element to wrap the menu block in'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('horizontal_wrapping_element'),
      '#options' => [
        'nav' => 'nav',
        'div' => 'div',
      ],
    ];
    $form['responsive_menu']['use_breakpoint'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use a breakpoint'),
      '#description' => $this->t("Unchecking this will disable the breakpoint and your mobile menu icon block will always display (assuming you have placed it on the page). This can be useful if you always want to display the mobile menu icon and don't want a horizontal menu at all, or if you want to control the visibility and breakpoints in your theme's css. Note that the horizontal menu block, if placed, will only be visible if this is checked."),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('use_breakpoint'),
    ];
    // Breakpoints.
    $queries = responsive_menu_get_breakpoints();
    $form['responsive_menu']['horizontal_breakpoint'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose a breakpoint to trigger the desktop format menu at'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('horizontal_breakpoint'),
      '#options' => $queries,
      '#states' => [
        'visible' => [
          ':input[name="use_breakpoint"]' => ['checked' => TRUE],
        ],
      ],
    ];
    if (empty($queries)) {
      $form['responsive_menu']['horizontal_breakpoint']['#disabled'] = TRUE;
      $form['responsive_menu']['horizontal_breakpoint']['#description'] = '<div class="description">' . $this->t('You must configure at least one @breakpoint to see any options. Until then the select widget above is disabled.', [
        '@breakpoint' => \Drupal::l('breakpoint', Url::fromUri('https://www.drupal.org/documentation/modules/breakpoint')),
      ]) . '</div>';

    }
    // Whether to load the base css.
    $form['responsive_menu']['css'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Load the responsive_menu module's css"),
      '#description' => $this->t('It might be that you want to override all of the css that comes with the responsive_menu module in which case you can disable the loading of the css here and include it instead in your theme.'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('include_css'),
    ];
    // Whether to load the keyboard extension.
    $form['responsive_menu']['extension_keyboard'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Enable the keyboard navigation accessibility extension"),
      '#description' => $this->t('By enabling the keyboard navigation extension for the mmenu library you will allow navigation by keyboard. Turning this off will affect the accessibility of your site.'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('extension_keyboard'),
    ];
    // Whether to allow on admin pages.
    $form['responsive_menu']['allow_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Allow on the admin theme"),
      '#description' => $this->t("By default the mmenu library is not added to admin pages using the admin theme (if different). By checking this option the code which adds the javascript and the wrapping elements to the page will be added to every page including backend admin pages using the admin theme."),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('allow_admin'),
    ];
    // Whether to add a theme wrapper for the admin theme.
    $form['responsive_menu']['wrapper_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Add a page wrapper div to the admin theme"),
      '#description' => $this->t("Many admin themes do not have a wrapping div around all their regions (Seven theme for example) and mmenu requires this div to render properly. Checking this option will add the wrapping div using a preprocess hook."),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('wrapper_admin'),
      '#states' => [
        // Only show this field when the 'allow_admin' checkbox is enabled.
        'visible' => [
          ':input[name="allow_admin"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['responsive_menu']['theme_compatibility'] = [
      '#type' => 'fieldset',
      '#title' => 'Theme compatiblity',
    ];
    // Whether to add a theme wrapper for the front end theme.
    $form['responsive_menu']['theme_compatibility']['wrapper_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Add a page wrapper div to the front end theme"),
      '#description' => $this->t("Some themes don't have a wrapping div around all their regions (Bootstrap theme for example) and mmenu requires this div to render properly. Checking this option will add the wrapping div using a preprocess hook. Alternatively you can do this manually in your theme."),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('wrapper_theme'),
    ];

    // Left or right positioned panel.
    $form['responsive_menu']['position'] = [
      '#type' => 'select',
      '#options' => [
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#title' => $this->t('Which side the mobile menu panel should slide out from'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('off_canvas_position'),
    ];
    // The theme of the slideout panel.
    $form['responsive_menu']['theme'] = [
      '#type' => 'select',
      '#options' => [
        'theme-light' => $this->t('Light'),
        'theme-dark' => $this->t('Dark'),
        'theme-black' => $this->t('Black'),
        'theme-white' => $this->t('White'),
      ],
      '#title' => $this->t('Which mmenu theme to use'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('off_canvas_theme'),
    ];
    // Whether to dim to the page when the menu slides out.
    $form['responsive_menu']['pagedim'] = [
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('No page dim'),
        'pagedim' => $this->t('Dim to default page colour'),
        'pagedim-white' => $this->t('Dim to white'),
        'pagedim-black' => $this->t('Dim to black'),
      ],
      '#title' => $this->t('The colour to dim the page to when the menu slides out'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('pagedim'),
    ];
    // A javascript enhancements fieldset.
    $form['responsive_menu']['js'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Javascript enhancements'),
    ];
    $form['responsive_menu']['js']['superfish'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Apply Superfish to the horizontal menu'),
      '#description' => $this->t('Adds the @superfish library functionality to the horizontal menu. This enhances the menu with better support for hovering and support for mobiles.', [
        '@superfish' => \Drupal::l('Superfish', Url::fromUri('https://github.com/joeldbirch/superfish')),
      ]),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('horizontal_superfish'),
    ];
    $form['responsive_menu']['js']['superfish_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Superfish options'),
      '#states' => [
        'visible' => [
          ':input[name="superfish"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['responsive_menu']['js']['superfish_options']['superfish_delay'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Delay'),
      '#description' => $this->t('The amount of time in milliseconds a menu will remain after the mouse leaves it.'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('horizontal_superfish_delay'),
    ];
    $form['responsive_menu']['js']['superfish_options']['superfish_speed'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Speed'),
      '#description' => $this->t('The amount of time in milliseconds it takes for a menu to reach 100% opacity when it opens.'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('horizontal_superfish_speed'),
    ];
    $form['responsive_menu']['js']['superfish_options']['superfish_speed_out'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Speed out'),
      '#description' => $this->t('The amount of time in milliseconds it takes for a menu to reach 0% opacity when it closes.'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('horizontal_superfish_speed_out'),
    ];
    $form['responsive_menu']['js']['superfish_options']['superfish_hoverintent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use hoverintent'),
      '#description' => $this->t("Whether to use the @hoverintent plugin with superfish. This library is included in the superfish download and doesn't require separate installation.", [
        '@hoverintent' => \Drupal::l('hoverIntent', Url::fromUri('http://cherne.net/brian/resources/jquery.hoverIntent.html')),
      ]),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('horizontal_superfish_hoverintent'),
    ];
    // Whether the optional superfish library is to be used.
    if (!file_exists(DRUPAL_ROOT . '/libraries/superfish/dist/js/superfish.min.js')) {
      $form['responsive_menu']['js']['superfish']['#disabled'] = TRUE;
      $form['responsive_menu']['js']['superfish']['#description'] .= '<br/><span class="warning">' . $this->t('You need to download the @superfish library and place it in a /libraries directory. Until then the superfish option is disabled.', [
        '@superfish' => \Drupal::l('superfish', Url::fromUri('https://github.com/joeldbirch/superfish/archive/master.zip')),
      ]) . '</span>';
    }
    // The hammer js library is optional.
    $form['responsive_menu']['js']['hammerjs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add swipe gestures'),
      '#description' => $this->t('Adds the hammer.js library to enhance the mobile experience with swipe gestures to open or close the menu.'),
      '#default_value' => \Drupal::config('responsive_menu.settings')->get('hammerjs'),
    ];
    // If the libraries module isn't installed or if the hammer.min.js
    // file isn't in the correct location then disable the hammer option
    // and display an appropriate message.
    if (!file_exists(DRUPAL_ROOT . '/libraries/hammerjs/hammer.min.js')) {
      $form['responsive_menu']['js']['hammerjs']['#disabled'] = TRUE;
      $form['responsive_menu']['js']['hammerjs']['#description'] .= '<br/><span class="warning">' . $this->t('You must download the @hammer file and place it in a hammerjs directory in /libraries. Until then the hammerjs option is disabled.', [
        '@hammer' => \Drupal::l('hammer.min.js', Url::fromUri('http://hammerjs.github.io/dist/hammer.min.js')),
      ]) . '</span>';
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure there are breakpoints configured.
    $values = $form_state->getValues();
    if ($values['use_breakpoint'] && empty($values['horizontal_breakpoint'])) {
      $breakpoint_message = Link::fromTextAndUrl('breakpoint file', Url::fromUri('https://www.drupal.org/node/1803874'))->toRenderable();
      $form_state->setErrorByName('horizontal_breakpoint', $this->t("You have chosen to use a breakpoint but you have not selected one. This may happen if your @breakpoint is not properly set up.", [
        '@breakpoint' => render($breakpoint_message),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    // Save all the submitted form values into config.
    \Drupal::configFactory()
      ->getEditable('responsive_menu.settings')
      ->set('horizontal_menu', $values['horizontal_menu'])
      ->set('horizontal_depth', $values['horizontal_depth'])
      ->set('horizontal_wrapping_element', $values['horizontal_wrapping_element'])
      ->set('use_breakpoint', $values['use_breakpoint'])
      ->set('include_css', $values['css'])
      ->set('extension_keyboard', $values['extension_keyboard'])
      ->set('allow_admin', $values['allow_admin'])
      ->set('wrapper_admin', $values['wrapper_admin'])
      ->set('wrapper_theme', $values['wrapper_theme'])
      ->set('pagedim', $values['pagedim'])
      ->set('off_canvas_menus', $values['off_canvas_menus'])
      ->set('off_canvas_position', $values['position'])
      ->set('off_canvas_theme', $values['theme'])
      ->set('horizontal_superfish', $values['superfish'])
      ->set('horizontal_superfish_delay', $values['superfish_delay'])
      ->set('horizontal_superfish_speed', $values['superfish_speed'])
      ->set('horizontal_superfish_speed_out', $values['superfish_speed_out'])
      ->set('horizontal_superfish_hoverintent', $values['superfish_hoverintent'])
      ->set('hammerjs', $values['hammerjs'])
      ->save();

    // Handle the breakpoint.
    $queries = responsive_menu_get_breakpoints();
    // Check if the breakpoint exists and the user has chosen
    // to use a breakpoint.
    if ($values['use_breakpoint'] && isset($queries[$values['horizontal_breakpoint']])) {
      // Store the breakpoint for using again in the form.
      \Drupal::configFactory()
        ->getEditable('responsive_menu.settings')
        ->set('horizontal_breakpoint', $values['horizontal_breakpoint'])
        // Also store the actual breakpoint string for use in calling
        // the stylesheet.
        ->set('horizontal_media_query', $queries[$values['horizontal_breakpoint']])
        ->save();

      // Generate the breakpoint css file and remove existing one.
      $path = _get_breakpoint_css_filepath();
      // Ensure the directory exists, if not create it.
      if (file_exists($path . RESPONSIVE_MENU_BREAKPOINT_FILENAME)) {
        unlink($path . RESPONSIVE_MENU_BREAKPOINT_FILENAME);
      }
      $breakpoint = \Drupal::config('responsive_menu.settings')->get('horizontal_media_query');
      responsive_menu_generate_breakpoint_css($breakpoint);
    }

    // Invalidate the block cache for the horizontal menu so these settings get
    // applied when rebuilding the block on view.
    Cache::invalidateTags(['config:block.block.horizontalmenu']);

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets a list of menu names for use as options.
   *
   * @param array $menu_names
   *   (optional) Array of menu names to limit the options, or NULL to load all.
   *
   * @return array
   *   Keys are menu names (ids) values are the menu labels.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getMenuOptions(array $menu_names = NULL) {
    $menus = \Drupal::entityManager()->getStorage('menu')->loadMultiple($menu_names);
    $options = [];
    /** @var \Drupal\system\MenuInterface[] $menus */
    foreach ($menus as $menu) {
      $options[$menu->id()] = $menu->label();
    }
    return $options;
  }

}
