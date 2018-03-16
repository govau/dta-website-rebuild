<?php

/**
 * Implements hook_form_alter().
 */
function govcmsui_form_views_exposed_form_alter(&$form, \Drupal\Core\Form\FormStateInterface &$form_state, $form_id = NULL) {
    if($form_id == 'views_exposed_form' && $form['#id'] == 'views-exposed-form-search-page') {
        $variables['block_html_id'] = "search";

        //Hide label.. can't add classes directly to label so add span inside label... hackery
        $form['#info']['filter-search_api_fulltext']['label'] = '<span class="sr-only">Search</span>';
        $form['actions']['submit'] = array(
            '#type' => 'inline_template',
            '#template' => '<button type="submit" name="op" value="Search" class="btn btn-default btn-lg"><i class="fa fa-search"></i><span class="sr-only">Search</span></button>',
            '#id' => 'edit-submit-search',
            '#name' => 'search-submit-button',
        );
    }

}