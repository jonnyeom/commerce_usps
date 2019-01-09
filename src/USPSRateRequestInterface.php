<?php

namespace Drupal\commerce_usps;

use Drupal\commerce_shipping\Entity\ShipmentInterface;

/**
 * The interface for fetching and returning rates using the USPS API.
 *
 * @package Drupal\commerce_usps
 */
interface USPSRateRequestInterface {

  /**
   * Fetch rates for the shipping method.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The commerce shipment.
   *
   * @return array
   *   An array of ShippingRate objects.
   */
  public function getRates(ShipmentInterface $commerce_shipment);

}
