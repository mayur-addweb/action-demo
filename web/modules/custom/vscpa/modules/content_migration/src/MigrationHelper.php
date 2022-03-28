<?php

namespace Drupal\vscpa_content_migration;

use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;

/**
 * Implementation of the Migration Helper class.
 */
class MigrationHelper {

  /**
   * The Firm Admin Access TID.
   */
  const FIRM_ADMIN_ACCESS_TID = 15275;

  /**
   * The Member Access TID.
   */
  const MEMBER_ACCESS_TID = 15274;

  /**
   * The Non-Member Access TID.
   */
  const NON_MEMBER_ACCESS_TID = 15273;

  /**
   * The Public Access TID.
   */
  const PUBLIC_ACCESS_TID = 15272;

  /**
   * Migrate Web Experiences.
   *
   * @param string $page_id
   *   The old Page ID.
   * @param array $experiences
   *   The array of web experiences.
   */
  public function migrateWebExperiences($page_id = '', array $experiences = []) {
    if (empty($page_id) || empty($experiences)) {
      return;
    }
    // Look up publication by Page ID.
    $publication = $this->loadPublicationNodeByPageId($page_id);
    if ($publication == FALSE) {
      return;
    }
    $interest_codes = $this->loadTermCodes('interest', 'field_amnet_interest_code');
    $general_business_codes = $this->loadTermCodes('general_business', 'field_amnet_gb_code');
    $position_codes = $this->loadTermCodes('job_position', 'field_amnet_position_code');
    $custom_codes = $this->loadTermCodes('web_experience_custom', 'field_amnet_code');
    $action_codes = $this->loadTermCodes('vscpa_action', 'field_amnet_action_code');
    $positions = [];
    $customs = [];
    $interests = [];
    $actions = [];
    $general_business = [];
    foreach ($experiences as $delta => $experience) {
      $code = isset($experience['code']) ? trim($experience['code']) : NULL;
      if (empty($code)) {
        continue;
      }
      // Interest codes.
      if (in_array($code, $interest_codes)) {
        $tid = $this->loadTidByCode($code, 'interest', 'field_amnet_interest_code');
        if (!empty($tid) && !in_array($tid, $interests)) {
          $interests[] = $tid;
        }
      }
      // General Business.
      elseif (in_array($code, $general_business_codes)) {
        $tid = $this->loadTidByCode($code, 'general_business', 'field_amnet_gb_code');
        if (!empty($tid) && !in_array($tid, $general_business)) {
          $general_business[] = $tid;
        }
      }
      // Position.
      elseif (in_array($code, $position_codes)) {
        $tid = $this->loadTidByCode($code, 'job_position', 'field_amnet_position_code');
        if (!empty($tid) && !in_array($tid, $positions)) {
          $positions[] = $tid;
        }
      }
      // Custom.
      elseif (in_array($code, $custom_codes)) {
        $tid = $this->loadTidByCode($code, 'web_experience_custom', 'field_amnet_code');
        if (!empty($tid) && !in_array($tid, $customs)) {
          $customs[] = $tid;
        }
      }
      // Actions.
      elseif (in_array($code, $action_codes)) {
        $tid = $this->loadTidByCode($code, 'vscpa_action', 'field_amnet_action_code');
        if (!empty($tid) && !in_array($tid, $actions)) {
          $actions[] = $tid;
        }
      }
    }
    // Update publication values.
    $save = FALSE;
    if (!empty($interests)) {
      $publication->set('field_field_of_interest', $interests);
      $save = TRUE;
    }
    if (!empty($general_business)) {
      $publication->set('field_general_business', $general_business);
      $save = TRUE;
    }
    if (!empty($positions)) {
      $publication->set('field_position', $positions);
      $save = TRUE;
    }
    if (!empty($customs)) {
      $publication->set('field_custom', $customs);
      $save = TRUE;
    }
    if (!empty($actions)) {
      $publication->set('field_vscpa_action', $actions);
      $save = TRUE;
    }
    // Save changes.
    if ($save) {
      $publication->save();
    }
  }

