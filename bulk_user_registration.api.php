<?php

/**
 * @file
 * Hooks provided by the Bulk User Registration module.
 */

use Drupal\user\UserInterface;

/**
 * Provide names of additional fields for import by bulk user registration.
 *
 * A field name is the column header text in the CSV file.
 *
 * @return array
 *   Array of field names.
 */
function hook_bulk_user_registration_extra_fields() {

  // Make sure you store the imported data of these field into the user object
  // in an implementation of hook_bulk_user_registration_extra_fields().
  return [
    'realname',
    'identifier',
  ];
}

/**
 * Allows to alter the user object after import of CSV data.
 *
 * Typically used to set the value of extra imported fields.
 *
 * @param \Drupal\user\UserInterface $user
 *   The user object.
 * @param array $data
 *   The raw CSV data.
 *
 * @see hook_bulk_user_registration_extra_fields()
 */
function hook_bulk_user_registration_user_presave(UserInterface $user, array $data) {

  if (!empty($data['realname'])) {
    $user->set('realname', trim($data['realname']));
  }
  if (!empty($data['identifier'])) {
    $user->set('identifier', trim($data['identifier']));
  }
}
