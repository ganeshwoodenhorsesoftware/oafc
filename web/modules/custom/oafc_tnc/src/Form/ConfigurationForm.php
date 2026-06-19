<?php

namespace Drupal\oafc_tnc\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures module settings.
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oafc_tnc_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'oafc_tnc.tnc',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    /** @var \Drupal\Core\Config\ImmutableConfig $config */
    $config = $this->config('oafc_tnc.tnc');

    $form['tnc'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Terms and Conditions'),
      '#default_value' => $config->get('tnc'),
      '#format' => $config->get('tnc_format'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('oafc_tnc.tnc')
      ->set('tnc', $values['tnc']['value'])
      ->set('tnc_format', $values['tnc']['format'])
      ->save();
  }

}
