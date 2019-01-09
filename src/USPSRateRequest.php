<?php

namespace Drupal\commerce_usps;

use Drupal\commerce_price\Price;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_shipping\ShippingService;
use USPS\Rate;
use USPS\ServiceDeliveryCalculator;

/**
 * Class USPSRateRequest.
 *
 * @package Drupal\commerce_usps
 */
class USPSRateRequest extends USPSRateRequestBase implements USPSRateRequestInterface {

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
    if (!empty($response['RateV4Response']['Package']['Postage'])) {
      foreach ($response['RateV4Response']['Package']['Postage'] as $rate) {
        $price = $rate['Rate'];
        $service_code = $rate['@attributes']['CLASSID'];
        $service_name = $this->cleanServiceName($rate['MailService']);

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
   * Checks the delivery date of a USPS shipment.
   *
   * @return array
   *   The delivery rate response.
   */
  public function checkDeliveryDate() {
    $to_address = $this->commerceShipment->getShippingProfile()
      ->get('address');
    $from_address = $this->commerceShipment->getOrder()
      ->getStore()
      ->getAddress();

    // Initiate and set the username provided from usps.
    $delivery = new ServiceDeliveryCalculator($this->configuration['api_information']['user_id']);
    // Add the zip code we want to lookup the city and state.
    $delivery->addRoute(3, $from_address->getPostalCode(), $to_address->postal_code);
    // Perform the call and print out the results.
    $delivery->getServiceDeliveryCalculation();

    return $delivery->getArrayResponse();
  }

  /**
   * Initialize the rate request object needed for the USPS API.
   */
  protected function initRequest() {
    $this->uspsRequest = new Rate(
      $this->configuration['api_information']['user_id']
    );
    $this->setMode();

    // Add each package to the request.
    foreach ($this->getPackages() as $package) {
      $this->uspsRequest->addPackage($package);
    }
  }

  /**
   * Utility function to translate service labels.
   *
   * @param string $serviceCode
   *   The service code.
   *
   * @return string
   *   The translated service code.
   */
  protected function translateServiceLables($serviceCode) {
    $label = '';
    if (strtolower($serviceCode) == 'parcel') {
      $label = 'ground';
    }

    return $label;
  }

  /**
   * Utility function to validate a USA zip code.
   *
   * @param string $zip_code
   *   The zip code.
   *
   * @return bool
   *   Returns TRUE if the zip code was validated.
   */
  protected function validateUsaZip($zip_code) {
    return preg_match("/^([0-9]{5})(-[0-9]{4})?$/i", $zip_code);
  }

}
