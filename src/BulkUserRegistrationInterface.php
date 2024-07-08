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
  const FIELD_FIRSTNAME = 'field_firstname';
  const FIELD_MIDDLENAME = 'field_middlename';
  const FIELD_LASTNAME = 'field_lastname';
  const FIELD_SEX = 'field_sex';
  const FIELD_SERVICE = 'field_service';
  const FIELD_OFFICE = 'field_office';
  const FIELD_DIVISON = 'field_division';
  const FIELD_MOBILE_NUMBER = 'field_mobile_number';
  const FIELD_POSITION = 'field_position';
  const FIELD_ID_NUMBER = 'field_id_number';

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
