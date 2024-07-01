<?php

namespace Drupal\bulk_user_registration;

/**
 * The bulk user registration interface.
 *
 * @package Drupal\bulk_user_registration
 */
interface BulkUserRegistrationInterface {

  /**
   * User field names.
   */
  const FIELD_USER_NAME = 'username';
  const FIELD_EMAIL = 'email';
  const FIELD_STATUS = 'status';
  const FIELD_ROLE = 'role';

  /**
   * Creates and saves a user.
   *
   * @param array $userData
   *   Structured array of user data.
   * @param string $defaultRole
   *   The machine name of the default user role.
   *
   * @return \Drupal\user\UserInterface
   *   The saved user.
   */
  public function createUser(array $userData, $defaultRole);

  /**
   * Get CSV field names.
   *
   * @return string[]
   *   Structured array of field names with the field names as key. Extra fields
   *   are identified by the value TRUE. Default fields have a value FALSE.
   */
  public function getFieldNames();

}
