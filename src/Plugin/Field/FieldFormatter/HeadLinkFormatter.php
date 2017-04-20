<?php

namespace Drupal\head_link_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'head_link' formatter.
 *
 * @FieldFormatter(
 *   id = "head_link",
 *   label = @Translation("Head link"),
 *   description = @Translation("Add the link to the HTML head"),
 *   field_types = {
 *     "link",
 *     "entity_reference"
 *   }
 * )
 */
class HeadLinkFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'rel' => '',
      'type' => '',
      'title' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $elements['rel'] = [
      '#type' => 'textfield',
      '#title' => t('Override the @rel value from RDF module.'),
      '#default_value' => $this->getSetting('rel'),
    ];
    $elements['type'] = [
      '#type' => 'textfield',
      '#title' => t('Add a MIME type for alternate links.'),
      '#default_value' => $this->getSetting('type'),
    ];
    $elements['title'] = [
      '#type' => 'textfield',
      '#title' => t('Add a title text.'),
      '#default_value' => $this->getSetting('title'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();
    if (!empty($settings['rel'])) {
      $summary[] = t('Set rel="@rel"', ['@rel' => $settings['rel']]);
    }
    if (!empty($settings['type'])) {
      $summary[] = t('Add type="@type"', ['@type' => $settings['type']]);
    }
    if (!empty($settings['title'])) {
      $summary[] = t('Add title="@title"', ['@title' => $settings['title']]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $rel = $this->getSetting('rel');
    if (empty($rel)) {
      if (\Drupal::moduleHandler()->moduleExists('rdf')) {
        $field_name = $items->getName();
        $entity = $items->getEntity();
        $entity_type = $entity->getEntityTypeId();
        $bundle = $entity->bundle();
        $mapping = rdf_get_mapping($entity_type, $bundle);
        $field_mapping = $mapping->getPreparedFieldMapping($field_name);
        if (isset($field_mapping["properties"])) {
          $rel = implode(" ", $field_mapping["properties"]);
        }
      }
    }
    if (empty($rel)) {
      $field_name = $items->getName();
      $rel = $field_name;
    }

    $elements = [];
    foreach ($items as $delta => $item) {
      $link = [
        'rel' => $rel,
      ];
      if ($item instanceof LinkItemInterface) {
        $link["href"] = $item->getUrl()->getUri();
      }
      if ($item instanceof EntityReferenceItem) {
        $link["href"] = $item->get('entity')->getTarget()->getValue()->url();
      }
      if ($item instanceof EntityReferenceItem) {
        $link["title"] = $item->get('entity')->getTarget()->getValue()->label();
      }
      if (!empty($this->getSetting('title'))) { 
        $link["title"] = $this->getSetting('title');
      }
      if (!empty($this->getSetting('type'))) { 
        $link["type"] = $this->getSetting('type');
      }
      $elements['#attached']['html_head_link'][] = [
        $link,
      ];

    }
 
   return $elements;
  }

}
