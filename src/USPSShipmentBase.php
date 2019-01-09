<?php

namespace Drupal\commerce_usps;

use Drupal\commerce_shipping\Entity\ShipmentInterface;
use USPS\RatePackage;

/**
 * Class that sets the shipment details needed for the USPS request.
 *
 * @package Drupal\commerce_usps
 */
class USPSShipmentBase implements USPSShipmentInterface {

  /**
   * The commerce shipment entity.
   *
   * @var \Drupal\commerce_shipping\Entity\ShipmentInterface
   */
  protected $commerceShipment;

  /**
   * The USPS rate package entity.
   *
   * @var \USPS\RatePackage
   */
  protected $uspsPackage;

  /**
   * Get the USPS RatePackage object.
   *
   * @param \Drupal\commerce_shipping\Entity\ShipmentInterface $commerce_shipment
   *   The commerce shipment entity.
   *
   * @return \USPS\RatePackage
   *   The RatePackage object.
   */
  public function getPackage(ShipmentInterface $commerce_shipment) {
    $this->commerceShipment = $commerce_shipment;
    $this->uspsPackage = new RatePackage();
    return $this->uspsPackage;
  }

  /**
   * Sets the package dimensions.
   */
  public function setDimensions() {
    $package_type = $this->getPackageType();
    if (!empty($package_type)) {
      $length = ceil($package_type->getLength()->convert('in')->getNumber());
      $width = ceil($package_type->getWidth()->convert('in')->getNumber());
      $height = ceil($package_type->getHeight()->convert('in')->getNumber());
      $size = $length > 12 || $width > 12 || $height > 12 ? 'LARGE' : 'REGULAR';
      $this->uspsPackage->setField('Size', $size);
      $this->uspsPackage->setField('Width', $width);
      $this->uspsPackage->setField('Length', $length);
      $this->uspsPackage->setField('Height', $height);
      $this->uspsPackage->setField('Girth', 0);
    }
  }

  /**
   * Sets the package weight.
   */
  protected function setWeight() {
    $weight = $this->commerceShipment->getWeight();

    if ($weight->getNumber() > 0) {
      $ounces = $weight->convert('oz')->getNumber();

      $this->uspsPackage->setPounds(floor($ounces / 16));
      $this->uspsPackage->setOunces($ounces % 16);
    }
  }

  /**
   * Determine the package type for the shipment.
   *
   * @return \Drupal\commerce_shipping\Plugin\Commerce\PackageType\PackageTypeInterface
   *   The package type.
   */
  protected function getPackageType() {
    // If the package is set on the shipment, use that.
    if (!empty($this->commerceShipment->getPackageType())) {
      return $this->commerceShipment->getPackageType();
    }
    // TODO return default shipment for shipping method.
  }

}
