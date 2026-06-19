<?php

namespace Drupal\Tests\oafc_license_notifications\Unit;

use Drupal\commerce_license\Entity\LicenseInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\oafc_license_notifications\LicenseExpirationChecker;
use Drupal\oafc_license_notifications\Plugin\QueueWorker\LicenseExpirationNotificationWorker;
use Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;

/**
 * Tests the LicenseExpirationNotificationWorker queue worker.
 *
 * @coversDefaultClass \Drupal\oafc_license_notifications\Plugin\QueueWorker\LicenseExpirationNotificationWorker
 * @group oafc_license_notifications
 */
class LicenseExpirationNotificationWorkerTest extends UnitTestCase {

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mail manager mock.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mailManager;

  /**
   * The config factory mock.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The renderer mock.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * The logger factory mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The logger channel mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The license expiration checker mock.
   *
   * @var \Drupal\oafc_license_notifications\LicenseExpirationChecker|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $licenseChecker;

  /**
   * The queue worker under test.
   *
   * @var \Drupal\oafc_license_notifications\Plugin\QueueWorker\LicenseExpirationNotificationWorker
   */
  protected $queueWorker;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create mocks for dependencies.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->renderer = $this->createMock(RendererInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);
    $this->licenseChecker = $this->createMock(LicenseExpirationChecker::class);

    // Setup logger factory to return logger channel.
    $this->loggerFactory
      ->method('get')
      ->with('oafc_license_notifications')
      ->willReturn($this->logger);

    // Create the queue worker.
    $this->queueWorker = new LicenseExpirationNotificationWorker(
      [],
      'oafc_license_expiration_notification',
      ['cron' => ['time' => 60]],
      $this->entityTypeManager,
      $this->mailManager,
      $this->configFactory,
      $this->renderer,
      $this->loggerFactory,
      $this->licenseChecker
    );

