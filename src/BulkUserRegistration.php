<?php

namespace Drupal\bulk_user_registration;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * The BulkUserRegistration service.
 *
 * @package Drupal\bulk_user_registration
 */
class BulkUserRegistration implements BulkUserRegistrationInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Cached field names.
   *
   * @var array
   */
  protected $fieldNames = [];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(LanguageManagerInterface $languageManager, ModuleHandlerInterface $moduleHandler) {
    $this->languageManager = $languageManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function createUser(array $userData, $defaultRole) {

    $langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    /** @var \Drupal\user\UserInterface $user */
    $user = User::create();
    $user->setUsername($userData[self::FIELD_USER_NAME]);
    $user->setEmail($userData[self::FIELD_EMAIL]);
    $user->set('init', $userData[self::FIELD_EMAIL]);
    $user->set('langcode', $langcode);
    $user->set('preferred_langcode', $langcode);
    $user->set('preferred_admin_langcode', $langcode);
    $user->enforceIsNew();
    if (!$userData[self::FIELD_STATUS]) {
      $user->block();
    }
    else {
      $user->activate();
    }

    // Single or multiple roles will be applied to the user object. Multiple
    // roles should be comma separated.
    $roles = [];
    $csvRoles = explode(',', $userData[self::FIELD_ROLE]);
    $allowedRoles = $this->getAllowedRoles();
    foreach ($csvRoles as $csvRole) {
      $csvRole = trim($csvRole);
      if (isset($allowedRoles[$csvRole])) {
        $roles[] = $csvRole;
      }
    }
    $roles = array_filter(array_unique($roles));
    $roles = empty($roles) ? [$defaultRole] : $roles;
    foreach ($roles as $role) {
      $this->addRole($user, $role);
    }

    // Allow modules to modify the user object before saving it. Typically used
    // to store the extra field data into the user.
    \Drupal::moduleHandler()
      ->invokeAll('bulk_user_registration_user_presave', [$user, $userData]);

    $user->save();

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames() {

    if (count($this->fieldNames) == 0) {
      $fieldNames = [];

      // Allow modules to define extra fields that will be allowed during
      // import.
      $extraFields = $this->moduleHandler
        ->invokeAll('bulk_user_registration_extra_fields');

      // Add standard fields.
      foreach ($this->getStandardFields() as $name) {
        $fieldNames[$name] = FALSE;
      }
      // Add extra fields to the set of field names and flag them as extra.
      foreach ($extraFields as $name) {
        if (!isset($fieldNames[$name])) {
          $fieldNames[$name] = TRUE;
        }
      }
      $this->fieldNames = $fieldNames;
    }
    return $this->fieldNames;
  }

  /**
   * Add user role.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $role
   *   Role Id.
   */
  protected function addRole(UserInterface $user, $role) {

    if (!in_array($role, [
      RoleInterface::AUTHENTICATED_ID,
      RoleInterface::ANONYMOUS_ID,
    ])) {
      $user->addRole($role);
    }
  }

  /**
   * The roles allowed to import.
   *
   * @return array
   *   An associative array with the role id as the key and the role name as
   *   value.
   */
  protected function getAllowedRoles() {

    $allowedRoles = \Drupal::config('bulk_user_registration.settings')->get('allowed_roles');
    return array_intersect_key(user_role_names(TRUE), array_flip(array_filter($allowedRoles)));
  }

  /**
   * Returns the names of standard CSV fields.
   *
   * @return array
   *   Array of field names.
   */
  protected function getStandardFields() {

    return [
      self::FIELD_USER_NAME,
      self::FIELD_EMAIL,
      self::FIELD_STATUS,
      self::FIELD_ROLE,
    ];
  }

}
