<?php

namespace Drupal\Tests\am_net_cpe\Kernel;

use Drupal\am_net_cpe\EventHelper as Helper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for the AM.net EventHelper.
 *
 * @coversDefaultClass \Drupal\am_net_cpe\EventHelper
 *
 * @group am_net_cpe
 */
class EventHelperTest extends KernelTestBase {

  /**
   * Tests getting Drupal event date/time ranges.
   *
   * :covers getDrupalEventDateTimeRanges.
   */
  public function testAmNetEventDatesTimesConversions() {
    $timezone = 'America/New_York';
    $event = [
      'BeginDate' => '2017-05-15T00:00:00',
      'BeginTimeDay1' => '8:00am',
      'BeginTimeDay2' => '7:00am',
      'BeginTimeDay3' => '6:00am',
      'EndDate' => '2017-05-17T00:00:00',
      'EndTimeDay1' => '7:10pm',
      'EndTimeDay2' => '4:30pm',
      'EndTimeDay3' => '3:30pm',
    ];
    $converted_dates_times = Helper::getDrupalEventDateTimeRanges($event, $timezone);
    $expected = [
      [
        'start_date' => new DrupalDateTime('2017-05-15T08:00:00', $timezone),
        'end_date' => new DrupalDateTime('2017-05-15T19:10:00', $timezone),
      ],
      [
        'start_date' => new DrupalDateTime('2017-05-16T07:00:00', $timezone),
        'end_date' => new DrupalDateTime('2017-05-16T16:30:00', $timezone),
      ],
      [
        'start_date' => new DrupalDateTime('2017-05-17T06:00:00', $timezone),
        'end_date' => new DrupalDateTime('2017-05-17T15:30:00', $timezone),
      ],
    ];
    $this->assertEquals($expected, $converted_dates_times);
  }

}
