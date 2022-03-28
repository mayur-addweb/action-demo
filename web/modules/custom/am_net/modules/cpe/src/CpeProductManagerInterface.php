<?php

namespace Drupal\am_net_cpe;

use Drupal\vscpa_commerce\Entity\EventSessionInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * An interface defining an AM.net CPE product manager.
 *
 * @package Drupal\am_net_cpe
 */
interface CpeProductManagerInterface {

  /**
   * Syncs an AM.net event to a Drupal product and related entities.
   *
   * @param string $event_code
   *   The AM.net event code.
   * @param string $event_year
   *   The AM.net event year (two digits).
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The new event product.
   *
   * @throws \Drupal\am_net\AmNetRecordExcludedException
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   * @throws \Exception
   */
  public function syncAmNetCpeEventProduct($event_code, $event_year);

  /**
   * Syncs an AM.net product to a Drupal product and related entities.
   *
   * @param string $code
   *   The AM.net product code.
   * @param bool $allow_excluded
   *   TRUE if products marked excluded should still be synced.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The new Self-study CPE product.
   *
   * @throws \Drupal\am_net\AmNetRecordExcludedException
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   * @throws \Exception
   */
  public function syncAmNetCpeSelfStudyProduct($code, $allow_excluded = FALSE);

  /**
   * Gets a Drupal CPE event product for the given event code and year.
   *
   * @param string $event_code
   *   The AM.net event code.
   * @param string $event_year
   *   The AM.net event year (two digits).
   * @param bool $try_sync
   *   TRUE (default) if the event should try to be synced if it is not found.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The product entity, or NULL if not found.
   */
  public function getDrupalCpeEventProduct($event_code, $event_year, $try_sync = TRUE);

  /**
   * Gets a Drupal Self-study CPE product for the given product code.
   *
   * @param string $product_code
   *   The AM.net product code.
   * @param bool $try_sync
   *   TRUE (default) if the event should try to be synced if it is not found.
   * @param bool $allow_excluded
   *   TRUE if excluded products should be allowed to be synced.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The product entity, or NULL if not found.
   *
   * @throws \Drupal\am_net\AmNetRecordExcludedException
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getDrupalCpeSelfStudyProduct($product_code, $try_sync = TRUE, $allow_excluded = FALSE);

  /**
   * Checks if a timeslot group contains a given timeslot.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $timeslot_group
   *   The timeslot group paragraph.
   * @param \Drupal\paragraphs\ParagraphInterface $timeslot
   *   The timeslot paragraph.
   *
   * @return bool
   *   TRUE if the timeslot group contains the timeslot.
   */
  public function timeslotGroupHasTimeslot(ParagraphInterface $timeslot_group, ParagraphInterface $timeslot);

  /**
   * Checks if the given event product contains the given timeslot group.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The CPE Event product.
   * @param \Drupal\paragraphs\ParagraphInterface $timeslot_group
   *   The timeslot group paragraph.
   *
   * @return bool
   *   TRUE if the product contains the timeslot group.
   */
  public function eventHasTimeslotGroup(ProductInterface $product, ParagraphInterface $timeslot_group);

  /**
   * Gets the CPE course levels from AM.net.
   *
   * @return array
   *   An array of AM.net CPE course levels, with the following keys:
   *     - 'Code': string 'I', 'U', etc.
   *     - 'Description': string 'Intermediate', 'Update', etc.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetCourseLevels();

  /**
   * Gets the CPE credit categories from AM.net.
   *
   * @return array
   *   An array of AM.net CPE credit categories, with the following keys:
   *     - 'Code': string 'AD', 'AR', etc.
   *     - 'Description': string 'Auditing', 'Business Law', etc.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetCreditCategories();

  /**
   * Gets the City / Area list from AM.net.
   *
   * @return array
   *   An array of AM.net City or Area items, with the following keys:
   *     - 'Code': string 'MC', 'MR', etc.
   *     - 'Description': string 'McClean', 'Martinsville', etc.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetCityAreas();

  /**
   * Gets the formats referenced by AM.net Self-study CPE products.
   *
   * @return array
   *   An array of event product types, with the following keys:
   *     - 'Code': string 'WB', 'WE', 'ON', 'DO' etc.
   *     - 'Description': string 'Webinar', 'Webcast', 'Online', 'Download' etc.
   */
  public function getAmNetCpeFormats();

  /**
   * Gets the event product types that reference AM.net Events and Products.
   *
   * @return array
   *   An array of event product types, with the following keys:
   *     - 'Code': string 'C', 'S', 'O', 'W' etc.
   *     - 'Description': string 'Conference', 'Course', etc.
   */
  public function getAmNetCpeTypes();

