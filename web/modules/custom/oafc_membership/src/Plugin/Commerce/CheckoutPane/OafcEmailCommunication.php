<?php

namespace Drupal\oafc_membership\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Email Communication declaration checkout pane.
 *
 * @CommerceCheckoutPane(
 *  id = "oafc_membership_individual_email_communication",
 *  label = @Translation("Email Communication Declaration"),
 *  admin_label = @Translation("Email Communication declaration"),
 *  wrapper_element = "fieldset",
 * )
 */
class OafcEmailCommunication extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition $field_definition */
    $field_definition = $this->order->getFieldDefinitions();
    /*
     * We are using drupal_map_assoc() here.
     * See https://www.drupal.org/node/2207453
     * We are assuming that the YES option will always be the first.
     * @TODO This can be improved.
     */
    $membership_email_options = array_combine([1, 0], $field_definition['field_oafc_email_communication']->getSettings());

    $pane_form['options'] = [
      '#type' => 'radios',
      '#title' => $this->t('OAFC Email Communication'),
      '#options' => $membership_email_options,
      '#required' => TRUE,
      '#title_display' => 'invisible',
    ];

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // Save the user's selection to the order.
    $values = $form_state->getValues();
    $this->order->set('field_oafc_email_communication', $values['oafc_membership_individual_email_communication']['options']);
    $this->order->save();
  }

}