  /**
   * Migrate Properties.
   *
   * @param string $page_id
   *   The old Page ID.
   * @param array $properties
   *   The array of properties.
   */
  public function migrateProperties($page_id = '', array $properties = []) {
    if (empty($page_id) || empty($properties)) {
      return;
    }
    // Look up publication by Page ID.
    $publication = $this->loadPublicationNodeByPageId($page_id);
    if ($publication == FALSE) {
      return;
    }
    $properties = $this->removeDuplicates($properties);
    $values = [];
    // Public Access.
    $public_access = 1;
    if (isset($properties['PublicAccess']['Value'])) {
      // Un-check.
      $public_access = 0;
    }
    if ($public_access) {
      $values[] = self::PUBLIC_ACCESS_TID;
    }
    // Non-Member Access.
    $non_member_access = 1;
    if (isset($properties['NonMemberAccess']['Value'])) {
      // Un-check.
      $non_member_access = 0;
    }
    if ($non_member_access) {
      $values[] = self::NON_MEMBER_ACCESS_TID;
    }
    // Firm Admin Access.
    $firm_admin_access = 0;
    if (isset($properties['FirmAdminAccess']['Value'])) {
      $firm_admin_access = 1;
    }
    if ($firm_admin_access) {
      $values[] = self::FIRM_ADMIN_ACCESS_TID;
    }
    // Member Access.
    $member_access = 1;
    if (isset($properties['MemberAccess']['Value'])) {
      // Un-check.
      $member_access = 0;
    }
    if ($member_access) {
      $values[] = self::MEMBER_ACCESS_TID;
    }
    // Can work with multi value fields like an array.
    $publication->field_memberonly = $values;
    // Is Searchable.
    $is_searchable = 1;
    if (isset($properties['ShowInSearch']['Value'])) {
      // Un-check.
      $is_searchable = 0;
    }
    $field_name = 'sae_exclude';
    if ($publication->hasField($field_name) && ($is_searchable == 0)) {
      // Prevent this node from being indexed.
      $publication->set($field_name, 1);
    }
    // Save the changes.
    $publication->save();
  }

  /**
   * Migrate Content Editor Files.
   *
   * @param string $node_id
   *   The Publication Node ID.
   */
  public function migrateContentEditorFiles($node_id = '') {
    if (empty($node_id)) {
      return;
    }
    $publication = Node::load($node_id);
    $body = $publication->get('body')->getValue();
    $body = is_array($body) ? current($body) : NULL;
    if (!isset($body['value']) || empty($body['value'])) {
      return;
    }
    $value = $body['value'];
    $old_value = $value;
    // Search for all <img> tag in the value (usually the body).
    if (preg_match_all('#<img[^>]*>#', $value, $matches)) {
      foreach ($matches[0] as $orig) {
        // Clean up the attributes in the img tag.
        $new = $this->cleanUpImageAttributes($orig, $node_id);
        // Replace the original image path with the new image path
        // TODO: This is not a great way to recompose the value string.
        $value = preg_replace("|$orig|", $new, $value);
      }
    }
    // Save the Changes.
    if (!empty($value)) {
      $body['value'] = $value;
      $publication->set('body', $body);
      $publication->save();
    }
  }

  /**
   * Populate Access Levels.
   *
   * @param string $node_id
   *   The Publication Node ID.
   */
  public function populateAccessLevels($node_id = '') {
    if (empty($node_id)) {
      return;
    }
    $publication = Node::load($node_id);
    $field_name = 'field_memberonly';
    if ($publication->hasField($field_name)) {
      $value = [
        self::PUBLIC_ACCESS_TID,
        self::NON_MEMBER_ACCESS_TID,
        self::MEMBER_ACCESS_TID,
      ];
      // Prevent this node from being indexed.
      $publication->set($field_name, $value);
      // Save the changes.
      $publication->save();
    }
  }

