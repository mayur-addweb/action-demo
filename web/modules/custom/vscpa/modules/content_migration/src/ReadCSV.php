<?php

namespace Drupal\vscpa_content_migration;

/**
 * Use this to read CSV files. PHP's fgetcsv() does not conform to RFC.
 */
class ReadCSV {

  /**
   * The field start.
   */
  const FIELD_START = 0;

  /**
   * The unquoted field.
   */
  const UNQUOTED_FIELD = 1;

  /**
   * The quoted field.
   */
  const QUOTED_FIELD = 2;

  /**
   * The found quote.
   */
  const FOUND_QUOTE = 3;

  /**
   * The found cr q.
   */
  const FOUND_CR_Q = 4;

  /**
   * The found cr.
   */
  const FOUND_CR = 5;

  /**
   * The file path.
   *
   * @var resource
   */
  protected $file;

  /**
   * The sep.
   *
   * @var string
   */
  protected $sep;

  /**
   * The eof.
   *
   * If $eof is TRUE, the next nextChar() will return FALSE.
   * Note that this is different to feof(), which is TRUE
   * _after_ EOF is encountered.
   *
   * @var string
   */
  protected $eof;

  /**
   * The nc.
   *
   * @var string
   */
  protected $nc;

  /**
   * The construct of ReadCSV objects.
   *
   * @param resource $file_handle
   *   Open file to read from.
   * @param string $sep
   *   Column separator character.
   * @param string $skip
   *   Initial character sequence to skip if found. e.g. UTF-8 byte-order mark.
   */
  public function __construct($file_handle, $sep, $skip = "") {
    $this->file = $file_handle;
    $this->sep = $sep;
    $this->nc = fgetc($this->file);
    // Skip junk at start.
    for ($i = 0; $i < strlen($skip); $i++) {
      if ($this->nc !== $skip[$i]) {
        break;
      }
      $this->nc = fgetc($this->file);
    }
    $this->eof = ($this->nc === FALSE);
  }

  /**
   * Get next char.
   *
   * @return string
   *   Strings from the next record.
   */
  private function nextChar() {
    $c = $this->nc;
    $this->nc = fgetc($this->file);
    $this->eof = ($this->nc === FALSE);
    return $c;
  }

  /**
   * Get next record from CSV file.
   *
   * @return array|null
   *   array of strings from the next record in the CSV file, or NULL if
   *   there are no more records.
   */
  public function getRow() {
    if ($this->eof) {
      return NULL;
    }

    $row = [];
    $field = "";
    $state = self::FIELD_START;

    while (TRUE) {
      $char = $this->nextChar();

      if ($state == self::QUOTED_FIELD) {
        if ($char === FALSE) {
          $row[] = $field;
          return $row;
        }
        // Fall through to accumulate quoted chars in switch() {...}.
      }
      elseif ($char === FALSE || $char == "\n") {
        // End of record.
        $row[] = $field;
        return $row;
      }
      elseif ($char == "\r") {
        // Possible start of \r\n line end, but might be just part of foo\rbar.
        $state = ($state == self::FOUND_QUOTE) ? self::FOUND_CR_Q : self::FOUND_CR;
        continue;
      }
      elseif ($char == $this->sep &&
        ($state == self::FIELD_START ||
          $state == self::FOUND_QUOTE ||
          $state == self::UNQUOTED_FIELD)
      ) {
        // End of current field, start of next field.
        $row[] = $field;
        $field = "";
        $state = self::FIELD_START;
        continue;
      }

      switch ($state) {

        case self::FIELD_START:
          if ($char == '"') {
            $state = self::QUOTED_FIELD;
          }
          else {
            $state = self::UNQUOTED_FIELD;
            $field .= $char;
          }
          break;

        case self::QUOTED_FIELD:
          if ($char == '"') {
            $state = self::FOUND_QUOTE;
          }
          else {
            $field .= $char;
          }
          break;

        case self::UNQUOTED_FIELD:
          $field .= $char;
          break;

        case self::FOUND_QUOTE:
          $field .= $char;
          $state = self::QUOTED_FIELD;
          break;

        case self::FOUND_CR:
          $field .= "\r" . $char;
          $state = self::UNQUOTED_FIELD;
          break;

        case self::FOUND_CR_Q:
          $field .= "\r" . $char;
          $state = self::QUOTED_FIELD;
          break;
      }
    }
    return NULL;
  }

}
