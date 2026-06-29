<?php

namespace Drupal\oafc_membership\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Individual membership declaration checkout pane.
 *
 * @CommerceCheckoutPane(
 *  id = "oafc_membership_individual_membership_declaration",
 *  label = @Translation("Membership Declaration"),
 *  admin_label = @Translation("Individual membership declaration"),
 *  wrapper_element = "fieldset",
 * )
 */
class IndividualMembershipDeclaration extends CheckoutPaneBase implements CheckoutPaneInterface {

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
    $membership_declaration_options = array_combine([1, 0], $field_definition['field_individual_member_decl']->getSettings());

    $pane_form['options'] = [
      '#type' => 'radios',
      '#title' => $this->t('Membership Declaration'),
      '#options' => $membership_declaration_options,
      '#required' => TRUE,
      '#title_display' => 'invisible',
    ];

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValues();
    $this->order->set('field_individual_member_decl', $values['oafc_membership_individual_membership_declaration']['options']);
    $this->order->save();
  }

}
