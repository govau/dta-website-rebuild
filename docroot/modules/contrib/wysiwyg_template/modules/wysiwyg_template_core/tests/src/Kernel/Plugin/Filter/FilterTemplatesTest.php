<?php
/**
 * @file
 * Contains \Drupal\Tests\wysiwyg_template_core\Kernel\Plugin\Filter\FilterTemplatesTest.
 */

namespace Drupal\Tests\wysiwyg_template_core\Kernel\Plugin\Filter;

use Drupal\filter\FilterPluginCollection;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the WYSIWYG cleanup filter.
 *
 * @group wysiwyg_template
 *
 * @coversDefaultClass \Drupal\wysiwyg_template_core\Plugin\Filter\FilterTemplates
 */
class FilterTemplatesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['filter', 'wysiwyg_template_core'];

  /**
   * The WYSIWYG filter to test.
   *
   * @var \Drupal\filter\Plugin\FilterInterface
   */
  protected $filter;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    /** @var \Drupal\filter\FilterPluginManager $manager */
    $manager = $this->container->get('plugin.manager.filter');
    $bag = new FilterPluginCollection($manager, []);
    $this->filter = $bag->getAll()['filter_wysiwyg_cleanup'];
  }

  /**
   * Tests that the WYSIWYG-specific attributes are removed.
   *
   * @param $input
   *   Input to test.
   * @param $expected
   *   Expected filtered result.
   *
   * @covers ::process
   *
   * @dataProvider providerTestFilter
   */
  public function testFilter($input, $expected) {
    $filter = $this->filter;
    $test = function($input) use ($filter) {
      return $this->filter->process($input, 'und');
    };
    $this->assertSame($expected, $test($input)->getProcessedText());
  }

  /**
   * Data provider for the testFilter method.
   *
   * @return array
   *   Array of data sets to test with.
   */
  public function providerTestFilter() {
    return [
      // Raw, expected.
      ['<img src="llama.jpg" />', '<img src="llama.jpg" />'],
      ['<img src="llama.jpg" contenteditable="true" />', '<img src="llama.jpg"  />'],
      ['<img src="llama.jpg" contenteditable="false" />', '<img src="llama.jpg"  />'],
    ];
  }

}
