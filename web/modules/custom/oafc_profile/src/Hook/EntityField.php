<?php

declare(strict_types=1);

namespace Drupal\oafc_profile\Hook;

use Drupal\user\UserInterface;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Provides methods implementing hooks related to entity fields.
 */
class EntityField {

  use StringTranslationTrait;

  /**
   * Constructs a new EntityField object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    TranslationInterface $string_translation
  ) {
    // Injections required by traits.
    $this->stringTranslation = $string_translation;
  }

  /**
   * Returns pseudo-field components related to industry member profiles.
   *
   * We expose the profile content item associated to an industry member user as
   * a pseudo field so that we can easily display it in the user view page. No
   * need to maintain an entity reference field because that already exists as
   * the author of the content item. The convention is that a user can only have
   * one profile and there will only be one user per company that the profile
   * will belong to and will be able to edit it.
   *
   * @return array
   *   An array containing the pseudo-field components.
   *
   * @see hook_entity_extra_field_info()
   * @see \Drupal\Core\Entity\EntityFieldManagerInterface::getExtraFields()
   */
  public function extraFieldInfo() {
    $extra = [];

    // Pseudo-field that renders the industry member profile authored by the
    // user.
    $extra['user']['user']['display']['profile'] = [
      'label' => $this->t('Industry member profile'),
      'description' => $this->t(
        'The profile for users with the Industry Member role.'
      ),
      'weight' => 0,
    ];

    return $extra;
  }

  /**
   * Renders the industry member profile pseudo-field for the given user.
   *
   * @param array &$build
   *   A renderable array representing the entity content.
   * @param \Drupal\user\UserInterface $user
   *   The user entity being rendered.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display holding the display options configured for the
   *   entity components.
   * @param string $view_mode
   *   The view mode the entity is rendered in.
   *
   * @see hook_entity_ENTITY_TYPE_view()
   */
  public function profileFieldView(
    array &$build,
    UserInterface $user,
    EntityViewDisplayInterface $display,
    string $view_mode
  ) {
    // Only relevant to industry members.
    if (!in_array('industry_member', $user->getRoles())) {
      return;
    }

    $node_storage = $this->entityTypeManager->getStorage('node');
    $node_ids = $node_storage
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'profile')
      ->condition('uid', $user->id())
      ->execute();

    // If the user is an industry member but does not have a profile yet, we
    // display a link to create it.
    if (!$node_ids) {
      $build['profile'] = $this->buildLinkToCreateProfile();
      return;
    }

    // We should never have more than one profile, it could happen though due to
    // human/software error. We could display the first one, however, it's
    // better to get the error corrected. Otherwise we could have duplicate
    // profiles in the listing with one being outdated.
    if (count($node_ids) > 1) {
      $build['profile'] = [
        '#markup' => $this->t(
          'More than one industry member profile associated with this account
           has been detected. Please contact the website administrator to
           resolve the issue before you can edit your profile again.'
        ),
      ];
      return;
    }

    // If there is a profile already, we render it in teaser mode. Users can
    // follow the link to edit it.
    $build['profile'] = $this->entityTypeManager
      ->getViewBuilder('node')
      ->view(
        $node_storage->load(current($node_ids)),
        'listing'
      );
  }

  /**
   * Returns a link to create a new profile content item.
   *
   * @return array
   *   A render array containing the link and help text.
   */
  protected function buildLinkToCreateProfile() {
    $build = [
      '#type' => 'container',
    ];

    $url = Url::fromRoute(
      'node.add',
      ['node_type' => 'profile']
    );
    $build['link'] = [
      '#type' => 'link',
      '#title' => $this->t('Create your industry member profile'),
      '#url' => $url,
      '#options' => $url->getOptions(),
    ];

    // Description does not seem to be used by link render arrays. Add some help
    // text separately.
    $build['description'] = [
      '#markup' => '<p>' . $this->t(
        "Being an active industry member, you can create your profile that
         will be published under our Buyer's Guide. A website administrator
         will review it before it is made publicly available."
      ) . '</p>',
    ];

    return $build;
  }

}
