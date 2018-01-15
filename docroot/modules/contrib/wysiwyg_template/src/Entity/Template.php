<?php

/**
 * @file
 * Contains \Drupal\wysiwyg_template\Entity\Template.
 */

namespace Drupal\wysiwyg_template\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\NodeTypeInterface;
use Drupal\wysiwyg_template_core\TemplateInterface;

/**
 * Defines the Template entity.
 *
 * @ConfigEntityType(
 *   id = "wysiwyg_template",
 *   label = @Translation("Template"),
 *   handlers = {
 *     "list_builder" = "Drupal\wysiwyg_template\TemplateListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wysiwyg_template\Form\TemplateForm",
 *       "edit" = "Drupal\wysiwyg_template\Form\TemplateForm",
 *       "delete" = "Drupal\wysiwyg_template\Form\TemplateDeleteForm"
 *     }
 *   },
 *   config_prefix = "wysiwyg_template",
 *   admin_permission = "administer wysiwyg templates",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/content/wysiwyg-templates/{wysiwyg_template}",
 *     "edit-form" = "/admin/config/content/wysiwyg-templates/{wysiwyg_template}/edit",
 *     "delete-form" = "/admin/config/content/wysiwyg-templates/{wysiwyg_template}/delete",
 *     "collection" = "/admin/config/content/wysiwyg-templates"
 *   }
 * )
 */
class Template extends ConfigEntityBase implements TemplateInterface {

  /**
   * The unique template ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The template title.
   *
   * @var string
   */
  protected $title;

  /**
   * The template description.
   *
   * @var string
   */
  protected $description;

  /**
   * The template HTML body.
   *
   * @var string
   */
  protected $body;

  /**
   * The template weight.
   *
   * @var integer
   */
  protected $weight;

  /**
   * The node types this template is available for.
   *
   * @var string[]
   */
  protected $node_types;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    if ($body = $this->get('body')) {
      return $body['value'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat() {
    if ($body = $this->get('body')) {
      return $body['format'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeTypes() {
    return $this->node_types ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $this->node_types = array_values(array_filter($this->getNodeTypes()));
    parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    parent::postLoad($storage, $entities);
    // Sort the queried roles by their weight.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, 'static::sort');
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByNodeType(NodeTypeInterface $node_type = NULL) {
    /** @var \Drupal\wysiwyg_template_core\TemplateInterface[] $templates */
    $templates = static::loadMultiple();
    foreach ($templates as $id => $template) {
      if (!$node_type) {
        // If no node type is passed than all templates that *don't specify any*
        // types are included, but those specifying a type are not.
        if (!empty($template->getNodeTypes())) {
          unset($templates[$id]);
        }
      }
      else {
        // Any templates without types, plus the templates that specify this type.
        if (empty($template->getNodeTypes()) || in_array($node_type->id(), $template->getNodeTypes())) {
          continue;
        }
        unset($templates[$id]);
      }
    }

    return $templates;
  }

}
