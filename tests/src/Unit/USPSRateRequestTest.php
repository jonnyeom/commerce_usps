<?php

namespace Drupal\Tests\commerce_usps\Unit;

use Drupal\commerce_shipping\ShippingRate;
use Drupal\commerce_usps\USPSShipment;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drupal\commerce_usps\USPSRateRequest;

/**
 * Class USPSRateRequestTest.
 *
 * @group commerce_usps
 * @package Drupal\Tests\commerce_usps\Unit
 */
class USPSRateRequestTest extends USPSUnitTestBase {

  /**
   * The USPS Rate Request class.
   *
   * @var \Drupal\commerce_usps\USPSRateRequest
   */
  protected $rate_request;

  /**
   * The USPS shipment class.
   *
   * @var \Drupal\commerce_usps\USPSShipment
   */
  protected $usps_shipment;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add the services to the config.
    $this->setConfig(['services' => [1, 2, 3, 4, 6, 7]]);

    // Mock all the objects and set the config.
    $event_dispatcher = new EventDispatcher();
    $this->usps_shipment = new USPSShipment($event_dispatcher);
    $this->rate_request = new USPSRateRequest($this->usps_shipment, $event_dispatcher);
    $this->rate_request->setConfig($this->getConfig());
  }

  /**
   * Tests getRates().
   *
   * @throws \Exception
   */
  public function testGetRates() {
    $config = $this->getConfig();
    $shipment = $this->mockShipment();

    // Fetch rates from the USPS api.
    $rates = $this->rate_request->getRates($shipment);

    // Make sure the same number of rates requested
    // is returned.
    $this->assertEquals(count($config['services']), count($rates));

    /** @var \Drupal\commerce_shipping\ShippingRate $rate */
    foreach ($rates as $rate) {
      $this->assertInstanceOf(ShippingRate::class, $rate);
      $this->assertNotEmpty($rate->getAmount()->getNumber());
    }

  }

}
