<?php

declare(strict_types=1);

namespace Drupal\oafc_no_tax\Resolver;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_tax\Resolver\TaxRateResolverInterface;
use Drupal\commerce_tax\TaxZone;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\profile\Entity\ProfileInterface;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Tax resolver for order items that are tax exempt.
 */
class NoTaxRateResolver implements TaxRateResolverInterface {

  /**
   * Constructs a new NoTaxRateResolver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(
    TaxZone $zone,
    OrderItemInterface $order_item,
    ProfileInterface $customer_profile
  ) {
    if ($order_item->bundle() === 'webform') {
      return $this->resolveWebformItem($order_item);
    }

    return $this->resolveEventVariation($order_item);
  }

  /**
   * Exempt items belonging to tax-exempt webforms from tax.
   *
   * When a webform for a course or event is submitted, order items are added
   * for elements that have a price. We expempt those from tax if the webform is
   * marked as tax-exempt in its third party settings.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return string|null
   *   The tax exemption string if the order item is deemed to be tax exempt,
   *   NULL otherwise.
   */
  protected function resolveWebformItem(
    OrderItemInterface $order_item
  ): ?string {
    $webform_id = $order_item->getData('webform_id');
    if ($webform_id === NULL) {
      return NULL;
    }

    $is_tax_exempt = $this->entityTypeManager
      ->getStorage('webform')
      ->load($webform_id)
      ->getThirdPartySetting(
        'oafc_base',
        'is_tax_exempt'
      );
    if ($is_tax_exempt === TRUE) {
      return TaxRateResolverInterface::NO_APPLICABLE_TAX_RATE;
    }

    return NULL;
  }

  /**
   * Exempt from tax event variations marked as tax exempts.
   *
   * Event product/variation types seem to not be used. Clarify and remove,
   * including this tax rate resolution.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return string|null
   *   The tax exemption string if the order item is deemed to be tax exempt,
   *   NULL otherwise.
   */
  protected function resolveEventVariation(
    OrderItemInterface $order_item
  ): ?string {
    $purchased_entity = $order_item->getPurchasedEntity();
    if (!$purchased_entity) {
      return NULL;
    }
    if (!$purchased_entity instanceof ProductVariationInterface) {
      return NULL;
    }

    $product = $purchased_entity->getProduct();
    if ($product->bundle() !== 'events') {
      return NULL;
    }

    $taxable_field = $product->get('field_is_taxable');
    if ($taxable_field->isEmpty()) {
      return NULL;
    }

    if (!$taxable_field->getValue()[0]['value']) {
      return TaxRateResolverInterface::NO_APPLICABLE_TAX_RATE;
    }

    return NULL;
  }

}
