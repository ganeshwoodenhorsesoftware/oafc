<?php

namespace Drupal\oafc_license_notifications;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Service to check for licenses approaching expiration.
 */
class LicenseExpirationChecker {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Constructs a new LicenseExpirationChecker.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    TimeInterface $time,
    LoggerChannelFactoryInterface $logger_factory,
    QueueFactory $queue_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->time = $time;
    $this->logger = $logger_factory->get('oafc_license_notifications');
    $this->queueFactory = $queue_factory;
  }

  /**
   * Check for licenses that need expiration notifications.
   *
   * @return int
   *   The number of notifications queued.
   */
  public function checkExpiringLicenses() {
    $config = $this->configFactory->get('oafc_license_notifications.settings');

    // Check if notifications are enabled.
    if (!$config->get('enabled')) {
      return 0;
    }

    $notification_days = [];

    // Get the configured notification days only if they are greater than 0.
    $notification_days = array_filter([
      $config->get('first_notification_days'),
      $config->get('second_notification_days'),
      $config->get('third_notification_days'),
    ], function ($day) {
      return $day > 0;
    });

    if (empty($notification_days)) {
      return 0;
    }

    $license_storage = $this->entityTypeManager->getStorage('commerce_license');
    $queue = $this->queueFactory->get('oafc_license_expiration_notification');
    $current_time = $this->time->getRequestTime();
    $queued_count = 0;

    foreach ($notification_days as $days) {
      // Calculate the target expiration time.
      // We want licenses expiring between $days and $days+1 from now.
      $target_time_start = $current_time + ($days * 86400);
      $target_time_end = $current_time + (($days + 1) * 86400);

      // Query for active licenses expiring in this window.
      $query = $license_storage->getQuery()
        ->condition('state', ['active', 'renewal_in_progress'], 'IN')
        ->condition('expires', 0, '<>')
        ->condition('expires', $target_time_start, '>=')
        ->condition('expires', $target_time_end, '<')
        ->accessCheck(FALSE);

      $license_ids = $query->execute();

      if (empty($license_ids)) {
        continue;
      }

      $licenses = $license_storage->loadMultiple($license_ids);

      foreach ($licenses as $license) {
        /** @var \Drupal\oafc_license_notifications\Entity\LicenseInterface $license */
        // Ensure license is of type 'role'.
        if ($license->bundle() !== 'role') {
          continue;
        }

        // Check if we've already sent this notification level.
        if ($this->hasNotificationBeenSent($license, $days)) {
          continue;
        }

        // Queue the notification.
        $queue->createItem([
          'license_id' => $license->id(),
          'notification_days' => $days,
          'expires_time' => $license->getExpiresTime(),
        ]);

        $queued_count++;

        if ($config->get('log_notifications')) {
          $this->logger->info('Queued expiration notification for license @license_id (@days days before expiration)', [
            '@license_id' => $license->id(),
            '@days' => $days,
          ]);
        }
      }
    }

    return $queued_count;
  }

  /**
   * Check if a notification has already been sent for this license & interval.
   *
   * @param \Drupal\oafc_license_notifications\Entity\LicenseInterface $license
   *   The license entity.
   * @param int $days
   *   The number of days before expiration.
   *
   * @return bool
   *   TRUE if notification has been sent, FALSE otherwise.
   */
  public function hasNotificationBeenSent(LicenseInterface $license, $days) {
    // Get the expiration_notifications array from license data.
    $notifications = $license->getData('expiration_notifications', []);
    return in_array($days, $notifications, TRUE);
  }

  /**
   * Mark a notification as sent for a license.
   *
   * @param \Drupal\oafc_license_notifications\Entity\LicenseInterface $license
   *   The license entity.
   * @param int $days
   *   The number of days before expiration.
   */
  public function markNotificationSent(LicenseInterface $license, $days) {
    // Get existing notifications.
    $notifications = $license->getData('expiration_notifications', []);

    // Add this notification if not already present.
    if (!in_array($days, $notifications, TRUE)) {
      $notifications[] = $days;
      $license->setData('expiration_notifications', $notifications);
      $license->save();
    }
  }

}
