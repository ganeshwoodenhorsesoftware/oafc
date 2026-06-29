<?php

namespace Drupal\oafc_license_notifications\Plugin\QueueWorker;

use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\oafc_license_notifications\LicenseExpirationChecker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes license expiration notification queue items.
 *
 * @QueueWorker(
 *   id = "oafc_license_expiration_notification",
 *   title = @Translation("License Expiration Notification"),
 *   cron = {"time" = 60}
 * )
 */
class LicenseExpirationNotificationWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The license expiration checker service.
   *
   * @var \Drupal\oafc_license_notifications\LicenseExpirationChecker
   */
  protected $licenseChecker;

  /**
   * Constructs a new LicenseExpirationNotificationWorker.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\oafc_license_notifications\LicenseExpirationChecker $license_checker
   *   The license expiration checker service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config_factory,
    RendererInterface $renderer,
    LoggerChannelFactoryInterface $logger_factory,
    LicenseExpirationChecker $license_checker,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->logger = $logger_factory->get('oafc_license_notifications');
    $this->licenseChecker = $license_checker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.mail'),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('logger.factory'),
      $container->get('oafc_license_notifications.license_checker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    /** @var \Drupal\oafc_license_notifications\Entity\LicenseInterface $license */
    $license = $license_storage->load($data['license_id']);

    if (!$license) {
      $this->logger->error('License @license_id not found when processing expiration notification.', [
        '@license_id' => $data['license_id'],
      ]);
      return;
    }

    $purchased_entity = $license->getPurchasedEntity();
    if (!$purchased_entity) {
      $this->logger->error('Purchased entity for license @license_id not found.', [
        '@license_id' => $license->id(),
      ]);
      return;
    }

    // Double-check the license is still in an appropriate state.
    if (!in_array($license->getState()->getId(), ['active', 'renewal_in_progress'], TRUE)) {
      $this->logger->notice('License @license_id is no longer active, skipping notification.', [
        '@license_id' => $license->id(),
      ]);
      return;
    }

    $owner = $license->getOwner();
    if ($owner->isAnonymous()) {
      $this->logger->error('License @license_id owner not found.', [
        '@license_id' => $license->id(),
      ]);
      return;
    }

    // Check if this notification has already been sent for this interval.
    if ($this->licenseChecker->hasNotificationBeenSent($license, $data['notification_days'])) {
      return;
    }

    // Send the email.
    $this->sendNotificationEmail($license, $owner, $data['notification_days']);

    // Mark this notification as sent.
    $this->licenseChecker->markNotificationSent($license, $data['notification_days']);
  }

  /**
   * Send the notification email.
   *
   * @param \Drupal\commerce_license\Entity\LicenseInterface $license
   *   The license entity.
   * @param \Drupal\user\UserInterface $owner
   *   The license owner.
   * @param int $days
   *   Days until expiration.
   */
  protected function sendNotificationEmail($license, $owner, $days) {
    $config = $this->configFactory->get('oafc_license_notifications.settings');
    $site_config = $this->configFactory->get('system.site');
    $purchased_entity = $license->getPurchasedEntity();

    $to = $owner->getEmail();

    // Determine from email.
    $from = $config->get('from_email');
    if (empty($from)) {
      $order = $license->getOriginatingOrder();
      if ($order && ($store = $order->getStore()) && !empty($store->getEmail())) {
        $from = $store->getEmailFromHeader();
      }
      else {
        $name = str_replace([',', ';'], '', $site_config->get('name') ?? '');
        $mail = $site_config->get('mail');
        if (!empty($mail)) {
          $from = sprintf('%s <%s>', $name, $mail);
        }
      }
    }

    // Build email parameters.
    $params = [
      'headers' => [
        'Content-Type' => 'text/html; charset=UTF-8;',
        'Content-Transfer-Encoding' => '8Bit',
      ],
      'from' => $from,
      'license' => $license,
      'days_until_expiration' => $days,
      'owner' => $owner,
    ];

    // Add BCC if configured.
    if ($bcc = $config->get('bcc_email')) {
      $params['headers']['Bcc'] = $bcc;
    }

    $expiry_date = date('F j, Y', $license->getExpiresTime());

    if ($days == 1) {
      $params['subject'] = $this->t('Your @membership expires tomorrow', [
        '@membership' => $purchased_entity->label(),
      ]);
    }
    else {
      $params['subject'] = $this->t('Your @membership expires in @days days', [
        '@membership' => $purchased_entity->label(),
        '@days' => $days,
      ]);
    }

    // Build the email body.
    $build = [
      '#theme' => 'oafc_license_expiration_notification',
      '#license' => $license,
      '#owner' => $owner,
      '#days_until_expiration' => $days,
      '#expiry_date' => $expiry_date,
      '#purchased_entity' => $purchased_entity,
      '#purchased_entity_url' => $purchased_entity->toUrl()->setAbsolute()->toString(),
    ];

    // Add renewal URL if order exists.
    if ($order = $license->getOriginatingOrder()) {
      // Take the user to login and then redirect to the product page.
      $login_url = Url::fromRoute('user.login', [], [
        'query' => ['destination' => $purchased_entity->toUrl()->setAbsolute()->toString()],
        'absolute' => TRUE,
      ])->toString();
      $build['#renew_url'] = $login_url;
    }

    $params['body'] = $this->renderer->executeInRenderContext(new RenderContext(), function () use ($build) {
      return $this->renderer->render($build);
    });

    $langcode = $owner->getPreferredLangcode();

    $message = $this->mailManager->mail(
      'oafc_license_notifications',
      'license_expiration_warning',
      $to,
      $langcode,
      $params
    );

    if ($message['result']) {
      if ($config->get('log_notifications')) {
        $this->logger->info('Sent expiration notification for license @license_id to @email (@days days before expiration)', [
          '@license_id' => $license->id(),
          '@email' => $to,
          '@days' => $days,
        ]);
      }
    }
    else {
      $this->logger->error('Failed to send expiration notification for license @license_id to @email', [
        '@license_id' => $license->id(),
        '@email' => $to,
      ]);
    }
  }

}
