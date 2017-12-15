<?php

namespace Drupal\search_api_autocomplete\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_autocomplete\SearchApiAutocompleteException;
use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Suggester\SuggesterInterface;

/**
 * Describes the autocomplete settings for a certain search.
 *
 * @ConfigEntityType(
 *   id = "search_api_autocomplete_search",
 *   label = @Translation("Autocomplete search"),
 *   label_collection = @Translation("Autocomplete searches"),
 *   label_singular = @Translation("autocomplete search"),
 *   label_plural = @Translation("autocomplete searches"),
 *   label_count = @PluralTranslation(
 *     singular = "@count autocomplete search",
 *     plural = "@count autocomplete searches",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\search_api_autocomplete\Entity\SearchStorage",
 *     "form" = {
 *       "default" = "\Drupal\search_api_autocomplete\Form\SearchEditForm",
 *       "edit" = "\Drupal\search_api_autocomplete\Form\SearchEditForm",
 *       "delete" = "\Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "\Drupal\Core\Entity\EntityListBuilder",
 *     "route_provider" = {
 *       "default" = "\Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer search_api_autocomplete",
 *   config_prefix = "search",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/search/search-api/index/{search_api_index}/autocomplete/{search_api_autocomplete_search}/edit",
 *     "delete-form" = "/admin/config/search/search-api/index/{search_api_index}/autocomplete/{search_api_autocomplete_search}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "status",
 *     "index_id",
 *     "suggester_settings",
 *     "suggester_weights",
 *     "suggester_limits",
 *     "search_settings",
 *     "options",
 *   }
 * )
 */
class Search extends ConfigEntityBase implements SearchInterface {

  /**
   * The entity ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The entity label.
   *
   * @var string
   */
  protected $label;

  /**
   * The index ID.
   *
   * @var string
   */
  protected $index_id;

  /**
   * The search index instance.
   *
   * @var \Drupal\search_api\IndexInterface|null
   *
   * @see \Drupal\search_api_autocomplete\Entity\Search::getIndex()
   */
  protected $index;

  /**
   * The settings of the suggesters selected for this search.
   *
   * The array has the following structure:
   *
   * @code
   * [
   *   'SUGGESTER_ID' => [
   *     // Settings …
   *   ],
   *   …
   * ]
   * @endcode
   *
   * @var array
   */
  protected $suggester_settings = [];

  /**
   * The suggester weights, keyed by suggester ID.
   *
   * @var int[]
   */
  protected $suggester_weights = [];

  /**
   * The suggester limits (where set), keyed by suggester ID.
   *
   * @var int[]
   */
  protected $suggester_limits = [];

  /**
   * The loaded suggester plugins.
   *
   * @var \Drupal\search_api_autocomplete\Suggester\SuggesterInterface[]|null
   */
  protected $suggesterInstances;

  /**
   * The settings for the search plugin.
   *
   * The array has the following structure:
   *
   * @code
   * [
   *   'SEARCH_ID' => [
   *     // Settings …
   *   ]
   * ]
   * @endcode
   *
   * There is always just a single entry in the array.
   *
   * @var array
   */
  protected $search_settings = [];

  /**
   * The search plugin.
   *
   * @var \Drupal\search_api_autocomplete\Search\SearchPluginInterface|null
   */
  protected $searchPlugin;

  /**
   * An array of general options for this search.
   *
   * @var array
   */
  protected $options = [];

