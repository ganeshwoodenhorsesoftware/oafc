<?php

declare(strict_types=1);

namespace Drupal\oafc_base\Webform\Entity\Form;

use Drupal\webform\EntitySettings\WebformEntitySettingsBaseForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for OAFC-specific third party settings on webforms.
 */
class OafcSettings extends WebformEntitySettingsBaseForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $webform = $this->getEntity();

    // Only relevant to course/event webforms.
    $is_course = strpos($webform->id(), 'course__') === 0;
    $is_event = strpos($webform->id(), 'event__') === 0;
    if (!($is_course || $is_event)) {
      $form['notice'] = [
        '#markup' => $this->t(
          'There are no OAFC-specific settings for this webform.'
        ),
      ];
      return $form;
    }

    // Mark the course/event as tax-exempt.
    $settings = $webform->getThirdPartySettings('oafc_base');
    $form['tax_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Tax settings'),
      '#open' => TRUE,
    ];
    $form['tax_settings']['hst_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GST/HST Number'),
      '#default_value' => $settings['hst_number'] ?? NULL,
      '#description' => $this->t('Leave this field blank to use the default HST number stored in store.'),
    ];
    $form['tax_settings']['is_tax_exempt'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is tax-exempt?'),
      '#default_value' => $settings['is_tax_exempt'] ?? FALSE,
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->getEntity()->setThirdPartySetting(
      'oafc_base',
      'is_tax_exempt',
      (bool) $form_state->getValue('is_tax_exempt')
    );

    $this->getEntity()->setThirdPartySetting(
      'oafc_base',
      'hst_number',
      $form_state->getValue('hst_number')
    );

    parent::save($form, $form_state);
  }

}
