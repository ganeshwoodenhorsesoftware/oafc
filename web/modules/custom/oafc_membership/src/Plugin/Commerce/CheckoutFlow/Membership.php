<?php

namespace Drupal\oafc_membership\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Membership checkout flow.
 *
 * @CommerceCheckoutFlow(
 *  id = "oafc_membership_membership",
 *  label = @Translation("Membership"),
 * )
 */
class Membership extends CheckoutFlowWithPanesBase {

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    return [
      'login' => [
        'label' => $this->t('Login'),
        'previous_label' => $this->t('Return to login'),
        'has_order_summary' => FALSE,
      ],
      'membership_details' => [
        'label' => $this->t('Membership details'),
        'next_label' => $this->t('Continue'),
        'previous_label' => $this->t('Go back'),
      ],
      'order_information' => [
        'label' => $this->t('Order information'),
        'next_label' => $this->t('Continue'),
        'previous_label' => $this->t('Go back'),
        'has_sidebar' => TRUE,
      ],
      'review' => [
        'label' => $this->t('Review'),
        'has_order_summary' => TRUE,
        'next_label' => $this->t('Continue'),
        'previous_label' => $this->t('Go back'),
        'has_sidebar' => TRUE,
      ],
    ] + parent::getSteps();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $pane_form, FormStateInterface $form_state, $step_id = NULL) {
    $form = parent::buildForm($pane_form, $form_state, $step_id);

    if ($step_id === 'login') {
      $form['actions']['#access'] = FALSE;
    }

    return $form;
  }

}
