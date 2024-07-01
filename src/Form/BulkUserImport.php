<?php

namespace Drupal\bulk_user_registration\Form;

use Drupal\bulk_user_registration\BulkUserRegistration;
use Drupal\bulk_user_registration\BulkUserRegistrationInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * Bulk user import form.
 *
 * @package Drupal\bulk_user_registration
 */
class BulkUserImport extends FormBase {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The bulk user registration service.
   *
   * @var \Drupal\bulk_user_registration\BulkUserRegistration
   */
  protected $bulkUserRegistration;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   * @param \Drupal\bulk_user_registration\BulkUserRegistration $bulkUserRegistration
   *   The bulk user registration service.
   */
  public function __construct(RequestStack $requestStack, BulkUserRegistration $bulkUserRegistration) {
    $this->requestStack = $requestStack;
    $this->bulkUserRegistration = $bulkUserRegistration;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('bulk_user_registration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_user_registration_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['file_upload'] = [
      '#type' => 'file',
      '#title' => $this->t('Import CSV file'),
      '#description' => $this->t('The CSV file to be imported. Check the CSV sample below if you are not sure about the format.'),
      '#autoupload' => TRUE,
      '#upload_validators' => ['file_validate_extensions' => ['csv']],
      '#required' => TRUE,
    ];

    $form['sample_csv'] = [
      '#type' => 'item',
      'link' => [
        '#type' => 'link',
        '#title' => $this->t('Download sample CSV'),
        '#url' => Url::fromRoute('bulk_user_registration.csv_sample'),
      ],
      '#description' => $this->t('This sample file contains all possible fields and various data samples to get you started.'),
    ];

    $form['default_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Default role'),
      '#description' => $this->t('The default role for imported users. When no role data is provided in the CSV, this role will be assigned.'),
      '#options' => self::getAllowedRoles(),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // TODO Prevent form submit when file_upload is empty.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $defaultRole = $form_state->getValue('default_role');
    $this->handleFileData($this->requestStack->getCurrentRequest()->files, $defaultRole);
  }

  /**
   * To import data as users.
   *
   * @param \Symfony\Component\HttpFoundation\FileBag $filedata
   *   Field data.
   * @param string $defaultRole
   *   The default role.
   */
  protected function handleFileData(FileBag $filedata, $defaultRole) {

    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $uploadedFile */
    $uploadedFiles = $filedata->get('files');
    $location = $uploadedFiles['file_upload']->getRealPath();
    if (($handle = fopen($location, 'r')) === FALSE) {
      return;
    }

    // Read the csv data.
    $headerData = [];
    $csvData = [];
    while (($data = fgetcsv($handle)) !== FALSE) {
      if (empty($headerData)) {
        $headerData = $data;
      }
      else {
        $csvData[] = $data;
      }
    }
    fclose($handle);

    // Only standard and extra fields are allowed as csv columns. Unknown
    // fields will be ignored.
    $fieldNames = $this->bulkUserRegistration->getFieldNames();
    $columnsToIgnore = [];
    foreach ($headerData as $column => $header) {
      if (!isset($fieldNames[$header])) {
        $columnsToIgnore[] = $column;
      }
    }

    // Collect the user data in a structured array. Where keys are the
    // names of the appropriate header column.
    $userData = [];
    foreach ($csvData as $csvRow) {
      $row_data = [];
      foreach ($csvRow as $column => $value) {
        if (in_array($column, $columnsToIgnore)) {
          continue;
        }
        $row_data[$headerData[$column]] = trim($value);
      }
      $userData[] = $row_data;
    }
    $userData = array_filter($userData);

    $this->batchProcessUserInfo($userData, $defaultRole);
  }

  /**
   * Process user information in a batch.
   *
   * @param array $userData
   *   Structured array of user data.
   * @param string $defaultRole
   *   Default user role.
   */
  public function batchProcessUserInfo(array $userData, $defaultRole) {
    $operations = [];
    foreach ($userData as $data) {
      $operations[] = [
        '\Drupal\bulk_user_registration\Form\BulkUserImport::batchImport',
        [
          $data,
          $defaultRole,
        ],
      ];
    }

    $batch = [
      'title' => $this->t('Importing users..'),
      'operations' => $operations,
      'finished' => '\Drupal\bulk_user_registration\Form\BulkUserImport::batchFinished',
    ];
    batch_set($batch);
  }

  /**
   * Batch callback: User import operation.
   *
   * @param array $userData
   *   Structured array of user data. The keys are user field names.
   * @param string $defaultRole
   *   The default role.
   * @param array $context
   *   Batch context data.
   */
  public static function batchImport(array $userData, $defaultRole, array &$context) {

    // Required user data is missing. Do not import.
    if (empty($userData[BulkUserRegistrationInterface::FIELD_EMAIL]) || empty($userData[BulkUserRegistrationInterface::FIELD_USER_NAME])) {
      return;
    }

    // This user already exists. Do not import.
    if (user_load_by_mail($userData[BulkUserRegistrationInterface::FIELD_EMAIL])) {
      return;
    }

    $user = \Drupal::service('bulk_user_registration')
      ->createUser($userData, $defaultRole);

    // Notify user via mail.
    if ($user->isActive()) {
      _user_mail_notify('register_no_approval_required', $user);
    }

    $context['results'][] = $user->id();
  }

  /**
   * Batch callback: Finish bulk user import process.
   *
   * @param bool $success
   *   Success or not.
   * @param array $results
   *   Results array.
   * @param mixed $operations
   *   Operations.
   */
  public static function batchFinished($success, array $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addStatus(\Drupal::translation()
        ->formatPlural(count($results), '1 user imported.', '@count users imported.'));
    }
    else {
      $messenger->addError(t('Finished with errors.'));
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

}
