<?php

namespace Drupal\oafc_course;

use \Drupal\webform\Entity\WebformSubmission;
use \Drupal\commerce_order\Entity\Order;
use \Drupal\commerce_order\Entity\OrderItem;
use \Drupal\webform\Entity\Webform;
use Drupal\Core\Messenger\MessengerInterface;
use \Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The entry point for seat availability checking.
 */
class SeatAvailabilityChecker {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new SeatAvailabilityChecker object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * Adjusts the available course stock.
   *
   * @param \Drupal\commerce_order\Entity\OrderItem $item
   *   The order item.
   * @param string $op
   *   The order operation.
   */
  public function courseStockAdjustment(OrderItem $item, $op = NULL) {
    $webform_id = \Drupal::service('oafc_course.seat_availability_checker')->getCourseWebformId($item);

    if (!$webform_id) {
      return FALSE;
    }
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_id);
    if (empty($webform)) {
      return FALSE;
    }
    // There may be multiple attendees per registration.
    // Calculating the number of attendees via the webform components.
    $submission_id = $item->getData('submision_id');
    if (!empty($submission_id)) {
      $webform_submission = WebformSubmission::load($submission_id);
      $contact_count = oafc_course_get_submission_contact_count($webform_submission->getData());
      if ($contact_count > 0) {
        $stock = $webform->getThirdPartySetting('oafc_course', 'remaining_stock');
        $remaining_stock = $stock - $contact_count;
        if ($op == "delete") {
          $stock = $webform->getThirdPartySetting('oafc_course', 'remaining_stock');
          $remaining_stock = $stock + $contact_count;
        }
        $webform->setThirdPartySetting('oafc_course', 'remaining_stock', $remaining_stock);
        $webform->save();
      }
    }
  }

  /**
   * Check and process the stock request against an course order item.
   *
   * Check whether the course has requested open seats.
   * If there are no open seats to fulfill the request that
   * order item is removed from cart.
   *
   * @param Drupal\commerce_order\Entity\Order $order
   *   Order object.
   * @param int $total_quantity
   *   The total webform order items.
   * @param int $number_of_course_items
   *   The number of course items in the order.
   */
  public function checkForOpenSeats(Order $order, $total_quantity, $number_of_course_items) {
    $adjustments = $order->getAdjustments();
    foreach ($order->getItems() as $item) {
      $webform_id = \Drupal::service('oafc_course.seat_availability_checker')->getCourseWebformId($item);
      if (!$webform_id) {
        return FALSE;
      }
      $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webform_id);
      if (empty($webform)) {
        return FALSE;
      }
      // There may be multiple attendees per registration.
      // Calculating the number of attendees via the webform components.
      $submission_id = $item->getData('submision_id');
      if (!empty($submission_id)) {
        $webform_submission = WebformSubmission::load($submission_id);
        $contact_count = oafc_course_get_submission_contact_count($webform_submission->getData());
        if ($contact_count > 0) {
          $remaining_stock = $webform->getThirdPartySetting('oafc_course', 'remaining_stock');
          if ($remaining_stock <= $contact_count) {
            $message = \Drupal::service('oafc_course.seat_availability_checker')->getCourseSeatValidationMessage($webform, $contact_count, $total_quantity, $number_of_course_items);
            if (!empty($message)) {
              $this->messenger->addWarning($message);
              $item->delete();
              foreach ($adjustments as $adjustment) {
                $item_id = $adjustment->getSourceId();
                if ($item_id == $item->id()) {
                  $order->removeAdjustment($adjustment);
                  $order->save();
                }
              }
              $url = Url::fromRoute('commerce_cart.page')->toString();
              $response = new RedirectResponse($url);
              $response->send();
              return;
            }
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Validates the course registration form.
   *
   * @see oafc_course_form_alter
   */
  public static function oafcCourseRegistrationValidate(&$form, &$form_state) {
    $webform_id = $form['#webform_id'];
    // Webform id is created with pattern node_type__node_id,
    // hence exploding the webform_id to get the nodetype.
    $webform_id_components = explode('__', $webform_id);
    if (isset($webform_id_components['0']) && $webform_id_components['0'] == "course" && !empty($form_state->getFormObject())) {
      $webform = Webform::load($webform_id);
      $remaining_stock = $webform->getThirdPartySetting('oafc_course', 'remaining_stock');
      $attendeesDetails = \Drupal::service('oafc_course.seat_availability_checker')->getAttendeesDetails($form['elements']);
      if (!$attendeesDetails) {
        return;
      }
      $form_state_values = $form_state->getValues();
      $item_quantity = count($form_state_values[$attendeesDetails['contact_element_key']]);
      if ($item_quantity > $remaining_stock) {
        if ($attendeesDetails['contact_element']) {
          $message = \Drupal::service('oafc_course.seat_availability_checker')->getCourseSeatValidationMessage($webform, $item_quantity, [], []);
          $form_state->setError($attendeesDetails['contact_element'], $message);

        }
      }
    }
  }

  /**
   * Provides the attendees form element details.
   *
   * @param array $elements
   *   The form elements.
   */
  public function getAttendeesDetails($elements) {
    $output = [];
    // Getting the form element in which the error should be displayed
    // Looping through the elements to see whether there is a element of
    // key first_name,if yes then that would be the contact element
    // of the course webform.
    foreach ($elements as $parent_key => $element) {
      if (isset($element['#element'])) {
        foreach ($element['#element'] as $key => $sub_element) {
          if (is_array($sub_element) && ($key == 'first_name')) {
            $output['contact_element_key'] = $parent_key;
            $output['contact_element'] = $element;
          }
        }
      }
    }
    return $output;
  }

  /**
   * Fetches the webform ID associated with the order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItem $item
   *   The order item.
   */
  public function getCourseWebformId(OrderItem $item) {
    $purchased_entity = $item->getPurchasedEntity();
    $type = $item->bundle();
    if ($purchased_entity && $type != 'webform') {
      return FALSE;
    }
    // We have to add the course fee only to webforms of
    // type course.
    $webform_id = $item->getData('webform_id');
    // Webform id is created with pattern node_type__node_id,
    // hence exploding the webform_id to get the nodetype.
    $webform_id_components = explode('__', $webform_id);
    if ($webform_id_components['0'] != "course") {
      return FALSE;
    }
    return $webform_id;
  }

  /**
   * Returns the appropriate validation message for course stock.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   The weform object.
   * @param int $quantity
   *   The  requested quantity.
   * @param int $total_quantity
   *   The total quantity requested in the order.
   * @param int $number_of_course_items
   *   The number of course items in the order.
   */
  public function getCourseSeatValidationMessage(Webform $webform, $quantity, $total_quantity = [], $number_of_course_items = []) {
    $webform_id = $webform->id();
    $course_link = Url::fromRoute('entity.webform.canonical', ['webform' => $webform_id])->toString();
    $remaining_stock = $webform->getThirdPartySetting('oafc_course', 'remaining_stock');
    if ($remaining_stock == 0) {
      $message = t('Sorry, All seats are booked for @name, please contact the course administrator', ['@name' => $webform->label()]);
      return $message;
    }
    if ($remaining_stock < $quantity) {
      $message = \Drupal::translation()->formatPlural($remaining_stock,
        'Sorry, we only have @quantity seat available for @name.
        Please remove attendees from your <a href = "@registration">registration</a>
        form or contact the course administrator.',
        'Sorry, we only have @quantity seats available for @name.
        Please remove attendees from your <a href = "@registration">registration</a>
        form or contact the course administrator.',
        [
          '@quantity' => $remaining_stock,
          '@name' => $webform->label(),
          '@registration' => $course_link,
        ]);
      return $message;
    }
    if (!empty($total_quantity) && !empty($number_of_course_items)) {
      if ($remaining_stock < $total_quantity[$webform_id] && $number_of_course_items[$webform_id] > 1) {
        $message = \Drupal::translation()->formatPlural($remaining_stock,
          'Sorry, we only have @quantity seat available for @name.
          Please remove attendees from your <a href = "@registration">registration</a>
          form or contact the course administrator.',
          'Sorry, we only have @quantity seats available for @name.
          Please remove attendees from your <a href = "@registration">registration</a>
          form or contact the course administrator.',
          [
            '@quantity' => $remaining_stock,
            '@name' => $webform->label(),
            '@registration' => $course_link,
          ]);
        return $message;
      }
    }
    return NULL;
  }

}
