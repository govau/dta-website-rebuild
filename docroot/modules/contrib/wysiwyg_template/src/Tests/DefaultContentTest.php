<?php
/**
 * @file
 * Contains \Drupal\wysiwyg_template\Tests\DefaultContentTest.
 */

namespace Drupal\wysiwyg_template\Tests;

use Drupal\node\Entity\NodeType;
use Drupal\simpletest\WebTestBase;
use Drupal\wysiwyg_template\Entity\Template;

/**
 * Test the ability to specify a template as default content for a node.
 *
 * @group wysiwyg_template
 */
class DefaultContentTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'wysiwyg_template'];

  /**
   * An array of templates.
   *
   * @var \Drupal\wysiwyg_template_core\TemplateInterface[]
   */
  protected $templates;

  /**
   * A content type.
   *
   * @var \Drupal\node\NodeTypeInterface
   */
  protected $nodeType;

  /**
   * An admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    foreach (range(1, 2) as $i) {
      $this->templates[$i] = Template::create([
        'id' => strtolower($this->randomMachineName()),
        'label' => $this->randomString(),
        'body' => [
          'value' => $this->randomString(),
        ],
      ]);
      $this->templates[$i]->save();
    }

    $this->nodeType = $this->createContentType([
      'type' => strtolower($this->randomMachineName()),
      'name' => $this->randomString(),
    ]);
    $this->nodeType->save();

    $this->admin = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($this->admin);
  }

  /**
   * Test the node type form.
   */
  public function testNodeTypeForm() {
    $this->drupalGet($this->nodeType->toUrl('edit-form'));
    $edit = [
      'wysiwyg_template_default' => $this->templates[1]->id(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save content type'));
    $type = NodeType::load($this->nodeType->id());
    $this->assertEqual($this->templates[1]->id(), $type->getThirdPartySetting('wysiwyg_template', 'default_template'));

    // Verify that the default content is set as expected.
    $this->drupalGet('node/add/' . $this->nodeType->id());
    $edit = [
      'title[0][value]' => $this->randomString(),
      // Leave body empty, and it should just be set to the template's value.
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertEqual($this->templates[1]->getBody(), $node->get('body')->value);
  }

}
