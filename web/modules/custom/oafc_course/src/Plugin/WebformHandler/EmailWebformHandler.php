<?php

namespace Drupal\oafc_course\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformSelectOther;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler as DefaultEmailWebformHandler;

/**
 * Overrides webform's email handler to provide new message templates.
 */
final class EmailWebformHandler extends DefaultEmailWebformHandler {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (empty($this->configuration['body']) || $this->configuration['body'] === '_default') {
      $this->configuration['body'] = WebformSelectOther::OTHER_OPTION;
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBodyDefaultValues($format = NULL) {
    // Override the HTML body option with our template.
    if ($format === NULL) {
      $formats = parent::getBodyDefaultValues($format);
      $formats['html'] = static::template();
      return $formats;
    }
    if ($format === 'html') {
      return static::template();
    }
    return parent::getBodyDefaultValues($format);
  }

  /**
   * Get a template for custom message bodies.
   *
   * @return string
   *   An HTML template for event emails.
   */
  private static function template() {
    return <<<EOF
<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;"><img src="/themes/custom/oafc/gfx/email/sample-event-header-banner.jpg" alt="Event Header Banner Image" /></p>
<br />

<h1 style="margin: 0 0 10px 0;padding: 0;font-family: Arial, Helvetica, sans-serif;font-size: 26px;font-weight: bold;line-height: 1.2;color: #333;">Primary Header, Sample H1</h1>

<h2 style="margin: 0 0 10px 0;padding: 0;font-family: Arial, Helvetica, sans-serif;font-size: 20px;font-weight: bold;line-height: 1.2;color: #c52423;">Thank you for registering for this event! H2</h2>

<h3 style="margin: 0 0 10px 0;padding: 0;font-family: Arial, Helvetica, sans-serif;font-size: 16px;font-weight: bold;line-height: 1.2;color: #333;">Your Sales Receipt/Invoice is attached, Sample H3</h3>

<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;">Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.</p>

<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;"><strong>Bolded Text:</strong> Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse <a href="#" style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #de2827;text-decoration: none;">text link</a> molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.</p>

<h3 style="margin: 0 0 10px 0;padding: 0;font-family: Arial, Helvetica, sans-serif;font-size: 16px;font-weight: bold;line-height: 1.2;color: #333;">You have registered for the following workshops:</h3>

<table class="table--email table--workshop" style="margin: 0 0 18px;padding: 0;border: 0;width: 100%;border-collapse: collapse;">
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #fff;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #990000;border-color: #990000;">Column 1</th>
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #fff;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #990000;border-color: #990000;">Column 2</th>
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #fff;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #990000;border-color: #990000;">Column 3</th>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Lorem ipsum dolor</td>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Interdum et malesuada</td>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Lorem ipsum dolor</td>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Interdum et malesuada</td>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Lorem ipsum dolor</td>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Interdum et malesuada</td>
  </tr>
</table>

<h2 style="margin: 0 0 10px 0;padding: 0;font-family: Arial, Helvetica, sans-serif;font-size: 20px;font-weight: bold;line-height: 1.2;color: #c52423;">Thank you for registering for this event! H2</h2>

<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;">Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.</p>

<ul style="margin-top: 0;margin-bottom: 18px;">
  <li style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;">Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit.</li>
  <li style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</li>
  <li style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;">Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit.</li>
</ul>

<h3 style="margin: 0 0 10px 0;padding: 0;font-family: Arial, Helvetica, sans-serif;font-size: 16px;font-weight: bold;line-height: 1.2;color: #333;">Sample Link List</h3>

<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;">Lorem ipsum dolor sit amet, consectetuer adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat.</p>

<ul style="margin-top: 0;margin-bottom: 18px;">
  <li style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;"><a href="http://www.web-address.com/information.html" style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #de2827;text-decoration: none;">www.web-address.com/information.html</a></li>
  <li style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;"><a href="http://www.web-address.com/information.html" style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #de2827;text-decoration: none;">www.web-address.com/information.html</a></li>
  <li style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;"><a href="http://www.web-address.com/information.html" style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #de2827;text-decoration: none;">www.web-address.com/information.html</a></li>
</ul>

<table class="table--email table--section no-bottom-margin" style="margin: 0 0 18px;padding: 0;border: 0;width: 100%;border-collapse: collapse;margin-bottom: 0;">
  <tr style="margin: 0;padding: 0;border: 0;">
    <th colspan="2" style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #fff;text-align: left;border: 1px solid #ccc;font-weight: normal;background: #666;border-color: #666;">
      <strong>Section Title</strong><br />
      Date, Time - Second Line, Etc<br />
      Lorem ipsum dolor sit amet, consectetuer adipiscing elit sed nonummy
    </th>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row One</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Adipiscing elit sed diam nonummy nibh euismod tincidunt ut laoreet dolore</td>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row Two</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Adipiscing elit sed diam nonummy nibh euismod tincidunt ut laoreet dolore</td>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row Three</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Adipiscing elit sed diam nonummy nibh euismod tincidunt ut laoreet dolore</td>
  </tr>
</table>

<table class="table--email table--section no-bottom-margin" style="margin: 0 0 18px;padding: 0;border: 0;width: 100%;border-collapse: collapse;margin-bottom: 0;">
  <tr style="margin: 0;padding: 0;border: 0;">
    <th colspan="2" style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #fff;text-align: left;border: 1px solid #ccc;font-weight: normal;background: #666;border-color: #666;">
      <strong>Section Title</strong><br />
      Date, Time - Second Line, Etc<br />
      Lorem ipsum dolor sit amet, consectetuer adipiscing elit sed nonummy
    </th>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row One</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Adipiscing elit sed diam nonummy nibh euismod tincidunt ut laoreet dolore</td>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row Two</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Adipiscing elit sed diam nonummy nibh euismod tincidunt ut laoreet dolore</td>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row Three</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Adipiscing elit sed diam nonummy nibh euismod tincidunt ut laoreet dolore</td>
  </tr>
</table>

<table class="table--email table--section" style="margin: 0 0 18px;padding: 0;border: 0;width: 100%;border-collapse: collapse;">
  <tr style="margin: 0;padding: 0;border: 0;">
    <th colspan="2" style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #fff;text-align: left;border: 1px solid #ccc;font-weight: normal;background: #666;border-color: #666;">
      <strong>Section Title</strong><br />
      Date, Time - Second Line, Etc<br />
      Lorem ipsum dolor sit amet, consectetuer adipiscing elit sed nonummy
    </th>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row One</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Adipiscing elit sed diam nonummy nibh euismod tincidunt ut laoreet dolore</td>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row Two</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Adipiscing elit sed diam nonummy nibh euismod tincidunt ut laoreet dolore</td>
  </tr>
  <tr style="margin: 0;padding: 0;border: 0;">
    <th style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;font-weight: bold;background: #efefef;">Row Three</th>
    <td style="margin: 0;padding: 5px 10px;font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;text-align: left;border: 1px solid #ccc;">Adipiscing elit sed diam nonummy nibh euismod tincidunt ut laoreet dolore</td>
  </tr>
</table>

<h3 style="margin: 0 0 10px 0;padding: 0;font-family: Arial, Helvetica, sans-serif;font-size: 16px;font-weight: bold;line-height: 1.2;color: #333;">Thanks for Registering!</h3>

<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;">We look forward to seeing you at the event! Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit <a href="#" style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #de2827;text-decoration: none;">text link</a> in vulputate velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto odio dignissim qui blandit praesent luptatum zzril delenit augue duis dolore te feugait nulla facilisi.</p>

<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;"><img src="/themes/custom/oafc/gfx/email/sample-content-area.jpg" alt="Content Area - Sample Graphic" /></p>

<hr size="1" class="line-break" style="clear: both;margin: 30px 0;padding: 0;width: 100%;height: 1px;color: #ccc;background: #ccc;border: 0;" />

<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;">
  Jonathan McLastname<br />
  Administrative Assistant
</p>

<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;">
  Ontario Association of Fire Chiefs<br />
  1234 Main Street, Unit 101<br />
  Toronto, Ontario  L0L 0L0
</p>

<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;">
  Tel: 905-555-2222 x225<br />
  Fax: 905-555-3333<br />
  Toll Free: 1-800-555-4444
</p>

<p style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #666;margin: 0 0 18px;padding: 0;">
  <a href="mailto:jonathan.mclastname@oafc.on.ca" style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #de2827;text-decoration: none;">jonathan.mclastname@oafc.on.ca</a><br />
  <a href="http://www.oafc.on.ca" style="font-family: Arial, Helvetica, sans-serif;font-size: 14px;color: #de2827;text-decoration: none;">www.oafc.on.ca</a>
</p>
EOF;
  }

}
