OAFC License Notifications
==========================

This module sends advance notification emails to users before their
Commerce License memberships expire.

Features
--------

-   Configurable notification intervals (e.g., 30 days, 10 days before
expiration)

-   Supports up to 3 notification emails before expiration

-   Uses Drupal's queue system for reliable email delivery

-   Tracks which notifications have been sent to prevent duplicates

-   Configurable email settings (from address, BCC, logging)

-   Professional HTML email templates

-   Runs automatically via cron

Login Redirect Handling
-----------------------

To ensure a smooth user experience, this module intercepts the user login form.

When a user clicks a renewal link in an email and is prompted to log in, the
module detects the original `destination` parameter (e.g., the membership
page) from the URL.

After a successful login, it overrides the default redirect (which usually
goes to the user's dashboard) and sends the user to their intended
destination page.

Installation
------------

1.  Enable the module:

    ```
    drush en oafc_license_notifications -y

    ```

2.  Clear cache:

    ```
    drush cr

    ```

3.  Configure settings at: `/admin/commerce/config/oafc-license-notifications`

Configuration
-------------

Navigate to **Commerce → Configuration → License Notifications** to configure:

### Notification Intervals

-   **First notification**: Days before expiration to send the first email
(default: 30)
-   **Second notification**: Days before expiration to send the second email
    (default: 10)
-   **Third notification**: Optional third notification (default: disabled)

### Email Settings

-   **Enable automatic notifications**: Toggle to enable/disable the
    notification system
-   **From email address**: Custom from address (leave empty for site
    default)
-   **BCC email address**: Optionally send a copy of all notifications to
    this address
-   **Log all notification attempts**: Enable detailed logging to watchdog

How It Works
------------

1.  **Cron runs**: Every cron run, the module checks for licenses approaching
expiration

2.  **Licenses queued**: Active licenses within the notification windows are
    added to a queue

3.  **Queue processing**: The queue worker processes items and sends emails

4.  **Tracking**: Each notification level is tracked on the license to
    prevent duplicate emails

5.  **Renewal Button on Emails**: The renewal button in the emails will take
    the user to the user login page with the redirect destination in the
    url. Upon login, it will immediately redirect them to the membership
    product page.

Technical Details
-----------------

### Extended Entity API This module extends the `commerce_license` entity to
provide clean data management methods:

```
// Clean, type-safe API
$notifications = $license->getDataValue('expiration_notifications', []);
$license->setDataValue('expiration_notifications', [30, 10]);
$license->save();

```

See `EXTENDED_ENTITY.md` for complete API documentation.

### Data Field Storage

Notification tracking is stored in the license entity's `data` field (added by
this module):

```
// The data field stores a serialized array
$data = [
  'expiration_notifications' => [30, 10],
  // Days for which notifications were sent
];

```

The `data` field is a `map` field type (key-value storage) added to the
`commerce_license` entity when this module is installed.

### Queue System

-   **Queue ID**: `oafc_license_expiration_notification`

-   **Processing**: Handled by cron (60 seconds per cron run)

-   **Worker Plugin**: `LicenseExpirationNotificationWorker`

### Email Template

The email template can be overridden in your theme:

```
themes/custom/YOUR_THEME/templates/oafc-license-expiration-notification.html.twig

```

Testing
-------

### Manual Testing

1.  Create a test license that expires in 30 days

2.  Run cron manually:

    ```
    drush cron

    ```

3.  Process the queue:

    ```
    drush queue:run oafc_license_expiration_notification

    ```

4.  Check the logs:

    ```
    drush watchdog:show --filter="oafc_license_notifications"

    ```

### Adjust Dates for Testing

Temporarily modify the configuration to use shorter intervals:

-   First notification: 1 day

-   Second notification: 0 days (same day)

Create a test license expiring tomorrow to trigger notifications.

Troubleshooting
---------------

### Emails not sending

1.  Check if notifications are enabled in the configuration

2.  Verify cron is running: `drush core:cron`

3.  Check the queue: `drush queue:list`

4.  Process the queue manually:
`drush queue:run oafc_license_expiration_notification`

5.  Check watchdog logs: `drush watchdog:show`

### Duplicate emails

The module prevents duplicates by tracking sent notifications. If you're getting
duplicates:

1.  Check if multiple cron jobs are running

2.  Verify the license data field is being saved correctly

### No notifications for specific licenses

Check that the license:

-   Has `state` set to 'active' or 'renewal_in_progress'

-   Has an `expires` timestamp set (not 0)

-   Expires within the configured notification window

-   Hasn't already received this notification level

Dependencies
------------

-   commerce_license

-   advancedqueue (used by commerce_license)

Compatibility
-------------

-   Drupal: 9.x, 10.x

-   Commerce License: 2.x, 3.x
