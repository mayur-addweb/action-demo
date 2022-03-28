<?php

namespace Drupal\Tests\am_net_cpe\Unit;

use Drupal\am_net_cpe\EventHelper as Helper;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the AM.net EventHelper.
 *
 * @coversDefaultClass \Drupal\am_net_cpe\EventHelper
 *
 * @group am_net_cpe
 */
class EventHelperTest extends UnitTestCase {

  /**
   * Tests converting AM.net time values into hour and minute parts.
   *
   * ::covers convertAmNetTimeParts.
   */
  public function testSessionTimeParts() {
    $in = '03:33am';
    $out = ['hour' => 3, 'minute' => 33];
    $this->assertEquals($out, Helper::convertAmNetTimeParts($in));
    $in = '5:00 AM ';
    $out = ['hour' => 5, 'minute' => 0];
    $this->assertEquals($out, Helper::convertAmNetTimeParts($in));
    $in = ' 1:23pm';
    $out = ['hour' => 13, 'minute' => 23];
    $this->assertEquals($out, Helper::convertAmNetTimeParts($in));
  }

  /**
   * Tests session timeslot key formatting.
   *
   * ::covers getAmNetSessionTimeslotKey.
   */
  public function testSessionGroupKey() {
    $session = [
      'SessionCode' => '002',
      'ConcurrentSesssions' => ['001', '003'],
    ];
    $session_code = Helper::getAmNetSessionTimeslotKey($session);
    $this->assertEquals('001:002:003', $session_code);
  }

}