  /**
   * Clean Up Image Attributes.
   *
   * Given the contents of an <img> tag, examine the attributes, clean
   * them up, and return the final text to use.
   *
   * @param string $imgElement
   *   The string with the img Element.
   * @param string $entity_id
   *   The Publication Node ID.
   *
   * @return string
   *   The string of th new imgElement.
   */
  public function cleanUpImageAttributes($imgElement = '', $entity_id = '') {
    // Pull the image path out of the image element.
    if (empty($imgElement) || empty($entity_id) || !preg_match('#src="([^"]*)"#', $imgElement, $matches)) {
      return $imgElement;
    }
    // If the image is already processed do nothing.
    if (strpos($imgElement, 'data-entity-uuid') !== FALSE) {
      return $imgElement;
    }
    // The Image Path.
    $imagePath = strtolower($matches[1]);
    $baseImagePath = $imagePath;
    // If the image is stored in 'public://image', then an img src of
    // 'files/image/subdir/pict.jpg' will correspond to an entity uri
    // of 'subdir/pict.jpg'. The 'base' in this example is 'files/image';
    // strip the base off the img src so that we can use it to search for
    // the file by its entity uri.
    $base_domain = 'https://www.vscpa.com';
    $base_source_url = $baseImagePath;
    if (!(strpos($baseImagePath, $base_domain) !== FALSE)) {
      $base_source_url = $base_domain . $baseImagePath;
    }
    // Remove prefixes.
    $prefixes = [
      'https://www.vscpa.com',
      'http://www.vscpa.com',
      '/content/files/vscpa/',
    ];
    // Remove Prefixes.
    foreach ($prefixes as $key => $prefix) {
      $imagePath = str_replace($prefix, '', $imagePath);
    }
    // Destination.
    $destination = 'public://' . $imagePath;
    $path = pathinfo($destination);
    $destination_directory = $path['dirname'];
    // Find File by URI.
    $fids = \Drupal::entityQuery('file')
      ->condition('uri', '%' . $imagePath . '%', 'LIKE')
      ->range(0, 2)
      ->execute();

    $file = FALSE;
    if ($fids) {
      $file_storage = \Drupal::entityTypeManager()->getStorage('file');
      $files = $file_storage->loadMultiple($fids);
      $file = reset($files);
    }
    else {
      // Create file object from remote URL.
      if ($this->remoteFileExists($base_source_url)) {
        // Checks that the destination directory exists and is writable.
        file_prepare_directory($destination_directory, FILE_CREATE_DIRECTORY);
        file_prepare_directory($destination_directory, FILE_MODIFY_PERMISSIONS);
        // Download the file.
        $data = file_get_contents($base_source_url);
        $file = file_save_data($data, $destination, FILE_EXISTS_ERROR);
      }
      else {
        // Logs an error.
        $message = "No image found for path: $imagePath (was " . $baseImagePath . ")</br>";
        $message .= "URL: $base_source_url </br>";
        $message .= "Node: $entity_id </br>";
        \Drupal::logger('vscpa_content_migration')->error($message);
      }
    }
    // If File Exists replace the IMG tag.
    if ($file != FALSE) {
      // Build new url.
      $new_relative_url = file_create_url($destination);
      $new_relative_url = file_url_transform_relative($new_relative_url);
      $uuid = $file->uuid();
      $align = $this->determineAlign($imgElement);
      if (preg_match('@src="([^"]+)"@', $imgElement, $match)) {
        $src = array_pop($match);
        $imgElement = str_replace($src, $new_relative_url, $imgElement);
      }
      $imgElement = str_replace('<img ', "<img$align data-entity-type=\"file\" data-entity-uuid=\"$uuid\" ", $imgElement);
      // Add File Usage.
      $entity_type = 'node';
      $module = 'editor';
      $file_usage = \Drupal::service('file.usage');
      $file_usage->delete($file, $module, $entity_type, $entity_id);
      $file_usage->add($file, $module, $entity_type, $entity_id);
    }

    return $imgElement;
  }

  /**
   * Check if a remote file exists in PHP using curl.
   *
   * @param string $url
   *   The remote url.
   *
   * @return bool
   *   TRUE if the remote file exists, Otherwise FALSE.
   */
  public function remoteFileExists($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    return (curl_exec($ch) !== FALSE);
  }

  /**
   * Determine IMG Align.
   *
   * Examine the entire contents of the <img> tag to see if there
   * is an `align="left"` or similar attribute to indicate a floating
   * image. If so, return an equivalent `data-align` attribute.
   *
   * @param string $imgElement
   *   The string with the img Element.
   *
   * @return string
   *   The data-align tag.
   */
  public function determineAlign($imgElement) {
    $alignments = ['right', 'left'];
    $alignmentPatterns = [
      'imgupl_floating_%s',
      'align="%s"',
      'align=\'%s\'',
    ];
    foreach ($alignments as $align) {
      foreach ($alignmentPatterns as $pattern) {
        $pattern = sprintf($pattern, $align);
        if (strpos($imgElement, $pattern) !== FALSE) {
          return " data-align=\"$align\"";
        }
      }
    }
    return '';
  }