  /**
   * {@inheritdoc}
   */
  public static function getDefaultOptions() {
    return [
      'autosubmit' => TRUE,
      'delay' => 0,
      'limit' => 10,
      'min_length' => 1,
      'submit_button_selector' => ':submit',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $parameters = parent::urlRouteParameters($rel);

    $parameters['search_api_index'] = $this->getIndexId();

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getIndexId() {
    return $this->index_id;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidIndex() {
    return $this->index || Index::load($this->index_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getIndex() {
    if (!isset($this->index)) {
      $this->index = Index::load($this->index_id);
      if (!$this->index) {
        throw new SearchApiAutocompleteException("The index with ID \"{$this->index_id}\" could not be loaded.");
      }
    }
    return $this->index;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggesters() {
    if ($this->suggesterInstances === NULL) {
      $this->suggesterInstances = \Drupal::getContainer()
        ->get('search_api_autocomplete.plugin_helper')
        ->createSuggesterPlugins($this, array_keys($this->suggester_settings));
    }

    return $this->suggesterInstances;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggesterIds() {
    if ($this->suggesterInstances !== NULL) {
      return array_keys($this->suggesterInstances);
    }
    return array_keys($this->suggester_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function isValidSuggester($suggester_id) {
    $suggesters = $this->getSuggesters();
    return !empty($suggesters[$suggester_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggester($suggester_id) {
    $suggesters = $this->getSuggesters();

    if (empty($suggesters[$suggester_id])) {
      $index_label = $this->label();
      throw new SearchApiAutocompleteException("The suggester with ID '$suggester_id' could not be retrieved for index '$index_label'.");
    }

    return $suggesters[$suggester_id];
  }

  /**
   * {@inheritdoc}
   */
  public function addSuggester(SuggesterInterface $suggester) {
    // Make sure the suggesterInstances are loaded before trying to add a plugin
    // to them.
    if ($this->suggesterInstances === NULL) {
      $this->getSuggesters();
    }
    $this->suggesterInstances[$suggester->getPluginId()] = $suggester;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeSuggester($suggester_id) {
    // Depending on whether the suggesters have already been loaded, we have to
    // either remove the settings or the instance.
    if ($this->suggesterInstances === NULL) {
      unset($this->suggester_settings[$suggester_id]);
    }
    else {
      unset($this->suggesterInstances[$suggester_id]);
    }
    unset($this->suggester_weights[$suggester_id]);
    unset($this->suggester_limits[$suggester_id]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuggesters(array $suggesters = NULL) {
    $this->suggesterInstances = $suggesters;

    // Sanitize the suggester weights and limits.
    $this->suggester_weights = array_intersect_key($this->suggester_weights, $suggesters);
    $this->suggester_limits = array_intersect_key($this->suggester_limits, $suggesters);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggesterWeights() {
    return $this->suggester_weights;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggesterLimits() {
    return $this->suggester_limits;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidSearchPlugin() {
    return (bool) \Drupal::getContainer()
      ->get('plugin.manager.search_api_autocomplete.search')
      ->getDefinition($this->getSearchPluginId(), FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchPluginId() {
    if ($this->searchPlugin) {
      return $this->searchPlugin->getPluginId();
    }
    reset($this->search_settings);
    return key($this->search_settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getSearchPlugin() {
    if (!$this->searchPlugin) {
      $plugin_id = $this->getSearchPluginId();

      $configuration = [];
      if (!empty($this->search_settings[$plugin_id])) {
        $configuration = $this->search_settings[$plugin_id];
      }

      $this->searchPlugin = \Drupal::getContainer()
        ->get('search_api_autocomplete.plugin_helper')
        ->createSearchPlugin($this, $plugin_id, $configuration);
    }

    return $this->searchPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($name) {
    $options = $this->getOptions();
    return isset($options[$name]) ? $options[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options + static::getDefaultOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($name, $option) {
    $this->options[$name] = $option;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->options = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function createQuery($keys, array $data = []) {
    return $this->getSearchPlugin()->createQuery($keys, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Make sure the search plugin's index matches this entity's index.
    $plugin_index_id = $this->getSearchPlugin()->getIndexId();
    if ($this->getIndexId() !== $plugin_index_id) {
      throw new SearchApiAutocompleteException("Attempt to save autocomplete search '{$this->id()}' with search plugin '{$this->getSearchPluginId()}' of index '$plugin_index_id' while the autocomplete search points to index '{$this->getIndexId()}'");
    }

    // Make sure only one search entity is ever saved for a certain search
    // plugin.
    /** @var \Drupal\search_api_autocomplete\Entity\SearchStorage $storage */
    $search = $storage->loadBySearchPlugin($this->getSearchPluginId());
    if ($search && $search->id() !== $this->id()) {
      throw new SearchApiAutocompleteException("Attempt to save autocomplete search '{$this->id()}' with search plugin '{$this->getSearchPluginId()}' when this plugin is already used for '{$search->id()}'");
    }

    // If we are in the process of syncing, we shouldn't change any entity
    // properties (or other configuration).
    if ($this->isSyncing()) {
      return;
    }

    // Write the plugin settings to the persistent *_settings properties.
    $this->writeChangesToSettings();

    // If there are no suggesters set for the search, it can't be enabled.
    if (!$this->getSuggesters()) {
      $this->disable();
    }
  }

  /**
   * Prepares for changes to this search to be persisted.
   *
   * To this end, the settings for all loaded plugin objects are written back to
   * the corresponding *_settings properties.
   *
   * @return $this
   */
  protected function writeChangesToSettings() {
    // Write the enabled suggesters to the settings property.
    $this->suggester_settings = [];
    foreach ($this->getSuggesters() as $suggester_id => $suggester) {
      if ($suggester->supportsSearch($this)) {
        $configuration = $suggester->getConfiguration();
        $this->suggester_settings[$suggester_id] = $configuration;
      }
      else {
        unset($this->suggesterInstances[$suggester_id]);
      }
    }

    // Write the search plugin configuration to the settings property.
    if ($this->searchPlugin !== NULL) {
      $plugin_id = $this->searchPlugin->getPluginId();
      $this->search_settings = [
        $plugin_id => $this->searchPlugin->getConfiguration(),
      ];
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    $name = $this->getIndex()->getConfigDependencyName();
    $this->addDependency('config', $name);

    if ($this->hasValidSearchPlugin()) {
      $this->calculatePluginDependencies($this->getSearchPlugin());
    }

    foreach ($this->getSuggesters() as $suggester) {
      $this->calculatePluginDependencies($suggester);
    }

    return $this;
  }

}
