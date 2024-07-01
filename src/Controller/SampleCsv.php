<?php

namespace Drupal\bulk_user_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sample CSV controller.
 *
 * @package Drupal\bulk_user_registration
 */
class SampleCsv extends ControllerBase {

  /**
   * Provides a response with downloadable CSV file data.
   */
  public function content() {

    $response = new Response();
    $response->setContent($this->getCsvData());
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="bulk-user-registration-sample.csv"');
    return $response;
  }

  /**
   * Returns the sample CSV data.
   *
   * @return string
   *   CSV data. Fields separated by comma, rows separated by new line.
   */
  protected function getCsvData() {

    $rows[] = implode(',', $this->wrap($this->getFieldNames()));
    $rows[] = 'active-user-default-role,mail1@example.com,1';
    $rows[] = 'blocked-user-default-role,mail2@example.com,0';
    $rows = array_merge($rows, $this->getUserDataWithRole());

    return implode("\n", $rows);
  }

  /**
   * Returns CSV data for users with role. One user per role.
   *
   * @return array
   *   Comma separated user data.
   */
  protected function getUserDataWithRole() {

    $data = [];
    $allowedRoles = \Drupal::config('bulk_user_registration.settings')
      ->get('allowed_roles');

    foreach (array_filter($allowedRoles) as $role) {
      $data[] = "user_{$role},mail.{$role}@example.com,1,{$role}";
    }

    return $data;
  }

  /**
   * Returns the CSV field names.
   *
   * @return string[]
   *   Array of field names.
   */
  protected function getFieldNames() {

    $standardFields = [
      'username',
      'email',
      'status',
      'role',
    ];
    $extraFields = \Drupal::moduleHandler()->invokeAll('bulk_user_registration_extra_fields');

    return array_merge($standardFields, $extraFields);
  }

  /**
   * A simple treatment for strings in CSV.
   *
   * Since this sample CSV generator uses controlled content, we can safely
   * assume no double quotes or comma's are present in string content. Therefore
   * no library is needed and we can use a simple treatment for stings.
   *
   * @param string|string[] $source
   *   The string to check.
   *
   * @return string|string[]
   *   When the source string contains a space it is wrapped in double quotes.
   */
  protected function wrap($source) {

    $strings = is_array($source) ? $source : [$source];
    $result = [];

    foreach ($strings as $string) {
      if (strpos($string, ' ') === FALSE) {
        $result[] = $string;
      }
      else {
        $result[] = '"' . $string . '"';
      }
    }

    return is_array($source) ? $result : reset($result);
  }

}