  /**
   * Load Terms codes.
   *
   * @param string $bundle
   *   Required param, The bundle.
   * @param string $field_name
   *   Required param, The field name.
   *
   * @return array
   *   The array of Codes relates to the terms of the vid.
   */
  public function loadTermCodes($bundle = NULL, $field_name = NULL) {
    if (empty($bundle) || empty($field_name)) {
      return [];
    }
    $table_name = "taxonomy_term__{$field_name}";
    $field_value_name = "{$field_name}_value";
    $query = \Drupal::database()->select($table_name, 't');
    $query->fields('t', [$field_value_name]);
    $query->condition('bundle', $bundle);
    $result = $query->execute();
    $codes = $result->fetchAllKeyed(0, 0);
    return !empty($codes) ? $codes : [];
  }

  /**
   * Load Term ID By Code.
   *
   * @param string $code
   *   Required param, The Code ID.
   * @param string $vid
   *   Required param, The vid.
   * @param string $field_name
   *   Required param, The field name.
   *
   * @return null|int
   *   Term ID when the operation was successfully completed, otherwise NULL
   */
  public function loadTidByCode($code = NULL, $vid = NULL, $field_name = NULL) {
    if (empty($code) || empty($vid) || empty($field_name)) {
      return NULL;
    }
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $vid);
    $query->condition($field_name, $code);
    $terms = $query->execute();
    return !empty($terms) ? current($terms) : NULL;
  }

  /**
   * Load Publication node By Page Id.
   *
   * @param int $page_id
   *   Required param, The Page ID.
   *
   * @return bool|\Drupal\node\NodeInterface
   *   TRUE when the operation was successfully completed, otherwise FALSE
   */
  public function loadPublicationNodeByPageId($page_id = NULL) {
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_amnet_id' => $page_id]);
    if (!empty($nodes)) {
      $node = current($nodes);
      if (($node instanceof NodeInterface) && ($node->bundle() == 'publication')) {
        return $node;
      }
    }
    return FALSE;
  }

  /**
   * Group CSV data by PageId.
   *
   * @param string $csv_file
   *   The full path to the CSV file.
   * @param string $callback
   *   The callback function.
   * @param string $type
   *   The type of grouping.
   * @param int $limit
   *   The number of items to be processed.
   *
   * @return array
   *   The items to be processed.
   */
  public function groupDataByPageId($csv_file = '', $callback = NULL, $type = 'web_experiences', $limit = -1) {
    if (empty($csv_file) || empty($callback)) {
      return [];
    }
    // Get CSV rows data.
    $path = \Drupal::service('file_system')->realpath($csv_file);
    $prepare_row_callback = ($type == 'web_experiences') ? 'webExperiencesPrepareRow' : 'propertiesPrepareRow';
    $items = $this->getCsvRowsData($path, $prepare_row_callback);
    if (empty($items)) {
      return [];
    }
    if ($limit != -1) {
      $items = array_slice($items, 0, $limit, TRUE);
    }
    // Build the array of operations.
    $operations = [];
    foreach ($items as $page_id => $params) {
      $operations[] = [
        $callback,
        [$page_id, $params],
      ];
    }
    return $operations;
  }

  /**
   * Get publications.
   *
   * @param array $types
   *   The array with the publication types.
   * @param string $callback
   *   The callback function.
   * @param int $limit
   *   The number of items to be processed.
   *
   * @return array
   *   The items to be processed.
   */
  public function getPublications(array $types = [], $callback = NULL, $limit = -1) {
    if (empty($types) || empty($callback)) {
      return [];
    }
    // Load Publications.
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'publication');
    $query->condition('field_pub_type', $types, 'IN');
    if ($limit != -1) {
      $query->range(0, $limit);
    }
    $nodes_ids = $query->execute();
    if (empty($nodes_ids)) {
      return [];
    }
    // Build the array of operations.
    $operations = [];
    foreach ($nodes_ids as $delta => $node_id) {
      $operations[] = [
        $callback,
        [$node_id],
      ];
    }
    return $operations;
  }

  /**
   * Get publications Without Access Levels.
   *
   * @param array $types
   *   The array with the publication types.
   * @param string $callback
   *   The callback function.
   * @param int $limit
   *   The number of items to be processed.
   *
   * @return array
   *   The items to be processed.
   */
  public function getPublicationsWithoutAccessLevels(array $types = [], $callback = NULL, $limit = -1) {
    if (empty($types) || empty($callback)) {
      return [];
    }
    // Load Publications.
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'publication');
    $query->notExists('field_memberonly');
    if ($limit != -1) {
      $query->range(0, $limit);
    }
    $nodes_ids = $query->execute();
    if (empty($nodes_ids)) {
      return [];
    }
    // Build the array of operations.
    $operations = [];
    foreach ($nodes_ids as $delta => $node_id) {
      $operations[] = [
        $callback,
        [$node_id],
      ];
    }
    return $operations;
  }

  /**
   * Migrate Web Experiences prepare row.
   *
   * @param array $row
   *   The array with the row info.
   *
   * @return array
   *   Returns array with metadata info.
   */
  public function webExperiencesPrepareRow(array $row = []) {
    $page_id = isset($row['PageID']) ? $row['PageID'] : NULL;
    $name = isset($row['Name']) ? $row['Name'] : NULL;
    $code = isset($row['Code']) ? $row['Code'] : NULL;
    $add = !(is_null($page_id) || is_null($code));
    return [
      'id' => $page_id,
      'add' => $add,
      'data' => ['name' => $name, 'code' => $code],
    ];
  }

  /**
   * Properties prepare row.
   *
   * @param array $row
   *   The array with the row info.
   *
   * @return array
   *   Returns array with metadata info.
   */
  public function propertiesPrepareRow(array $row = []) {
    $page_id = isset($row['PageID']) ? $row['PageID'] : NULL;
    $name = isset($row['Name']) ? $row['Name'] : NULL;
    $key = isset($row['Key']) ? $row['Key'] : NULL;
    $value = isset($row['Value']) ? $row['Value'] : NULL;
    $add = !(is_null($page_id) || is_null($key));
    return [
      'id' => $page_id,
      'add' => $add,
      'data' => ['name' => $name, 'key' => $key, 'value' => $value],
    ];
  }

  /**
   * Remove Duplicates Properties.
   *
   * @param array $properties
   *   The array of properties.
   *
   * @return array
   *   The list of properties.
   */
  public function removeDuplicates(array $properties = []) {
    if (empty($properties)) {
      return [];
    }
    $items = [];
    foreach ($properties as $delta => $item) {
      $key = isset($item['key']) ? $item['key'] : NULL;
      if (empty($key)) {
        $items[$key] = $item;
      }
    }
    return $items;
  }

  /**
   * Get CSV rows data by file path.
   *
   * @param string $csv_file
   *   The local file path with the name included.
   * @param string $prepare_row_callback
   *   The prepare row callback.
   *
   * @return array
   *   Returns the array with the CSV data.
   */
  public function getCsvRowsData($csv_file = '', $prepare_row_callback = '') {
    if (empty($csv_file) || empty($prepare_row_callback)) {
      return [];
    }
    // Load the CSV file.
    $fileHandle = @fopen($csv_file, 'r');
    $rows_data = [];
    $headers = [];
    if ($fileHandle) {
      // Loop through the file lines.
      // Skip any UTF-8 byte order mark.
      $csv_reader = new ReadCSV($fileHandle, ',', "\xEF\xBB\xBF");
      $first = TRUE;
      while (($line = $csv_reader->getRow()) !== NULL) {
        // If the first line is empty, abort .
        // If another line is empty, just skip it.
        if (empty($line)) {
          if ($first) {
            break;
          }
          else {
            continue;
          }
        }
        // If we are on the first line, the columns are the headers.
        if ($first) {
          $headers = $line;
          $first = FALSE;
          continue;
        }
        // Get row data.
        $row_data = [];
        foreach ($line as $ckey => $column) {
          $column_name = $headers[$ckey];
          $column = trim($column);
          $row_data[$column_name] = $column;
        }
        // If no user data, bailout!
        if (empty($row_data)) {
          continue;
        }
        $info = $this->$prepare_row_callback($row_data);
        if (!($info['add'])) {
          continue;
        }
        $id = $info['id'];
        $rows_data[$id][] = $info['data'];
      }
      fclose($fileHandle);
    }
    return $rows_data;
  }

}
