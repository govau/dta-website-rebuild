<?php

namespace Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator;

/**
 * Class EntityUrlGenerator
 * @package Drupal\simple_sitemap\Plugin\simple_sitemap\UrlGenerator
 *
 * @UrlGenerator(
 *   id = "entity",
 *   weight = 10,
 *   instantiateForEachDataSet = true
 * )
 */
class EntityUrlGenerator extends UrlGeneratorBase {

  /**
   * @inheritdoc
   */
  public function getDataSets() {
    $data_sets = [];
    $sitemap_entity_types = $this->entityHelper->getSupportedEntityTypes();
    foreach ($this->generator->getBundleSettings() as $entity_type_name => $bundles) {
      if (isset($sitemap_entity_types[$entity_type_name])) {
        $keys = $sitemap_entity_types[$entity_type_name]->getKeys();

        // Menu fix.
        $keys['bundle'] = $entity_type_name == 'menu_link_content' ? 'menu_name' : $keys['bundle'];

        foreach ($bundles as $bundle_name => $bundle_settings) {
          if ($bundle_settings['index']) {
            $data_sets[] = [
              'bundle_settings' => $bundle_settings,
              'bundle_name' => $bundle_name,
              'entity_type_name' => $entity_type_name,
              'keys' => $keys,
            ];
          }
        }
      }
    }
    return $data_sets;
  }

  /**
   * @inheritdoc
   */
  protected function processDataSet($entity) {

    $entity_id = $entity->id();
    $entity_type_name = $entity->getEntityTypeId();

    $entity_settings = $this->generator->getEntityInstanceSettings($entity_type_name, $entity_id);

    if (empty($entity_settings['index'])) {
      return FALSE;
    }

    switch ($entity_type_name) {
      // Loading url object for menu links.
      case 'menu_link_content':
        if (!$entity->isEnabled()) {
          return FALSE;
        }
        $url_object = $entity->getUrlObject();
        break;

      // Loading url object for other entities.
      default:
        $url_object = $entity->toUrl();
    }

    // Do not include external paths.
    if (!$url_object->isRouted()) {
      return FALSE;
    }

    $path = $url_object->getInternalPath();

    // Do not include paths that have been already indexed.
    if ($this->batchSettings['remove_duplicates'] && $this->pathProcessed($path)) {
      return FALSE;
    }

    $url_object->setOption('absolute', TRUE);

    $lastmod = method_exists($entity, 'getChangedTime') ? date_iso8601($entity->getChangedTime()) : NULL;

    // Menu fix.
    $lastmod = $entity_type_name !== 'menu_link_content' ? $lastmod : NULL;

    return [
      'url' => $url_object,
      'lastmod' => $lastmod,
      'priority' => isset($entity_settings['priority']) ? $entity_settings['priority'] : NULL,
      'changefreq' => !empty($entity_settings['changefreq']) ? $entity_settings['changefreq'] : NULL,
      'images' => !empty($entity_settings['include_images'])
        ? $this->getImages($entity_type_name, $entity_id)
        : [],

      // Additional info useful in hooks.
      'meta' => [
        'path' => $path,
        'entity_info' => [
          'entity_type' => $entity_type_name,
          'id' => $entity_id,
        ],
      ]
    ];
  }

  /**
   * @inheritdoc
   */
  protected function getBatchIterationElements(array $entity_info) {
    $query = $this->entityTypeManager->getStorage($entity_info['entity_type_name'])->getQuery();

    if (!empty($entity_info['keys']['id'])) {
      $query->sort($entity_info['keys']['id'], 'ASC');
    }
    if (!empty($entity_info['keys']['bundle'])) {
      $query->condition($entity_info['keys']['bundle'], $entity_info['bundle_name']);
    }
    if (!empty($entity_info['keys']['status'])) {
      $query->condition($entity_info['keys']['status'], 1);
    }

    if ($this->needsInitialization()) {
      $count_query = clone $query;
      $this->initializeBatch($count_query->count()->execute());
    }

    if ($this->isBatch()) {
      $query->range($this->context['sandbox']['progress'], $this->batchSettings['batch_process_limit']);
    }

    return $this->entityTypeManager
      ->getStorage($entity_info['entity_type_name'])
      ->loadMultiple($query->execute());
  }
}
