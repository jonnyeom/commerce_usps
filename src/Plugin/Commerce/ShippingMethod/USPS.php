<?php

namespace Drupal\commerce_usps\Plugin\Commerce\ShippingMethod;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\PackageTypeManagerInterface;
use Drupal\commerce_usps\USPSRateRequestInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides the USPS shipping method.
 *
 * @CommerceShippingMethod(
 *  id = "usps",
 *  label = @Translation("USPS"),
 *  services = {
 *    "_1" = @translation("Priority Mail 2-Day"),
 *    "_2" = @translation("Priority Mail Express 2-Day Hold For Pickup"),
 *    "_3" = @translation("Priority Mail Express 2-Day"),
 *    "_4" = @translation("USPS Retail Ground"),
 *    "_6" = @translation("Media Mail Parcel"),
 *    "_7" = @translation("Library Mail Parcel"),
 *    "_13" = @translation("Priority Mail Express 2-Day Flat Rate Envelope"),
 *    "_16" = @translation("Priority Mail 2-Day Flat Rate Envelope"),
 *    "_17" = @translation("Priority Mail 2-Day Medium Flat Rate Box"),
 *    "_22" = @translation("Priority Mail 2-Day Large Flat Rate Box"),
 *    "_27" = @translation("Priority Mail Express 2-Day Flat Rate Envelope Hold For Pickup"),
 *    "_28" = @translation("Priority Mail 2-Day Small Flat Rate Box"),
 *    "_29" = @translation("Priority Mail 1-Day Padded Flat Rate Envelope"),
 *    "_30" = @translation("Priority Mail Express 2-Day Legal Flat Rate Envelope"),
 *    "_31" = @translation("Priority Mail Express 2-Day Legal Flat Rate Envelope Hold For Pickup"),
 *    "_38" = @translation("Priority Mail 2-Day Gift Card Flat Rate Envelope"),
 *    "_40" = @translation("Priority Mail 2-Day Window Flat Rate Envelope"),
 *    "_42" = @translation("Priority Mail 1-Day Small Flat Rate Envelope"),
 *    "_44" = @translation("Priority Mail 2-Day Legal Flat Rate Envelope"),
 *    "_62" = @translation("Priority Mail Express 2-Day Padded Flat Rate Envelope"),
 *    "_63" = @translation("Priority Mail Express 2-Day Padded Flat Rate Envelope Hold For Pickup"),
 *  }
 * )
 */
class USPS extends USPSBase {

  /**
   * Constructs a new ShippingMethodBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_shipping\PackageTypeManagerInterface $package_type_manager
   *   The package type manager.
   * @param \Drupal\commerce_usps\USPSRateRequestInterface $usps_rate_request
   *   The rate request service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PackageTypeManagerInterface $package_type_manager, USPSRateRequestInterface $usps_rate_request) {
    // Rewrite the service keys to be integers.
    $plugin_definition = $this->preparePluginDefinition($plugin_definition);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $package_type_manager, $usps_rate_request);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateRates(ShipmentInterface $shipment) {
    // Only attempt to collect rates if an address exists on the shipment.
    if ($shipment->getShippingProfile()->get('address')->isEmpty()) {
      return [];
    }

    return $this->uspsRateService->getRates($shipment);
  }

  /**
   * Prepares the service array keys to support integer values.
   *
   * @param array $plugin_definition
   *   The plugin definition provided to the class.
   *
   * @return array
   *   The prepared plugin definition.
   */
  private function preparePluginDefinition(array $plugin_definition) {
    // Cache and unset the parsed plugin definitions for services.
    $services = $plugin_definition['services'];
    unset($plugin_definition['services']);

    // Loop over each service definition and redefine them with
    // integer keys that match the UPS API.
    // TODO: Remove once core issue has been addressed.
    // See: https://www.drupal.org/node/2904467 for more information.
    foreach ($services as $key => $service) {
      // Remove the "_" from the service key.
      $key_trimmed = str_replace('_', '', $key);
      $plugin_definition['services'][$key_trimmed] = $service;
    }

    // Sort the options alphabetically.
    uasort($plugin_definition['services'], function (TranslatableMarkup $a, TranslatableMarkup $b) {
      return $a->getUntranslatedString() < $b->getUntranslatedString() ? -1 : 1;
    });

    return $plugin_definition;
  }

}
