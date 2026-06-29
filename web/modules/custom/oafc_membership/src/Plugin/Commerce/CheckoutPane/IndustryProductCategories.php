<?php

namespace Drupal\oafc_membership\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;

/**
 * Industry product categories checkout pane.
 *
 * @CommerceCheckoutPane(
 *  id = "oafc_membership_industry_product_categories",
 *  label = @Translation("Product / Service Categories"),
 *  admin_label = @Translation("Industry Product Categories"),
 *  wrapper_element = "fieldset",
 * )
 */
class IndustryProductCategories extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * The account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * Constructs a new CheckoutPaneBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   The account proxy.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $account_proxy) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);

    $this->accountProxy = $account_proxy;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\taxonomy\TermStorageInterface $term_storage */
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    /** @var array $product_categories */
    $product_categories = $term_storage->loadTree('industry_member_categories');
    $options = [];

    foreach ($product_categories as $product_category) {
      $options[$product_category->tid] = $product_category->name;
    }

    $pane_form['product_categories'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Product / Service Categories'),
      '#options' => $options,
      '#required' => TRUE,
      '#title_display' => 'invisible',
    ];

    $pane_form['other_categories'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other Categories'),
      '#attributes' => [
        'placeholder' => $this->t('Enter text...'),
      ],
      '#description' => $this->t('Separate categories by comma. Please note that new categories will not be created at this time.  Thank you.'),
    ];

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var array $values */
    $values = $form_state->getValues();

    $this->order->set('field_product_categories', array_filter($values['oafc_membership_industry_product_categories']['product_categories']));
    $this->order->save();

    /** @var \Drupal\Core\Session\AccountInterface $current_account */
    $current_account = $this->accountProxy->getAccount();
    /** @var \Drupal\user\UserInterface $account */
    $account = User::load($current_account->id());

    $account->set('field_service_categories', array_filter($values['oafc_membership_industry_product_categories']['product_categories']));
    $account->save();
  }

}