    // Mock string translation for the queue worker.
    $string_translation = $this->getStringTranslationStub();
    $this->queueWorker->setStringTranslation($string_translation);
  }

  /**
   * Tests processing a queue item successfully.
   *
   * @covers ::processItem
   */
  public function testProcessItemSuccess() {
    $license_id = 123;
    $notification_days = 7;

    // Create mock license.
    $license = $this->createMockLicense($license_id, 'active', FALSE);

    // Create mock URL object.
    $url = $this->getMockBuilder('Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->getMock();
    $url->method('setAbsolute')->willReturnSelf();
    $url->method('toString')->willReturn('https://example.com/product');

    // Create mock purchased entity.
    $purchased_entity = $this->createMock(ProductVariationInterface::class);
    $purchased_entity->method('label')->willReturn('Premium Membership');
    $purchased_entity->method('toUrl')->willReturn($url);

    $license->method('getPurchasedEntity')->willReturn($purchased_entity);

    // Create mock owner.
    $owner = $this->createMock(UserInterface::class);
    $owner->method('isAnonymous')->willReturn(FALSE);
    $owner->method('getEmail')->willReturn('user@example.com');
    $owner->method('getPreferredLangcode')->willReturn('en');

    $license->method('getOwner')->willReturn($owner);

    // Setup license storage.
    $license_storage = $this->createMock(EntityStorageInterface::class);
    $license_storage
      ->method('load')
      ->with($license_id)
      ->willReturn($license);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('commerce_license')
      ->willReturn($license_storage);

    // Setup config.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')
      ->willReturnMap([
        ['from_email', NULL],
        ['bcc_email', NULL],
        ['log_notifications', TRUE],
      ]);

    $site_config = $this->createMock(ImmutableConfig::class);
    $site_config->method('get')
      ->willReturnMap([
        ['name', 'Test Site'],
        ['mail', 'noreply@example.com'],
      ]);

    $this->configFactory
      ->method('get')
      ->willReturnMap([
        ['oafc_license_notifications.settings', $config],
        ['system.site', $site_config],
      ]);

    // License checker should say notification hasn't been sent.
    $this->licenseChecker
      ->expects($this->once())
      ->method('hasNotificationBeenSent')
      ->with($license, $notification_days)
      ->willReturn(FALSE);

    // Expect notification to be marked as sent.
    $this->licenseChecker
      ->expects($this->once())
      ->method('markNotificationSent')
      ->with($license, $notification_days);

    // Setup renderer.
    $this->renderer
      ->method('executeInRenderContext')
      ->willReturnCallback(function ($context, $callback) {
        return $callback();
      });

    $this->renderer
      ->method('render')
      ->willReturn('<html>Email body</html>');

    // Expect mail to be sent successfully.
    $this->mailManager
      ->expects($this->once())
      ->method('mail')
      ->with(
        'oafc_license_notifications',
        'license_expiration_warning',
        'user@example.com',
        'en',
        $this->callback(function ($params) use ($notification_days) {
          return $params['days_until_expiration'] === $notification_days
            && isset($params['license'])
            && isset($params['owner']);
        })
      )
      ->willReturn(['result' => TRUE]);

    // Logger should log success.
    $this->logger
      ->expects($this->once())
      ->method('info')
      ->with(
        $this->stringContains('Sent expiration notification'),
        $this->arrayHasKey('@license_id')
      );

    // Process the queue item.
    $data = [
      'license_id' => $license_id,
      'notification_days' => $notification_days,
      'expires_time' => time() + (7 * 86400),
    ];

    $this->queueWorker->processItem($data);

    // If we get here without exceptions, the test passes.
    $this->assertTrue(TRUE, 'Queue item processed successfully');
  }

  /**
   * Tests processing when license is not found.
   *
   * @covers ::processItem
   */
  public function testProcessItemLicenseNotFound() {
    $license_id = 999;

    // Setup license storage to return NULL.
    $license_storage = $this->createMock(EntityStorageInterface::class);
    $license_storage
      ->method('load')
      ->with($license_id)
      ->willReturn(NULL);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('commerce_license')
      ->willReturn($license_storage);

    // Logger should log error.
    $this->logger
      ->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('License @license_id not found'),
        ['@license_id' => $license_id]
      );

    // Process the queue item.
    $data = [
      'license_id' => $license_id,
      'notification_days' => 7,
    ];

    $this->queueWorker->processItem($data);
  }

  /**
   * Tests processing when purchased entity is not found.
   *
   * @covers ::processItem
   */
  public function testProcessItemPurchasedEntityNotFound() {
    $license_id = 123;

    $license = $this->createMockLicense($license_id, 'active', FALSE);
    $license->method('getPurchasedEntity')->willReturn(NULL);

    $license_storage = $this->createMock(EntityStorageInterface::class);
    $license_storage
      ->method('load')
      ->with($license_id)
      ->willReturn($license);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('commerce_license')
      ->willReturn($license_storage);

    // Logger should log error.
    $this->logger
      ->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Purchased entity for license @license_id not found'),
        ['@license_id' => $license_id]
      );

    $data = [
      'license_id' => $license_id,
      'notification_days' => 7,
    ];

    $this->queueWorker->processItem($data);
  }

  /**
   * Tests processing when license is not in active state.
   *
   * @covers ::processItem
   */
  public function testProcessItemLicenseNotActive() {
    $license_id = 123;

    $license = $this->createMockLicense($license_id, 'expired', FALSE);

    $purchased_entity = $this->createMock(ProductVariationInterface::class);
    $license->method('getPurchasedEntity')->willReturn($purchased_entity);

    $license_storage = $this->createMock(EntityStorageInterface::class);
    $license_storage
      ->method('load')
      ->with($license_id)
      ->willReturn($license);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('commerce_license')
      ->willReturn($license_storage);

    // Logger should log notice.
    $this->logger
      ->expects($this->once())
      ->method('notice')
      ->with(
        $this->stringContains('License @license_id is no longer active'),
        ['@license_id' => $license_id]
      );

    $data = [
      'license_id' => $license_id,
      'notification_days' => 7,
    ];

    $this->queueWorker->processItem($data);
  }

  /**
   * Tests processing when owner is anonymous.
   *
   * @covers ::processItem
   */
  public function testProcessItemAnonymousOwner() {
    $license_id = 123;

    $license = $this->createMockLicense($license_id, 'active', FALSE);

    $purchased_entity = $this->createMock(ProductVariationInterface::class);
    $license->method('getPurchasedEntity')->willReturn($purchased_entity);

    $owner = $this->createMock(UserInterface::class);
    $owner->method('isAnonymous')->willReturn(TRUE);
    $license->method('getOwner')->willReturn($owner);

    $license_storage = $this->createMock(EntityStorageInterface::class);
    $license_storage
      ->method('load')
      ->with($license_id)
      ->willReturn($license);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('commerce_license')
      ->willReturn($license_storage);

    // Logger should log error.
    $this->logger
      ->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('License @license_id owner not found'),
        ['@license_id' => $license_id]
      );

    $data = [
      'license_id' => $license_id,
      'notification_days' => 7,
    ];

    $this->queueWorker->processItem($data);
  }

  /**
   * Tests processing when notification has already been sent.
   *
   * @covers ::processItem
   */
  public function testProcessItemNotificationAlreadySent() {
    $license_id = 123;
    $notification_days = 7;

    $license = $this->createMockLicense($license_id, 'active', FALSE);

    $purchased_entity = $this->createMock(ProductVariationInterface::class);
    $license->method('getPurchasedEntity')->willReturn($purchased_entity);

    $owner = $this->createMock(UserInterface::class);
    $owner->method('isAnonymous')->willReturn(FALSE);
    $license->method('getOwner')->willReturn($owner);

    $license_storage = $this->createMock(EntityStorageInterface::class);
    $license_storage
      ->method('load')
      ->with($license_id)
      ->willReturn($license);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('commerce_license')
      ->willReturn($license_storage);

    // License checker should say notification has been sent.
    $this->licenseChecker
      ->expects($this->once())
      ->method('hasNotificationBeenSent')
      ->with($license, $notification_days)
      ->willReturn(TRUE);

    // Expect notification NOT to be marked as sent again.
    $this->licenseChecker
      ->expects($this->never())
      ->method('markNotificationSent');

    // Expect mail NOT to be sent.
    $this->mailManager
      ->expects($this->never())
      ->method('mail');

    $data = [
      'license_id' => $license_id,
      'notification_days' => $notification_days,
    ];

    $this->queueWorker->processItem($data);
  }

  /**
   * Tests email sending failure logging.
   *
   * @covers ::processItem
   */
  public function testProcessItemEmailFailure() {
    $license_id = 123;
    $notification_days = 7;

    $license = $this->createMockLicense($license_id, 'active', FALSE);

    // Create mock URL object.
    $url = $this->getMockBuilder('Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->getMock();
    $url->method('setAbsolute')->willReturnSelf();
    $url->method('toString')->willReturn('https://example.com/product');

    $purchased_entity = $this->createMock(ProductVariationInterface::class);
    $purchased_entity->method('label')->willReturn('Premium Membership');
    $purchased_entity->method('toUrl')->willReturn($url);

    $license->method('getPurchasedEntity')->willReturn($purchased_entity);

    $owner = $this->createMock(UserInterface::class);
    $owner->method('isAnonymous')->willReturn(FALSE);
    $owner->method('getEmail')->willReturn('user@example.com');
    $owner->method('getPreferredLangcode')->willReturn('en');

    $license->method('getOwner')->willReturn($owner);

    $license_storage = $this->createMock(EntityStorageInterface::class);
    $license_storage
      ->method('load')
      ->with($license_id)
      ->willReturn($license);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('commerce_license')
      ->willReturn($license_storage);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(NULL);

    $site_config = $this->createMock(ImmutableConfig::class);
    $site_config->method('get')
      ->willReturnMap([
        ['name', 'Test Site'],
        ['mail', 'noreply@example.com'],
      ]);

    $this->configFactory
      ->method('get')
      ->willReturnMap([
        ['oafc_license_notifications.settings', $config],
        ['system.site', $site_config],
      ]);

    $this->licenseChecker
      ->method('hasNotificationBeenSent')
      ->willReturn(FALSE);

    $this->renderer
      ->method('executeInRenderContext')
      ->willReturnCallback(function ($context, $callback) {
        return $callback();
      });

    $this->renderer
      ->method('render')
      ->willReturn('<html>Email body</html>');

    // Mail sending fails.
    $this->mailManager
      ->method('mail')
      ->willReturn(['result' => FALSE]);

    // Logger should log error.
    $this->logger
      ->expects($this->once())
      ->method('error')
      ->with(
        $this->stringContains('Failed to send expiration notification'),
        $this->arrayHasKey('@license_id')
      );

    $data = [
      'license_id' => $license_id,
      'notification_days' => $notification_days,
      'expires_time' => time() + (7 * 86400),
    ];

    $this->queueWorker->processItem($data);
  }

  /**
   * Helper method to create a mock license.
   *
   * @param int $id
   *   The license ID.
   * @param string $state
   *   The license state.
   * @param bool $is_anonymous
   *   Whether the owner is anonymous.
   *
   * @return \Drupal\commerce_license\Entity\LicenseInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mock license.
   */
  protected function createMockLicense($id, $state, $is_anonymous) {
    $license = $this->createMock(LicenseInterface::class);
    $license->method('id')->willReturn($id);
    $license->method('getExpiresTime')->willReturn(time() + (7 * 86400));

    $state_item = $this->createMock(StateItemInterface::class);
    $state_item->method('getId')->willReturn($state);
    $license->method('getState')->willReturn($state_item);

    return $license;
  }

}
