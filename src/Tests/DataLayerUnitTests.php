<?php
namespace Drupal\datalayer\Tests;

use Drupal\simpletest\KernelTestBase;
use Drupal\Tests\UnitTestCase;
use Drupal\node\Entity\Node;
use Drupal\Core\Language\LanguageInterface;

/**
 * @file
 * Tests the functionality of the DataLayer module.
 */

class DataLayerUnitTests extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['datalayer', 'system', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'DataLayer Unit Tests',
      'description' => 'Tests to ensure data makes it client-side.',
      'group' => 'DataLayer',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(['system']);
  }

  /**
   * Test DataLayer Defaults function.
   */
  public function testDataLayerDefaults() {
    // $this->setupMockLanguage();
    $this->assertEqual(
      array('drupalLanguage' => $this->defaultLanguageData()['id'], 'drupalCountry' => $this->config('system.date')->get('country.default')),
      _datalayer_defaults()
    );
  }

  /**
   * Test DataLayer Add Will Add Data.
   */
  public function testDataLayerAddWillAddData() {
    $this->setupEmptyDataLayer();
    $this->assertEqual(
      array('foo' => 'bar'),
      datalayer_add(array('foo' => 'bar'))
    );
  }

  /**
   * Test DataLayer Add Does Not Overwrite By Default.
   */
  public function testDataLayerAddDoesNotOverwriteByDefault() {
    $this->setupEmptyDataLayer();
    datalayer_add(array('foo' => 'bar'));
    $this->assertEqual(
      array('foo' => 'bar'),
      datalayer_add(array('foo' => 'baz'))
    );
  }

  /**
   * Test DataLayer Add Will Overwrite With Flag.
   */
  public function testDataLayerAddWillOverwriteWithFlag() {
    $this->setupEmptyDataLayer();
    datalayer_add(array('foo' => 'bar'));
    $this->assertEqual(
      array('foo' => 'baz'),
      datalayer_add(array('foo' => 'baz'), TRUE)
    );
  }

  /**
   * Test DataLayer Menu Get Any Object.
   *
   * Returns False Without Load Functions.
   */
  public function testDataLayerMenuGetAnyObjectReturnsFalseWithoutLoadFunctions() {
    $item = $this->setupMockNode();
    $item['node/1']['load_functions'] = NULL;
    $item_static = &drupal_static('menu_get_item');
    $item_static = $item;
    $return_type = FALSE;
    $result = _datalayer_menu_get_any_object($return_type);
    $this->assertEqual($return_type, FALSE);
    $this->assertEqual($result, FALSE);
  }

  /**
   * Test DataLayer Menu Get Any Object.
   *
   * Returns False Without Load Function Match.
   */
  public function testDataLayerMenuGetAnyObjectReturnsFalseWithoutLoadFunctionMatch() {
    $item = $this->setupMockNode();
    $item['node/1']['load_functions'] = array(1 => 'user_load');
    $item_static = &drupal_static('menu_get_item');
    $item_static = $item;
    $return_type = FALSE;
    $result = _datalayer_menu_get_any_object($return_type);
    $this->assertEqual($return_type, FALSE);
    $this->assertEqual($result, FALSE);
  }

  /**
   * Test DataLayer Menu Get Any Object.
   *
   * Returns False With Incorrect Arg Position.
   */
  public function testDataLayerMenuGetAnyObjectReturnsFalseWithIncorrectArgPosition() {
    $item = $this->setupMockNode();
    $item['node/1']['load_functions'] = array('user_load');
    $item_static = &drupal_static('menu_get_item');
    $item_static = $item;
    $return_type = FALSE;
    $result = _datalayer_menu_get_any_object($return_type);
    $this->assertEqual($return_type, FALSE);
    $this->assertEqual($result, FALSE);
  }

  /**
   * Test DataLayer Menu Get Any Object Returns Object.
   */
  public function testDataLayerMenuGetAnyObjectReturnsObject() {
    $item = $this->setupMockNode();
    $return_type = FALSE;
    $object = _datalayer_menu_get_any_object($return_type);
    $this->assertEqual($return_type, 'node');
    $this->assertEqual($object, $item['node/1']['map'][1]);
  }

  /**
   * Test DataLayer Get Entity Terms Returns Empty Array.
   */
  public function testDataLayerGetEntityTermsReturnsEmptyArray() {
    $item = $this->setupMockNode();
    $this->setupMockFieldMap();
    $terms = _datalayer_get_entity_terms('node', 'page', $item['node/1']['map'][1]);
    $this->assertEqual(array(), $terms);
  }

  /**
   * Test DataLayer Get Entity Terms Returns Term Array.
   */
  public function testDataLayerGetEntityTermsReturnsTermArray() {
    $item = $this->setupMockNode();
    $this->setupMockEntityTerms();
    $terms = _datalayer_get_entity_terms('node', 'article', $item['node/1']['map'][1]);
    $this->assertEqual(array('tags' => array(1 => 'someTag')), $terms);
  }

  /**
   * Test DataLayer Get Entity Terms Returns Entity Data Array.
   */
  public function testDataLayerGetEntityDataReturnsEntityDataArray() {
    $this->setupEmptyDataLayer();
    $item = $this->setupMockNode();
    $this->setupMockEntityTerms();
    $entity_data = _datalayer_get_entity_data($item['node/1']['map'][1]);
    $this->assertEqual(
      $this->getExpectedEntityDataArray(),
      $entity_data
    );
  }

  /**
   * Setup user.
   */
  public function setupMockUser() {
    $user = \Drupal::currentUser();
    $user->uid = 1;
  }

  /**
   * Setup language.
   */
  public function setupMockLanguage($lang = 'en') {
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $language->getId();
  }

  /**
   * Setup empty datalayer.
   */
  public function setupEmptyDataLayer() {
    $data = &drupal_static('datalayer_add', array());
  }

  /**
   * Setup mock node.
   */
  public function setupMockNode() {
    // Hijack static cache for menu_get_item call.
    $item = &drupal_static('menu_get_item');
    $_GET['q'] = 'node/1';
    $item = array(
      'node/1' => array(
        'load_functions' => array(1 => 'node_load'),
        'map' => array(
          'node',
        ),
      ),
    );

    // Create a node.
    $edit = array(
      'uid'      => 1,
      'name'     => 'admin',
      'type'     => 'page',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'title'    => 'testing_transaction_exception',
    );

    $item['node/1']['map'][] = Node::create($edit);

    // Hijack static cache for entity_get_info call.
    $entity = &drupal_static('entity_get_info');
    $entity = array('node' => array('load hook' => 'node_load'));
    return $item;
  }

  /**
   * Setup Mock Field Map.
   */
  public function setupMockFieldMap() {
    $field_map = &drupal_static('_field_info_field_cache');
    $field_map = new DataLayerMockFieldInfo();
  }

  /**
   * Setup Mock Field Language.
   */
  public function setupMockFieldLanguage() {
    $field_language = &drupal_static('field_language');
    $field_language = array(
      'node' => array(
        1 => array(
          'en' => array(
            'field_tags' => 'und',
          ),
        ),
      ),
    );
  }

  /**
   * Setup Mock Entity Info.
   */
  public function setupMockEntityInfo() {
    $entity_info = &drupal_static('entity_get_info');
    $entity_info = array(
      'node' => array(
        'entity keys' => array(
          'id' => 'nid',
          'revision' => 'vid',
          'bundle' => 'type',
          'label' => 'title',
          'language' => 'language',
        ),
      ),
      'taxonomy_term' => array(
        'controller class' => 'TaxonomyTermController',
        'base table' => 'taxonomy_term_data',
        'uri callback' => 'taxonomy_term_uri',
        'entity keys' => array(
          'id' => 'tid',
          'bundle' => 'vocabulary_machine_name',
          'label' => 'name',
          'revision' => '',
        ),
        'bundles' => array(
          'tags' => array(
            'label' => 'Tags',
            'admin' => array(
              'path' => 'admin/structure/taxonomy/%taxonomy_vocabulary_machine_name',
              'real path' => 'admin/structure/taxonomy/tags',
              'bundle argument' => 3,
              'access arguments' => array(0 => 'administer taxonomy'),
            ),
          ),
        ),
      ),
    );
  }

  /**
   * Setup Mock Entity Controller.
   */
  public function setupMockEntityController() {
    $entity_contoller = &drupal_static('entity_get_controller');
    $entity_contoller = array(
      'taxonomy_term' => new DataLayerMockEntityController(),
    );
  }

  /**
   * Setup Mock Entity Terms.
   */
  public function setupMockEntityTerms() {
    $this->setupMockFieldMap();
    $this->setupMockLanguage('en');
    $this->setupMockFieldLanguage();
    $this->setupMockEntityInfo();
    $this->setupMockEntityController();
  }

  /**
   * Get expected entity data array.
   */
  public function getExpectedEntityDataArray() {
    return array(
      'entityType' => 'node',
      'entityBundle' => 'article',
      'entityId' => 1,
      'entityLabel' => 'My Article',
      'entityLangcode' => 'und',
      'entityTnid' => 0,
      'entityVid' => 1,
      'entityName' => 'admin',
      'entityUid' => 1,
      'entityCreated' => '1435019805',
      'entityStatus' => 1,
      'entityTaxonomy' => array(
        'tags' => array(
          1 => 'someTag',
        ),
      ),
    );
  }

}