  /**
   * Gets the list of 'Field of Interest' items in AM.net.
   *
   * @return array
   *   An array of AM.net Field of Interest items, with the following keys:
   *     - 'Code': string 'AD', 'AR', etc.
   *     - 'Description': string 'Auditing', 'Business Law', etc.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetFieldsOfInterest();

  /**
   * Gets the list of 'Field of Study' items in AM.net.
   *
   * @return array
   *   An array of AM.net Field of Study items, with the following keys:
   *     - 'Code': string 'AC', 'AU', etc.
   *     - 'Description': string 'Accounting', 'Auditing', etc.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetFieldsOfStudy();

  /**
   * Gets an event record from AM.net.
   *
   * @param string $event_code
   *   The AM.net event code.
   * @param string $event_year
   *   The AM.net event year (two digits).
   *
   * @return array|null
   *   An AM.net event record, or NULL if not found.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetEvent($event_code, $event_year);

  /**
   * Gets a list of AM.net events.
   *
   * @param string $since
   *   A date from which to get all events ('yyyy-mm-dd' format).
   *
   * @return array|null
   *   An array of AM.net event records, or NULL if none found.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetEvents($since = '2015-01-01');

  /**
   * Gets a list of Drupal events.
   *
   * @return array|null
   *   An array of Drupal event records, or NULL if none found.
   */
  public function getDrupalEvents();

  /**
   * Gets a product record from AM.net.
   *
   * @param string $product_code
   *   The AM.net product code.
   *
   * @return array|null
   *   An AM.net event record, or NULL if not found.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetProduct($product_code);

  /**
   * Gets a list of AM.net product codes.
   *
   * @return array|null
   *   An array of AM.net product codes, or NULL if none found.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetProductCodes();

  /**
   * Gets the list of Event location ('City or Area') items in AM.net.
   *
   * Relates to AM.net Events' 'FacilityLocationFirmCode' property.
   *
   * @return array
   *   An array of AM.net Event 'City or Area' items, with the following keys:
   *     - 'Code': string 'AR', 'OL', etc.
   *     - 'Description': string 'Arlington', 'Online', etc.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getAmNetEventLocations();

  /**
   * Get the Credit Type taxonomy term for an AM.net credit category code.
   *
   * @param string $credit_category_code
   *   The AM.net credit category code.
   * @param bool $sync
   *   TRUE if terms should be synced and this operation should try again,
   *   FALSE if this operation should fail without re-syncing terms.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Taxonomy term for the given category code, if one exists.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getDrupalCreditType($credit_category_code, $sync = TRUE);

  /**
   * Get the Course Level taxonomy term for an AM.net course level code.
   *
   * @param string $course_level_code
   *   The AM.net course level code.
   * @param bool $sync
   *   TRUE if terms should be synced and this operation should try again,
   *   FALSE if this operation should fail without re-syncing terms.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Taxonomy term for the given course level, if one exists.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getDrupalCourseLevel($course_level_code, $sync = TRUE);

  /**
   * Get the City or Area term for a given Event or event type Product.
   *
   * @param array $event
   *   An AM.net event record.
   * @param array|null $product
   *   An AM.net product record.
   * @param bool $sync
   *   TRUE if terms should be synced and this operation should try again,
   *   FALSE if this operation should fail without re-syncing terms.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The 'City or Area' taxonomy term for the event.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getDrupalEventCityArea(array $event, $product = NULL, $sync = TRUE);

  /**
   * Gets the Event Type taxonomy term for an AM.net CPE event record.
   *
   * @param array $am_net_record
   *   The AM.net Event or event type Product record.
   * @param bool $product
   *   TRUE if this is an AM.net Product record.
   * @param bool $sync
   *   TRUE if terms should be synced and this operation should try again,
   *   FALSE if this operation should fail without re-syncing terms.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Taxonomy term for the given event type code, if one exists.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getDrupalCpeType(array $am_net_record, $product = FALSE, $sync = TRUE);

  /**
   * Gets the CPE Format taxonomy term for an AM.net Self-study CPE record.
   *
   * @param array $am_net_record
   *   The AM.net CPE Self-study product record.
   * @param bool $sync
   *   TRUE if terms should be synced and this operation should try again,
   *   FALSE if this operation should fail without re-syncing terms.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Taxonomy term for the given CPE format code, if one exists.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getDrupalCpeFormat(array $am_net_record, $sync = TRUE);

  /**
   * Gets the Vendor (Firm) taxonomy terms.
   *
   * @param array $event
   *   An AM.net event record.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of Vendor (Firm) taxonomy terms for the given event.
   */
  public function getDrupalEventVendorFieldItems(array $event);

