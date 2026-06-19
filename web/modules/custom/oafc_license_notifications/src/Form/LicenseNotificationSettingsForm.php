<?php

namespace Drupal\oafc_license_notifications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure license expiration notification settings.
 */
class LicenseNotificationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'oafc_license_notifications_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['oafc_license_notifications.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('oafc_license_notifications.settings');

    $form['notification_intervals'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Notification Intervals'),
      '#description' => $this->t('Configure how many days before expiration to send notification emails. Leave empty to disable that notification.'),
    ];

    $form['notification_intervals']['first_notification_days'] = [
      '#type' => 'number',
      '#title' => $this->t('First notification (days before expiration)'),
      '#description' => $this->t('Send the first notification this many days before the license expires. Recommended: 30 days.'),
      '#default_value' => $config->get('first_notification_days') ?? 0,
      '#min' => 0,
      '#max' => 365,
    ];

    $form['notification_intervals']['second_notification_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Second notification (days before expiration)'),
      '#description' => $this->t('Send the second notification this many days before the license expires. Recommended: 10 days.'),
      '#default_value' => $config->get('second_notification_days') ?? 0,
      '#min' => 0,
      '#max' => 365,
    ];

    $form['notification_intervals']['third_notification_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Third notification (days before expiration) - Optional'),
      '#description' => $this->t('Send a third notification this many days before the license expires. Leave at 0 to disable.'),
      '#default_value' => $config->get('third_notification_days') ?? 0,
      '#min' => 0,
      '#max' => 365,
    ];

    $form['email_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Email Settings'),
    ];

    $form['email_settings']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable automatic notifications'),
      '#description' => $this->t('When enabled, notification emails will be sent automatically via cron.'),
      '#default_value' => $config->get('enabled') ?? TRUE,
    ];

    $form['email_settings']['from_email'] = [
      '#type' => 'email',
      '#title' => $this->t('From email address'),
      '#description' => $this->t('Leave empty to use the site default email address.'),
      '#default_value' => $config->get('from_email') ?? '',
    ];

    $form['email_settings']['bcc_email'] = [
      '#type' => 'email',
      '#title' => $this->t('BCC email address'),
      '#description' => $this->t('Optionally send a copy of all notifications to this address.'),
      '#default_value' => $config->get('bcc_email') ?? '',
    ];

    $form['debugging'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Debugging'),
    ];

    $form['debugging']['log_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log all notification attempts'),
      '#description' => $this->t('When enabled, all notification attempts will be logged to watchdog.'),
      '#default_value' => $config->get('log_notifications') ?? TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $first = $form_state->getValue('first_notification_days');
    $second = $form_state->getValue('second_notification_days');
    $third = $form_state->getValue('third_notification_days');

    // Ensure notifications are in descending order.
    if ($second >= $first) {
      $form_state->setErrorByName('second_notification_days',
        $this->t('The second notification must be fewer days than the first notification.'));
    }

    if ($third > 0 && $third >= $second) {
      $form_state->setErrorByName('third_notification_days',
        $this->t('The third notification must be fewer days than the second notification.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('oafc_license_notifications.settings')
      ->set('first_notification_days', $form_state->getValue('first_notification_days'))
      ->set('second_notification_days', $form_state->getValue('second_notification_days'))
      ->set('third_notification_days', $form_state->getValue('third_notification_days'))
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('from_email', $form_state->getValue('from_email'))
      ->set('bcc_email', $form_state->getValue('bcc_email'))
      ->set('log_notifications', $form_state->getValue('log_notifications'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
