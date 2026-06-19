<?php

namespace Drupal\oafc_membership\Form;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\oafc_membership\CommerceLicense\LicenseStorage;

/**
 * Provide the form for transfering a license.
 *
 * Transferring a license is implemented as permanently deactivating the current
 * license i.e. revoke or cancel, and creating a new license with the same
 * expiration type and time for the new owner. This is mostly so that we can
 * keep a clean history of the licenses and their associations with the
 * originating orders and owners.
 */
class TransferLicense extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_license_transfer';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->canBeTransferred()) {
      return parent::buildForm($form, $form_state);
    }

    $form['invalid_state'] = [
      '#markup' => $this->t(
        'Only active licenses that can be revoked or cancelled can be
         transferred.'
      ),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['transferee_uid'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('User to transfer the license to'),
      '#target_type' => 'user',
      '#default_value' => NULL,
      '#placeholder' => $this->t('Search by username'),
      '#required' => TRUE,
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate that the license can be transferred.
    if (!$this->canBeTransferred()) {
      $form_state->setError(
        $form,
        $this->t(
          'Only active licenses that can be revoked or cancelled can be
           transferred.'
        )
      );
    }

    // Validate that the new owner is not the current owner.
    $transferee_uid = $form_state->getValues()['transferee_uid'];
    $current_license = $this->getLicense();
    if ($transferee_uid == $current_license->getOwnerId()) {
      $form_state->setError(
        $form['transferee_uid'],
        $this->t('You cannot transfer the license to the same user.')
      );
    }

    // Validate the transferee does not have a license of the same type already.
    $purchased_entity = $current_license->getPurchasedEntity();
    if (!$purchased_entity instanceof ProductVariationInterface) {
      return $this->getLicense();
    }
    /** @var \Drupal\oafc_membership\CommerceLicense\LicenseStorage $license_storage */
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    $existing_license = $license_storage->getExistingLicense(
      $purchased_entity,
      $transferee_uid
    );
    if ($existing_license) {
      $form_state->setError(
        $form['transferee_uid'],
        $this->t(
          'The user that you are trying to transfer the license to already has
           <a href=":link">a license</a> of the same type.',
          [':link' => $existing_license->toUrl()->toString()]
        )
      );
    }

    return $this->getLicense();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\oafc_membership\CommerceLicense\LicenseStorage $license_storage */
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    $new_license = $this->createNewLicense(
      $license_storage,
      $form_state->getValues()['transferee_uid']
    );
    $this->updateCurrentLicense(
      $license_storage,
      $new_license
    );

    $this->messenger()->addStatus(
      $this->t(
        'This license has been revoked/cancelled and a
         <a href=":link">new license</a> has been created.',
        [':link' => $new_license->toUrl()->toString()]
      )
    );
    $form_state->setRedirectUrl($this->getLicense()->toUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to transfer this license?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getLicense()->toUrl();
  }

  /**
   * Returns the license entity for this form.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface
   *   The license entity.
   */
  protected function getLicense(): LicenseInterface {
    $entity = $this->getEntity();
    assert($entity instanceof LicenseInterface);
    return $entity;
  }

  /**
   * Returns whether the license can be transferred.
   *
   * A license can be transferred if we can permanently deactivate it. That is,
   * if the license is in pending state we cancel it, if it is in active or
   * suspension states (`active`, `renewal_in_progress`, `suspended`) we revoke
   * it.
   *
   * @return bool
   *   TRUE if the license can be transferred, FALSE otherwise.
   */
  protected function canBeTransferred() {
    $state_item = $this->getLicense()->getState();
    $can_revoke = $state_item->isTransitionAllowed('revoke');
    $can_cancel = $state_item->isTransitionAllowed('cancel');
    if ($can_revoke || $can_cancel) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Creates and returns the new license.
   *
   * We want to carry over information about type, state, purchased entity,
   * expiration type and time, but not other fields. As far as the rest, it
   * should be treated as a new license.
   *
   * @param \Drupal\oafc_membership\CommerceLicense\LicenseStorage $license_storage
   *   The license entity storage.
   * @param string $transferee_uid
   *   The ID of the user to assign as the owner of the new license.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface
   *   The created license entity.
   */
  protected function createNewLicense(
    LicenseStorage $license_storage,
    string $transferee_uid,
  ): LicenseInterface {
    $current_license = $this->getLicense();
    $new_license = $current_license->createDuplicate();

    // We set the new owner.
    $new_license->setOwnerId((int) $transferee_uid);

    // We add a reference to the current license so that administrators can
    // follow the trail. We do remove the originating order reference though
    // because that is the order that the current license was purchased, not
    // this one. Administrators can get there via the current license.
    // If recurring is ever enabled - not the plan now, this will need to be
    // reviewed e.g. review if the original order is used to determine details
    // e.g. payment details of subsequent renewals. In any case we wouldn't want
    // to carry over the payment method from the previous license owner to the
    // new owner.
    $new_license->set('originating_order', NULL);
    $new_license->set('field_transferred_from', $current_license);

    $now = $this->time->getRequestTime();
    $new_license->setCreatedTime($now);
    $new_license->setChangedTime($now);
    $new_license->setGrantedTime($now);
    $new_license->set('renewed', NULL);

    $license_storage->save($new_license);
    return $new_license;
  }

  /**
   * Updates the current license.
   *
   * @param \Drupal\oafc_membership\CommerceLicense\LicenseStorage $license_storage
   *   The license entity storage.
   * @param \Drupal\commerce_license\Entity\LicenseInterface $new_license
   *   The new license that was created.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface
   *   The updated license entity.
   */
  protected function updateCurrentLicense(
    LicenseStorage $license_storage,
    LicenseInterface $new_license,
  ): LicenseInterface {
    $current_license = $this->getLicense();

    // We revoke or cancel the license depending on the current state.
    $transition_id = 'revoke';
    $state_field = $current_license->getState();
    if (!$state_field->isTransitionAllowed('revoke')) {
      $transition_id = 'cancel';
    }
    $current_license->getState()->applyTransitionById($transition_id);

    // We add a reference to the current license so that administrators can
    // follow the trail.
    $current_license->set('field_transferred_to', $new_license);

    $license_storage->save($current_license);
    return $current_license;
  }

}
