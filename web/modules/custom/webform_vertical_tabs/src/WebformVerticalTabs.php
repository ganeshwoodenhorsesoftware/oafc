<?php

namespace Drupal\webform_vertical_tabs;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Element\WebformMultiple;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Render webform elements grouped in vertical tabs.
 *
 * Creates one tab group for each of another element's value. Used when an
 * element's name is `[webform_submission:values:key1:$delta:key2]` where `key1`
 * is the key of another element and `key2` is the sub-key to show.
 *
 * For example, with a people element that includes a name field, you could have
 * one tab per person using the token
 * `[webform_submission:values:people:$delta:name]`.
 */
class WebformVerticalTabs extends WebformMultiple {

  /**
   * Determine if vertical tabs processing should be used.
   */
  public static function processWebformMultiple(&$element, FormStateInterface $form_state, &$complete_form) {
    $form = $form_state->getFormObject();
    // When building a webform, the form object does not have a getEntity
    // method.
    if (method_exists($form, 'getEntity')) {
      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = $form->getEntity();
      $webform = $webform_submission->getWebform();
      $definition = $webform->getElement($element['#webform_key']);
      $text_tokens = \Drupal::token()->scan($definition['#title']);
      if ($text_tokens && isset($text_tokens['webform_submission']) && count($text_tokens['webform_submission']) == 1) {
        $key = key($text_tokens['webform_submission']);
        $parts = explode(':', $key);
        $data = $webform_submission->getData();
        $n = isset($data[$parts[1]]) ? count($data[$parts[1]]) : 0;
        if ($parts[0] === 'values' && $parts[2] === '$delta' && $n > 0) {
          $form_state->set('vertical_tab_base_title', $definition['#title']);
          $element['#cardinality'] = $n;
          return static::processWebformVerticalTabs($element, $form_state);
        }
      }
    }
    return parent::processWebformMultiple($element, $form_state, $complete_form);
  }

  /**
   * Alternate processing for WebformMultiple to use vertical tabs instead.
   */
  public static function processWebformVerticalTabs($element, FormStateInterface $form_state) {
    $element['#tree'] = TRUE;

    // Add validate callback that extracts the array of items.
    $element['#element_validate'] = [[get_called_class(), 'validateWebformMultiple']];

    // Wrap this $element in a <div> that handle #states.
    WebformElementHelper::fixStatesWrapper($element);
    $number_of_items = $element['#cardinality'];
    $element['#child_keys'] = Element::children($element['#element']);
    // Build (single) element rows.
    $row_index = 0;
    if (!$form_state->isProcessingInput() && isset($element['#default_value']) && is_array($element['#default_value'])) {
      $default_values = $element['#default_value'];
    }
    elseif ($form_state->isProcessingInput() && isset($element['#value']) && is_array($element['#value'])) {
      $default_values = $element['#value'];
    }
    else {
      $default_values = [];
    }
    $element['vertical_tabs'] = [
      '#type' => 'vertical_tabs',
    ];

    foreach ($default_values as $key => $default_value) {
      // If #key is defined make sure to set default value's key item.
      if (!empty($element['#key']) && !isset($default_value[$element['#key']])) {
        $default_value[$element['#key']] = $key;
      }
      $element['items'][$row_index] = static::buildTab($row_index, $element, $default_value, $form_state);
      $row_index++;
    }

    while ($row_index < $number_of_items) {
      $element['items'][$row_index] = static::buildTab($row_index, $element, NULL, $form_state);
      $row_index++;
    }
    return $element;
  }

  /**
   * Build the elements for each tab.
   *
   * @param int $row_index
   *   This tab's delta.
   * @param array $element
   *   The form element array.
   * @param mixed $default_value
   *   The element's default value.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The webform's form state object.
   *
   * @return array
   *   The render array for the tab.
   */
  public static function buildTab(int $row_index, array $element, $default_value, FormStateInterface $form_state) {
    $child_keys = $element['#child_keys'];
    $tab = [
      '#type' => 'details',
    ];
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_state->getFormObject()->getEntity();
    if ($child_keys) {
      foreach ($child_keys as $child_key) {
        if (isset($default_value[$child_key])) {
          if ($element['#element'][$child_key]['#type'] == 'value') {
            $element['#element'][$child_key]['#value'] = $default_value[$child_key];
          }
          else {
            $element['#element'][$child_key]['#default_value'] = $default_value[$child_key];
          }
        }
        // Store hidden element in the '_handle_' column.
        // @see \Drupal\webform\Element\WebformMultiple::convertValuesToItems
        if (static::isHidden($element['#element'][$child_key])) {
          $tab['_handle_'][$child_key] = $element['#element'][$child_key];
          // ISSUE: All elements in _handle_ are losing their value.
          // WORKAROUND: Convert to element to rendered hidden field.
          $tab['_handle_'][$child_key]['#type'] = 'hidden';
          unset($tab['_handle_'][$child_key]['#access']);
        }
        else {
          $keys[] = $child_key;
          $tab[$child_key] = $element['#element'][$child_key];
        }
      }
      static::addTitleAndGroup($tab, $element, $row_index, $form_state, $webform_submission);
    }
    else {
      $element['#element']['#default_value'] = $default_value;
      $tab['_item_'] = $element['#element'];
      static::addTitleAndGroup($tab['_item_'], $element, $row_index, $form_state, $webform_submission);
    }
    return $tab;
  }

  /**
   * Set a tab's title and add it to the vertical_tabs group of tabs.
   *
   * @param array $tab
   *   The render array for the current tab.
   * @param array $element
   *   A form element.
   * @param int $row_index
   *   The delta for the current tab.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The webform's form state object.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission entity.
   */
  public static function addTitleAndGroup(array &$tab, array $element, int $row_index, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $title = str_replace('$delta', $row_index, $form_state->get('vertical_tab_base_title'));
    $tab['#theme_wrappers']['details'] = [
      '#title' => \Drupal::service('webform.token_manager')->replace($title, $webform_submission),
      '#attributes' => [],
      '#required' => FALSE,
      '#errors' => [],
      '#description' => '',
      '#value' => '',
    ];
    $tab['#group'] = $element['#webform_key'] . '][vertical_tabs';
  }

}
