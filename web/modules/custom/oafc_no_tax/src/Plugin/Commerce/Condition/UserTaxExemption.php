<?php

namespace Drupal\oafc_no_tax\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\entity\EntityInterface;

/**
 * Provides tax exemption for users.
 *
 * @CommerceCondition(
 *   id = "user_tax_exemption",
 *   label = @Translation("Is not tax exempt"),
 *   category = @Translation("Customer"),
 *   entity_type = "commerce_order",
 *   weight = 10,
 * )
 */
class UserTaxExemption extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $order) {
    $this->assertEntity($order);
    assert($order instanceof OrderInterface);

    // The field value will be either TRUE, FALSE or NULL. The condition passes
    // if it is FALSE/NULL i.e. apply the tax if the user does not have a tax
    // exemption.
    return !(bool) $order->getCustomer()
      ->get('field_is_tax_exempt')
      ->value;
  }

}