  /**
   * Gets the Field of Interest taxonomy terms.
   *
   * @param array $event
   *   An AM.net event record.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of Field of Interest taxonomy terms for the given event.
   */
  public function getDrupalFieldOfInterestFieldItems(array $event);

  /**
   * Gets the Field of Study taxonomy terms.
   *
   * @param array $event
   *   An AM.net event record.
   *
   * @return \Drupal\taxonomy\TermInterface[]
   *   An array of Field of Study taxonomy terms for the given event.
   */
  public function getDrupalFieldOfStudyFieldItems(array $event);

  /**
   * Get the Field of Interest taxonomy term for an AM.net interest code.
   *
   * @param string $interest_code
   *   The AM.net interest code.
   * @param bool $sync
   *   TRUE if terms should be synced and this operation should try again,
   *   FALSE if this operation should fail without re-syncing terms.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Taxonomy term for the given interest code, if one exists.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getDrupalFieldOfInterestTerm($interest_code, $sync = TRUE);

  /**
   * Get the Field of Study taxonomy term for an AM.net field of study code.
   *
   * @param string $code
   *   The AM.net field of study code.
   * @param bool $sync
   *   TRUE if terms should be synced and this operation should try again,
   *   FALSE if this operation should fail without re-syncing terms.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Taxonomy term for the given field of study code, if one exists.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getDrupalFieldOfStudyTerm($code, $sync = TRUE);

  /**
   * Get the Firm taxonomy term for an AM.net firm code.
   *
   * @param string $amnet_firm_code
   *   The AM.net firm code.
   * @param bool $sync
   *   TRUE if terms should be synced and this operation should try again,
   *   FALSE if this operation should fail without re-syncing terms.
   *
   * @return \Drupal\taxonomy\TermInterface|null
   *   The Taxonomy term for the given firm code, if one exists.
   *
   * @throws \Drupal\am_net\AmNetRecordNotFoundException
   */
  public function getDrupalFirm($amnet_firm_code, $sync = TRUE);

  /**
   * Gets Speaker Person node references for the given AM.net event or session.
   *
   * This method de-duplicates leaders, in case they lead multiple sessions.
   *
   * @param array $record
   *   An AM.net event or session record.
   *
   * @return \Drupal\node\NodeInterface[]
   *   An array of Drupal Person nodes keyed by node id.
   */
  public function getDrupalSessionLeaders(array $record);

  /**
   * Gets the timeslot for the given AM.net session in a Drupal event node.
   *
   * @param \Drupal\vscpa_commerce\Entity\EventSessionInterface $session
   *   The Drupal event session entity.
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The Drupal event node entity.
   * @param array $session_record
   *   The AM.net event session record.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The session timeslot paragraph.
   */
  public function getDrupalSessionTimeslot(EventSessionInterface $session, ProductInterface $product, array $session_record);

  /**
   * Gets the timeslot group for the given session and node.
   *
   * Returns a timeslot group for one day in an event date/time list, or for one
   * session if a day does not exist on the event to group the session(s).
   *
   * @param \Drupal\vscpa_commerce\Entity\EventSessionInterface $session
   *   The event session.
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The event node.
   *
   * @return \Drupal\paragraphs\ParagraphInterface
   *   The session timeslot group paragraph.
   */
  public function getDrupalSessionTimeslotGroup(EventSessionInterface $session, ProductInterface $product);

  /**
   * Syncs AM.net CPE course level codes to Drupal taxonomy terms.
   */
  public function syncDrupalCourseLevels();

  /**
   * Syncs AM.net CPE credit category codes to Drupal taxonomy terms.
   */
  public function syncDrupalCreditTypes();

  /**
   * Syncs AM.net CPE credit category codes to Drupal taxonomy terms.
   */
  public function syncDrupalCityAreas();

  /**
   * Syncs AM.net CPE product format codes to Drupal taxonomy terms.
   */
  public function syncDrupalCpeFormats();

  /**
   * Syncs AM.net event type codes to Drupal taxonomy terms.
   */
  public function syncDrupalCpeTypes();

  /**
   * Syncs AM.net 'Field of Interest' items to Drupal taxonomy terms.
   */
  public function syncDrupalFieldsOfInterest();

  /**
   * Syncs AM.net 'Field of Study' items to Drupal taxonomy terms.
   */
  public function syncDrupalFieldsOfStudy();

}
