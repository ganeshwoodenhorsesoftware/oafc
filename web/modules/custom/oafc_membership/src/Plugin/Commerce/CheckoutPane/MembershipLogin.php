<?php

namespace Drupal\oafc_membership\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\Login;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the login pane for membership.
 *
 * @CommerceCheckoutPane(
 *   id = "oafc_membership_login",
 *   label = @Translation("Login or continue as guest"),
 *   default_step = "login",
 * )
 */
class MembershipLogin extends Login implements CheckoutPaneInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

    $pane_form['returning_customer']['submit']['#attributes']['class'][] = 'btn--primary';
    $pane_form['register']['register']['#attributes']['class'][] = 'btn--primary';

    return $pane_form;
  }

}
