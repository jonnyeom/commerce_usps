<?php

namespace Drupal\commerce_usps;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use USPS\Rate;

/**
 * Class USPSRateRequest.
 *
 * @package Drupal\commerce_usps
 */
class USPSRateRequestInternational extends USPSRateRequestBase implements USPSRateRequestInterface {

  /**
   * Fetch rates from the USPS API.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The commerce shipment.
   *
   * @throws \Exception
   *   Exception when required properties are missing.
   *
   * @return array
   *   An array of ShippingRate objects.
   */
  public function getRates(ShipmentInterface $commerce_shipment) {
    // Validate a commerce shipment has been provided.
    if (empty($commerce_shipment)) {
      throw new \Exception('Shipment not provided');
    }

    $rates = [];

    // Set the necessary info needed for the request.
    $this->commerceShipment = $commerce_shipment;
    $this->initRequest();

    // Fetch the rates.
    $this->uspsRequest->getRate();
    $response = $this->uspsRequest->getArrayResponse();

    // Parse the rate response and create shipping rates array.
    if (!empty($response['IntlRateV2Response']['Package']['Service'])) {
      foreach ($response['IntlRateV2Response']['Package']['Service'] as $service) {
        $price = $service['Postage'];
        $service_code = $service['@attributes']['ID'];
        $service_name = $this->cleanServiceName($service['SvcDescription']);

        // Only add the rate if this service is enabled.
        if (!in_array($service_code, $this->configuration['services'])) {
          continue;
        }

        $shipping_service = new ShippingService(
          $service_code,
          $service_name
        );

        $rates[] = new ShippingRate(
          $service_code,
          $shipping_service,
          new Price($price, 'USD')
        );
      }
    }

    return $rates;
  }

  /**
   * Initialize the rate request object needed for the USPS API.
   */
  protected function initRequest() {
    $this->uspsRequest = new Rate(
      $this->configuration['api_information']['user_id']
    );
    $this->uspsRequest->setInternationalCall(TRUE);
    $this->uspsRequest->addExtraOption('Revision', 2);
    $this->setMode();

    // Add each package to the request.
    // Todo: IntlRateV2 is limited to 25 packages per txn.
    foreach ($this->getPackages() as $package) {
      $this->uspsRequest->addPackage($package);
    }
  }

}
