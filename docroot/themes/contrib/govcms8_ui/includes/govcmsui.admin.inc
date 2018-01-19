<?php

function govcmsui_form_system_theme_settings_alter(&$form, \Drupal\Core\Form\FormStateInterface &$form_state, $form_id = NULL) {
    // Work-around for a core bug affecting admin themes. See issue #943212.
    if (isset($form_id)) {
        return;
    }

    // Let's tidy up the page by minifying all options
    foreach($form as &$form_item) {
        if(isset($form_item['#open'])) {
            $form_item['#open'] = false;
        }
    }

    // govCMS UI Framework Settings
    $form['govcmsui_framework_details'] = array(
        '#type' => 'details',
        '#title' => t('govCMS Theme Framework'),
        '#open' => false,
        '#description' => t('Options for the govCMS Framework e.g. Bootstrap, govau UI Toolkit, jQuery')
    );
    $form['govcmsui_framework_details']['govcmsui_use_bootstrap'] = array(
        '#type'          => 'checkbox',
        '#title'         => t('Use Bootstrap'),
        '#attributes' => array('disabled' => FALSE),
        '#default_value' => theme_get_setting('govcmsui_use_bootstrap'),
        '#description'   => t("Do you want to include bootstrap. See <a href='http://getbootstrap.com/' target='_blank'>http://getbootstrap.com/</a>"),
    );
    $form['govcmsui_framework_details']['govcmsui_use_jquery'] = array(
        '#type'          => 'checkbox',
        '#title'         => t('Use jQuery'),
        '#attributes' => array('disabled' => FALSE),
        '#default_value' => theme_get_setting('govcmsui_use_jquery'),
        '#description'   => t("Do you want to include the latest jQuery"),
    );
    $form['govcmsui_framework_details']['govcmsui_use_jquery'] = array(
        '#type'          => 'checkbox',
        '#title'         => t('Use jQuery'),
        '#attributes' => array('disabled' => FALSE),
        '#default_value' => theme_get_setting('govcmsui_use_jquery'),
        '#description'   => t("Do you want to include the latest jQuery"),
    );
    $form['govcmsui_framework_details']['govcmsui_use_fontawesome'] = array(
        '#type'          => 'checkbox',
        '#title'         => t('Use Font Awesome'),
        '#default_value' => theme_get_setting('govcmsui_use_fontawesome'),
        '#description'   => t("Do you want to include <a href='http://fontawesome.io' target='_blank'>Font Awesome</a>"),
    );

    $form['govcmsui_navbar_details'] = array(
        '#type' => 'details',
        '#title' => t('govCMS Navigation Bar'),
        '#open' => false,
        '#description' => t('Options for the navbar')
    );
    $form['govcmsui_navbar_details']['govcmsui_navbar_justified'] = array(
        '#type'          => 'checkbox',
        '#title'         => t('Make navbar justified'),
        '#default_value' => theme_get_setting('govcmsui_navbar_justified'),
        '#description'   => t("See <a href='https://getbootstrap.com/components/#nav-justified' target='_blank'>https://getbootstrap.com/components/#nav-justified/</a> to find out about nav-justified"),
    );

    $form['govcmsui_coa_details'] = array(
        '#type' => 'details',
        '#title' => t('Coat of Arms'),
        '#open' => false,
    );
    $form['govcmsui_coa_details']['govcmsui_coa'] = array(
        '#title' => t('Select which Coat of Arms you want to display on your site'),
        '#type' => 'radios',
        '#default_value' => theme_get_setting('govcmsui_coa'),
        '#options' => array(
            '' => t('None (I\'ll use my own)'),
            'coas-inline.svg' => t('Inline (white)').'<p><img height="80" src="/'.drupal_get_path('theme', 'govcmsui').'/img/coas-inline-admin.svg" /></p>',
            'coas-inline-colour.svg' => t('Inline (black)').'<p><img height="80" src="/'.drupal_get_path('theme', 'govcmsui').'/img/coas-inline-colour.svg" /></p>',
            'coas-stacked.svg' => t('Stacked (white)').'<p><img height="80" src="/'.drupal_get_path('theme', 'govcmsui').'/img/coas-stacked-admin.svg" /></p>',
            'coas-stacked-colour.svg' => t('Stacked (black)').'<p><img height="80" src="/'.drupal_get_path('theme', 'govcmsui').'/img/coas-stacked-colour.svg" /></p>',
        ),
    );

    $form['govcmsui_footer'] = array(
        '#type' => 'details',
        '#title' => t('Footer'),
        '#open' => false,
    );
    $form['govcmsui_footer']['govcmsui_coa_footer'] = array(
        '#type'          => 'checkbox',
        '#title'         => t('Show Coat of Arms in footer'),
        '#default_value' => theme_get_setting('govcmsui_coa_footer'),
    );
    $form['govcmsui_footer']['govcmsui_footer_copyright'] = array(
        '#type'          => 'textfield',
        '#title'         => t('Copyright text'),
        '#default_value' => theme_get_setting('govcmsui_footer_copyright'),
        '#description'   => t("Leave blank to hide."),
    );

}