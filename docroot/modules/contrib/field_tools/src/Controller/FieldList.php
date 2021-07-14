<?php

namespace Drupal\field_tools\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field list admin page.
 *
 * TODO: convert this to a list builder!
 */
class FieldList implements ContainerInjectionInterface, FormInterface {

  use StringTranslationTrait;

  /**
   * Creates an FieldList object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * Builds the page content.
   */
  function content() {
    $field_storage_config_storage = $this->entityTypeManager->getStorage('field_storage_config');
    $query = $field_storage_config_storage->getQuery();

    // TODO: apparently this should be avoided.
    $query_params = \Drupal::request()->query->all();
    if (isset($query_params['sort']) && in_array($query_params['sort'], ['type', 'field_name', 'entity_type'])) {
      $query->sort($query_params['sort']);
    }
    else {
      $query->sort('field_name');
    }

    // Filter by field type.
    if (isset($query_params['filter-type'])) {
      $query->condition('type', $query_params['filter-type']);
    }

    // Filter by host entity type and bundle.
    if (isset($query_params['host_entity_bundle'])) {
      $entity_type_filters = [];
      $bundle_filters = [];

      foreach ($query_params['host_entity_bundle'] as $host_entity_bundle_filter_value) {
        list($entity_type_filters[], $bundle_filters[]) = explode(':', $host_entity_bundle_filter_value);
      }

      // Apply the host entity type condition.
      // Field storages don't know about bundles, so that filter must be
      // applied later.
      $query->condition('entity_type', array_unique($entity_type_filters));
    }

    $entity_ids = $query->execute();
    $field_storage_configs = $field_storage_config_storage->loadMultiple($entity_ids);
    // dsm($field_storage_configs);

    // TODO: add a way to clear the query parameters.

    $build['form_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Filters'),
    ];

    $build['form_container']['form'] = \Drupal::service('form_builder')->getForm($this);

    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        Link::fromTextAndUrl(t('Field name'), $this->getSortQueryURL('field_name')),
        Link::fromTextAndUrl(t('Type'), $this->getSortQueryURL('type')),
        t('Cardinality'),
        Link::fromTextAndUrl(t('Entity type'), $this->getSortQueryURL('entity_type')),
        t('Instances'),
        t('Operations'),
      ],
    ];

    $rows = [];
    foreach ($field_storage_configs as $field_storage_config) {
      // Get all the fields for this storage; that is, the fields on all the
      // bundles of the entity type.
      $field_config_storage = $this->entityTypeManager->getStorage('field_config');
      $query = $field_config_storage->getQuery();
      $query
        ->condition('entity_type', $field_storage_config->getTargetEntityTypeId())
        ->condition('field_name', $field_storage_config->getName());

      // Apply the query filter for host bundle.
      if (isset($bundle_filters)) {
        $query->condition('bundle', $bundle_filters, 'IN');
      }

      $field_ids = $query->execute();

      // If we got no fields because of the bundle condition, skip this field
      // storage.
      if (empty($field_ids)) {
        continue;
      }

      // TODO: load the fields here and pass them to buildRow().
      // $fields = $field_config_storage->loadMultiple($field_ids);
      $row = $this->buildRow($field_storage_config);
      $rows[$field_storage_config->id()] = $row;
    }

    // Group rows?
    foreach ($rows as $id => $row) {
      $previous_field_name = NULL;

      //if ($row$previous_field_name)
    }
    // TODO!
    //dsm($rows);

    $build['table'] += $rows;

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_tools_field_list_filter';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $bundle_info = $this->entityBundleInfo->getAllBundleInfo();

    $bundle_options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Only look at content entities.
      if ($entity_type->getGroup() != 'content') {
        continue;
      }

      // WTF some broken entity types?
      if (!isset($bundle_info[$entity_type_id])) {
        continue;
      }

      foreach ($bundle_info[$entity_type_id] as $bundle_name => $entity_bundle_info) {
        $bundle_options["$entity_type_id:$bundle_name"] = $entity_type_id . ' - ' . $bundle_name;
      }
    }

    $form['host_entity_bundle'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Host entity and bundle'),
      '#options' => $bundle_options,
      // TODO: default value from query parameters.
    ];

    $form['#method'] = 'get';

    $form['actions'] = [
      '#tree' => FALSE,
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply filter'),
      '#button_type' => 'primary',
    ];
    $form['actions']['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset'),
      // TODO: get the current route automatically?
      '#url' => Url::fromRoute('field_tools.reports.list'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit: 'GET' form.
  }

  protected function buildRow($field_storage_config) {
    $entity_type_id = $field_storage_config->getTargetEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $bundle_entity_type = $entity_type->getBundleEntityType();

    $row = [];
    $row['name'] = [
      '#plain_text' => $field_storage_config->getName(),
      //'#wrapper_attributes' => ['rowspan' => count($grouped_field_storage_configs)],
    ];
    $row['type'] = [
      '#type' => 'link',
      '#title' => $field_storage_config->getType(),
      '#url' => Url::fromRoute('field_tools.reports.list', [], [
        // TODO: preserve existing query parameters!
        'query' => [
          'filter-type' => $field_storage_config->getType(),
        ],
      ]),
    ];
    $cardinality = $field_storage_config->getCardinality();
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $cardinality = $this->t('Unlimited');
    }
    $row['cardinality'] = [
      '#plain_text' => $cardinality,
    ];
    $row['entity_type'] = [
      '#plain_text' => $entity_type_id,
    ];

    // Get all the fields for this storage; that is, the fields on all the
    // bundles of the entity type.
    $field_config_storage = $this->entityTypeManager->getStorage('field_config');
    $query = $field_config_storage->getQuery();
    $field_ids = $query
      ->condition('entity_type', $entity_type_id)
      ->condition('field_name', $field_storage_config->getName())
      ->execute();
    $fields = $field_config_storage->loadMultiple($field_ids);

    // The route for editing a field, provided by Field UI.
    $route_name = "entity.field_config.{$entity_type_id}_field_edit_form";

    $items = [];
    foreach ($fields as $field) {
      $bundle = $field->getTargetBundle();

      $route_parameters = [
        'field_config' => $field->id(),
      ];
      if (!empty($bundle_entity_type)) {
        $route_parameters[$bundle_entity_type] = $bundle;
      }

      $url = Url::fromRoute($route_name, $route_parameters);

      $items[$bundle] = Link::fromTextAndUrl($bundle, $url)->toString();
    }

    natcasesort($items);

    $row['bundles'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    $row['operations']['data'] = $this->buildOperations($field_storage_config);

    return $row;
  }

  // TODO: remove when this changes to extend EntityListBuilder.
  public function buildOperations(EntityInterface $entity) {
    $build = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($entity),
    );

    return $build;
  }

  // TODO: add inherit doc when this changes to extend EntityListBuilder.
  public function getOperations(EntityInterface $entity) {
    $operations = [];
    $operations['delete'] = array(
      'title' => $this->t('Delete'),
      'weight' => 10,
      'url' => $entity->toUrl('delete-form'),
    );
    return $operations;
  }

  /**
   * Gets a URL for the current page with a sort order query parameter.
   *
   * @param $sort
   *  The name of a field on field_storage_config entities that can be used for
   *  sorting.
   *
   * @return \Drupal\Core\Url
   *  A URL object.
   */
  protected function getSortQueryURL($sort) {
    return Url::fromRoute('field_tools.reports.list', [], [
      // TODO: preserve existing query parameters!
      'query' => [
        'sort' => $sort,
      ],
    ]);
  }

}
