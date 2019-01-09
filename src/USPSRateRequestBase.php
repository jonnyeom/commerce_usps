<?php

namespace Drupal\commerce_usps;

/**
 * Class USPSRateRequest.
 *
 * @package Drupal\commerce_usps
 */
class USPSRateRequestBase extends USPSRequest {

  /**
   * The commerce shipment entity.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $commerceShipment;

  /**
   * The configuration array from a CommerceShippingMethod.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The USPS rate request API.
   *
   * @var \USPS\Rate
   */
  protected $uspsRequest;

  /**
   * The USPS Shipment object.
   *
   * @var \Drupal\commerce_usps\USPSShipmentInterface
   */
  protected $uspsShipment;

  /**
   * USPSRateRequest constructor.
   *
   * @param \Drupal\commerce_usps\USPSShipmentInterface $usps_shipment
   *   The USPS shipment object.
   */
  public function __construct(USPSShipmentInterface $usps_shipment) {
    $this->uspsShipment = $usps_shipment;
  }

  /**
   * Set the mode to either test/live.
   */
  protected function setMode() {
    $this->uspsRequest->setTestMode($this->isTestMode());
  }

  /**
   * Get an array of USPS packages.
   *
   * @return array
   *   An array of USPS packages.
   */
  protected function getPackages() {
    // @todo: Support multiple packages.
    return [$this->uspsShipment->getPackage($this->commerceShipment)];
  }

  /**
   * Utility function to clean the USPS service name.
   *
   * @param string $service
   *   The service id.
   *
   * @return string
   *   The cleaned up service id.
   */
  protected function cleanServiceName($service) {
    // Remove the html encoded trademark markup since it's
    // not supported in radio labels.
    return str_replace('&lt;sup&gt;&#8482;&lt;/sup&gt;', '', $service);
  }

}
