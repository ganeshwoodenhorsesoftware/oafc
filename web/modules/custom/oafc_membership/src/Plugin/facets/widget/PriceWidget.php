<?php

namespace Drupal\oafc_membership\Plugin\facets\widget;

use Drupal\facets\FacetInterface;
use Drupal\facets\Plugin\facets\widget\LinksWidget;
use Drupal\facets\Result\ResultInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Granular widget.
 *
 * The default numeric granular widget defines all steps by a single granularity
 * setting. Instead, we need to be able to define custom, irregular steps, with
 * customisable labels for each.
 *
 * @todo Consider making a generic version and contribute to drupal.org
 *
 * @FacetsWidget(
 *   id = "oafc_membership_numericgranular",
 *   label = @Translation("Custom granular numeric list"),
 *   description = @Translation("List of numbers grouped in custom steps."),
 * )
 */
class PriceWidget extends LinksWidget {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'granularity' => 0,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetInterface $facet) {
    $configuration = $this->getConfiguration();

    $form += parent::buildConfigurationForm($form, $form_state, $facet);

    $form['granularity'] = [
      '#type' => 'number',
      '#title' => $this->t('Granularity'),
      '#default_value' => $configuration['granularity'],
      '#description' => $this->t('The numeric size of the steps to group the result facets in.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    $this->facet = $facet;

    $items = [];
    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    $results = $facet->getResults();
    $amounts = array_map(function (ResultInterface $result) {
      $raw = $result->getRawValue();
      return number_format(is_numeric($raw) ? (float) $raw : 0, 2);
    }, $results);
    $count = count($results);

    // If we have only one result, generate the label from its position
    // depending on the granularity step.
    if (count($results) === 1) {
      $result = $results[0];
      $amount = is_numeric($amounts[0]) ? (float) $amounts[0] : 0.0;
      $step = (int) $this->configuration['granularity'];
      $step = $step > 0 ? $step : 1;
      $floor = floor($amount / $step);

      if ($floor) {
        $label = '$' . ($floor * $step) . ' - $' . (($floor + 1) * $step);
      }
      else {
        $label = $this->t('Under $@step', ['@step' => $step]);
      }

      $items[] = $this->prepareItem($result, $label);
    }
    // Otherwise, generate the label using the next result.
    else {
      foreach ($results as $index => $result) {
        $amount = $amounts[$index];

        // First item.
        if ($index == 0) {
          $label = $this->t('Under $@amount', ['@amount' => $amounts[$index + 1]]);
        }
        // Last item.
        elseif ($index == $count - 1) {
          $label = $this->t('Over $@amount', ['@amount' => $amount]);
        }
        // All other items.
        else {
          $label = '$' . $amount . ' - $' . $amounts[$index + 1];
        }

        $items[] = $this->prepareItem($result, $label);
      }
    }

    $widget = $facet->getWidget();

    return [
      '#theme' => $this->getFacetItemListThemeHook($facet),
      '#items' => $items,
      '#attributes' => [
        'data-drupal-facet-id' => $facet->id(),
        'data-drupal-facet-alias' => $facet->getUrlAlias(),
      ],
      '#context' => ['list_style' => $widget['type']],
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
    ];
  }

  /**
   * Builds a facet result item.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   The result item.
   * @param string $label
   *   The label of the result.
   *
   * @return array
   *   The facet result item as a render array.
   */
  protected function prepareItem(ResultInterface $result, $label) {
    $count = $result->getCount();
    $item = [
      '#theme' => 'facets_result_item',
      '#is_active' => $result->isActive(),
      '#value' => $label,
      '#show_count' => $this->getConfiguration()['show_numbers'] && ($count !== NULL),
      '#count' => $count,
    ];

    // Convert to link.
    $item = (new Link($item, $result->getUrl()))->toRenderable();

    if ($result->isActive()) {
      $item['#attributes'] = ['class' => ['is-active']];
    }

    $item['#wrapper_attributes'] = ['class' => ['facet-item']];
    $item['#attributes']['data-drupal-facet-item-id'] = $this->facet->getUrlAlias() . '-' . str_replace(' ', '-', $result->getRawValue());
    $item['#attributes']['data-drupal-facet-item-value'] = $result->getRawValue();

    return $item;
  }

}
