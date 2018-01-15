<?php
/**
 * @file
 * Contains \Drupal\wysiwyg_template\Form\CrudTest.
 */

namespace Drupal\wysiwyg_template\Tests\Form;

use Drupal\Core\Url;
use Drupal\node\Entity\NodeType;
use Drupal\simpletest\WebTestBase;
use Drupal\wysiwyg_template\Entity\Template;

/**
 * Tests the template CRUD forms.
 *
 * @group wysiwyg_template
 */
class CrudTest extends WebTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['wysiwyg_template', 'filter_test', 'node'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->admin = $this->drupalCreateUser(['administer wysiwyg templates', 'administer filters']);
    $this->drupalLogin($this->admin);
  }

  /**
   * Tests the CRUD UI.
   */
  public function testCrudUi() {

    // Add.
    $this->drupalGet(Url::fromRoute('entity.wysiwyg_template.collection'));
    $this->assertText(t('There are no WYSIWYG templates yet.'));
    $this->drupalGet(Url::fromRoute('entity.wysiwyg_template.add_form'));
    // Node type selection should be hidden if there are less than 2 types.
    $this->assertNoText(t('Available for content types'));
    $id = strtolower($this->randomMachineName());
    $edit = [
      'id' => $id,
      'label' => $this->randomString(),
      'description' => $this->randomString(),
      'body[value]' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertUrl(Url::fromRoute('entity.wysiwyg_template.collection'));
    $this->assertEscaped($id, 'The machine name appears on the listing page.');
    $this->assertEscaped($edit['label'], 'The label appears on the listing page.');

    /** @var \Drupal\wysiwyg_template_core\TemplateInterface $template */
    $template = Template::load($id);
    $this->assertEqual('filter_test', $template->getFormat());

    // Edit.
    $this->drupalGet($template->toUrl('edit-form'));
    $edit['label'] = $this->randomString(12);
    unset($edit['id']);
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertUrl(Url::fromRoute('entity.wysiwyg_template.collection'));
    $this->assertEscaped($id, 'The machine name appears on the listing page.');
    $this->assertEscaped($edit['label'], 'The label appears on the listing page.');

    // Delete.
    $this->drupalGet($template->toUrl('delete-form'));
    $this->drupalPostForm(NULL, [], t('Delete'));
    $this->assertText(t('There are no WYSIWYG templates yet.'));

    // Add a few node types.
    $type1 = NodeType::create([
      'type' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $type1->save();
    $type2 = NodeType::create([
      'type' => $this->randomMachineName(),
      'name' => $this->randomString(),
    ]);
    $type2->save();
    $this->drupalGet(Url::fromRoute('entity.wysiwyg_template.add_form'));
    // Node type selection should be hidden if there are less than 2 types.
    $this->assertText(t('Available for content types'));
    $id = strtolower($this->randomMachineName());
    $edit = [
      'id' => $id,
      'label' => $this->randomString(),
      'description' => $this->randomString(),
      'body[value]' => $this->randomString(),
      'node_types[' . $type2->id() . ']' => 1,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $template = Template::load($id);
    $this->assertEqual([$type2->id()], $template->getNodeTypes());
  }

}
