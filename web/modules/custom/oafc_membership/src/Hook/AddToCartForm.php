<?php

namespace Drupal\oafc_membership\Hook;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_product\Entity\ProductInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides methods implementing hooks related to add-to-cart forms.
 */
class AddToCartForm {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * Constructs a new AddToCartForm object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   The current user account proxy.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   */
  public function __construct(
    AccountProxyInterface $account_proxy,
    EntityTypeManagerInterface $entity_type_manager,
    TranslationInterface $string_translation,
    CurrencyFormatterInterface $currency_formatter,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->account = $account_proxy->getAccount();

    // Injections required by traits.
    $this->stringTranslation = $string_translation;
    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * Makes alterations to the add-to-cart form.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function alter(array &$form, FormStateInterface $form_state) {
    // We only alter add-to-cart forms on membership product pages.
    $product = $form_state->getStorage()['product'] ?? NULL;
    if (!$product) {
      return;
    }

    $membership_bundles = [
      'individual_membership',
      'industry_membership',
    ];
    if (!in_array($product->bundle(), $membership_bundles)) {
      return;
    }

    // Get price from default variation.
    $variation = $product->getDefaultVariation();
    $price = $variation ? $variation->getPrice() : NULL;
    $price_formatted = $this->currencyFormatter->format($price->getNumber(), $price->getCurrencyCode());

    // Changes for all cases.
    $form['actions']['submit']['#value'] = $this->t('Register Now for @price', ['@price' => $price_formatted]);
    $form['#validate'][] = 'oafc_membership_membership_validate';
    $form['actions']['submit']['#submit'][] = 'oafc_membership_add_to_cart_redirect';

    if ($this->account->isAnonymous()) {
      return;
    }

    /** @var \Drupal\oafc_membership\CommerceLicense\LicenseStorage $license_storage */
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    $license = $license_storage->getExistingLicense(
      $product->getDefaultVariation(),
      $this->account->id()
    );
    if (!$license) {
      return;
    }

    $this->alterForPendingLicenses($form, $form_state, $product, $license);
    $this->alterRenewalOrderItems($form, $form_state, $product, $license);
  }

  /**
   * Disables the add-to-cart button if there is a license pending approval.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license.
   */
  protected function alterForPendingLicenses(
    array &$form,
    FormStateInterface $form_state,
    ProductInterface $product,
    LicenseInterface $license,
  ) {
    if ($license->getState()->getId() !== 'pending') {
      return;
    }

    $form['actions']['submit']['#value'] = $this->t('Register Now');
    $form['actions']['submit']['#disabled'] = TRUE;
    $form['submit_message'] = [
      '#markup' => $this->t(
        '<strong>You already have an existing license that is pending approval. Please
         wait until we review your order.</strong>'
      ),
    ];
  }

  /**
   * Performs alterations when we are renewing an existing license.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license.
   */
  protected function alterRenewalOrderItems(
    array &$form,
    FormStateInterface $form_state,
    ProductInterface $product,
    LicenseInterface $license,
  ) {
    // Get price from default variation.
    $variation = $product->getDefaultVariation();
    $price = $variation ? $variation->getPrice() : NULL;
    $price_formatted = $this->currencyFormatter->format($price->getNumber(), $price->getCurrencyCode());
    $form['actions']['submit']['#value'] = $this->t('Renew Membership for @price', ['@price' => $price_formatted]);
  }

}
