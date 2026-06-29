<?php

namespace Drupal\oafc_mini\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\link\LinkItemInterface;
use Drupal\user\UserInterface;

/**
 * Defines the OAFC mini entity.
 *
 * @ingroup oafc_mini
 *
 * @ContentEntityType(
 *   id = "oafc_mini",
 *   label = @Translation("OAFC Image"),
 *   bundle_label = @Translation("OAFC mini type"),
 *   handlers = {
 *     "view_builder" = "Drupal\oafc_mini\OafcMiniViewBuilder",
 *     "access" = "Drupal\oafc_mini\OafcMiniAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "oafc_mini",
 *   admin_permission = "administer oafc mini entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "edit-form" = "/images/{oafc_mini}/edit",
 *     "delete-form" = "/images/{oafc_mini}/delete",
 *   },
 * )
 */
class OafcMiniEntity extends ContentEntityBase implements OafcMiniEntityInterface
{

  /**
   * {@inheritdoc}
   */
  public function getOwner()
  {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId()
  {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid)
  {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account)
  {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
  {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('image'))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'default',
        'weight' => 0,
        'third_party_settings' => ['linked_field' => [
          'linked' => TRUE,
          'type' => 'field',
          'destination' => 'link',
          'advanced' => [],
        ]],
      ])
      ->setDisplayOptions('form', [
        'label' => 'hidden',
        'type' => 'image_image',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['menu'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'menu')
      ->setDisplayOptions('view', ['type' => 'hidden']);
    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDisplayOptions('view', ['type' => 'hidden'])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ]);
    $fields['region'] = BaseFieldDefinition::create('string')
      ->setDisplayOptions('view', ['type' => 'hidden'])
      ->setDisplayOptions('form', ['type' => 'hidden']);
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the mini entity author.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setDisplayOptions('view', ['type' => 'hidden'])
      ->setDisplayOptions('form', ['type' => 'hidden']);
    $fields['link'] = BaseFieldDefinition::create('link')
      ->setLabel(t('Link'))
      ->setDescription(t('The location this image link points to.'))
      ->setRequired(TRUE)
      ->setSettings([
        'link_type' => LinkItemInterface::LINK_GENERIC,
        'title' => DRUPAL_DISABLED,
      ])
      ->setDisplayOptions('view', ['type' => 'hidden'])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => -2,
      ]);

    return $fields;
  }
}
