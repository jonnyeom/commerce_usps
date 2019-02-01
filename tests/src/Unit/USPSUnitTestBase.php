<?php

namespace Drupal\Tests\commerce_usps\Unit;

use Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\physical\Length;
use Drupal\physical\Weight;
use Drupal\profile\Entity\ProfileInterface;
use Ups\Entity\PackagingType;
use CommerceGuys\Addressing\Address;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_shipping\Entity\ShipmentInterface;
use Drupal\commerce_shipping\Plugin\Commerce\PackageType\PackageTypeInterface;
use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Class USPSUnitTestBase.
 *
 * @package Drupal\Tests\commerce_usps\Unit
 */
abstract class USPSUnitTestBase extends UnitTestCase {

  /**
   * Configuration array.
   *
   * @var array
   */
  protected $configuration;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setConfig();
  }

  /**
   * Mocks the configuration array for tests.
   *
   * @param array $config
   *   The shipping method plugin configuration.
   */
  protected function setConfig(array $config = []) {
    $this->configuration = [
      'api_information' => [
        'user_id' => '972BLUEO5743',
        'password' => '972BLUEO5743',
        'mode' => 'live',
      ],
      'default_package_type' => 'usps_small_flat_rate_box',
    ] + $config;
  }

  /**
   * Get the configuration array.
   *
   * @return array
   *   The config.
   */
  public function getConfig() {
    return $this->configuration;
  }

  /**
   * Creates a mock Drupal Commerce shipment entity.
   *
   * @param string $weight_unit
   *   Weight measurement unit.
   * @param string $length_unit
   *   Length measurement unit.
   * @param bool $send_form_usa
   *   Whether the shipment should be sent from USA.
   *
   * @return \Drupal\commerce_shipping\Entity\ShipmentInterface
   *   A mocked commerce shipment object.
   */
  public function mockShipment($weight_unit = 'lb', $length_unit = 'in', $send_form_usa = TRUE) {
    // Mock a Drupal Commerce Order and associated objects.
    $order = $this->prophesize(OrderInterface::class);
    $store = $this->prophesize(StoreInterface::class);

    // Mock the getAddress method to return a US address.
    if ($send_form_usa) {
      $store->getAddress()->willReturn(new Address('US', 'NC', 'Asheville', '', 28806, '', '1025 Brevard Rd'));
    }
    else {
      // Mock the address list to ship to Germany address.
      // To those who are wondering, this is where Drupal Europe 2018 took
      // place.
      $store->getAddress()->willReturn(new Address('DE', '', 'Darmstadt', '', 64283, '', 'Schlossgraben 1'));
    }
    $order->getStore()->willReturn($store->reveal());

    // Mock a Drupal Commerce shipment and associated objects.
    $shipment = $this->prophesize(ShipmentInterface::class);
    $profile = $this->prophesize(ProfileInterface::class);
    $address_list = $this->prophesize(FieldItemListInterface::class);

    // Mock the address list to return a US address.
    $address_list->first()->willReturn(new Address('US', 'CO', 'Morrison', '', 80465, '', '18300 W Alameda Pkwy'));
    $profile->get('address')->willReturn($address_list->reveal());
    $shipment->getShippingProfile()->willReturn($profile->reveal());
    $shipment->getOrder()->willReturn($order->reveal());

    // Mock a package type including dimensions and remote id.
    $package_type = $this->prophesize(PackageTypeInterface::class);
    $package_type->getHeight()->willReturn((new Length(10, 'in'))->convert($length_unit));
    $package_type->getLength()->willReturn((new Length(10, 'in'))->convert($length_unit));
    $package_type->getWidth()->willReturn((new Length(3, 'in'))->convert($length_unit));
    $package_type->getRemoteId()->willReturn(PackagingType::PT_UNKNOWN);

    // Mock the shipments weight and package type.
    $shipment->getWeight()->willReturn((new Weight(10, 'lb'))->convert($weight_unit));
    $shipment->getPackageType()->willReturn($package_type->reveal());

    // Return the mocked shipment object.
    return $shipment->reveal();
  }

  /**
   * Mocks a shipping method.
   *
   * @return \Drupal\commerce_shipping\Plugin\Commerce\ShippingMethod\ShippingMethodInterface
   *   The mocked shipping method.
   */
  public function mockShippingMethod() {
    $shipping_method = $this->prophesize(ShippingMethodInterface::class);
    $package_type = $this->prophesize(PackageTypeInterface::class);
    $package_type->getHeight()->willReturn(new Length(10, 'in'));
    $package_type->getLength()->willReturn(new Length(10, 'in'));
    $package_type->getWidth()->willReturn(new Length(3, 'in'));
    $package_type->getWeight()->willReturn(new Weight(10, 'lb'));
    $package_type->getRemoteId()->willReturn('custom');
    $shipping_method->getDefaultPackageType()->willReturn($package_type);
    return $shipping_method->reveal();
  }

}
