Bulk user registration
======================
To import and create large number of users from a CSV file.

INSTALLATION
==============

Install this module as usual. Please see
http://drupal.org/documentation/install/modules-themes/modules-8

CONFIGURATION
==============

After successfully installing the module you should configure the module and
set permissions.

Configure the roles that can be used while importing at
/admin/config/people/bulk_user_register

Set permissions at /admin/people/permissions#module-bulk_user_registration

IMPORTING USERS
===============

Once configured, the module is ready to use for bulk import users from a CSV
file at /admin/people/bulk_user_register

The CSV file must match the field names (columns) of the provided sample CSV.
The first row of the CSV file must contain the field names (see below).
Columns in the CSV file must be separated by commas, values may be enclosed by
double quotes.

A sample CSV file is available for download from the form. The file contains
sample data ready for import. Both standard and extra fields are provided in
the file (see below). For the extra fields only the column names are set, but
no sample data is available.

Fields in CSV
=============

The field names below are the standard fields that are always available for import.

- username: (required) Username of the user
- email: (required) Email of the user
- status: (optional) The user status. Allowed values 0 (blocked) and 1 (active)
  By default the user is blocked.
- role: (optional) The role assigned to the user. Multiple roles must be
  separated by a comma (and role names enclosed by double quotes). If no role
  is provided the default role will be applied.

Only these fields and the extra field below will be processed. Other field names
will be ignored.

Extra fields can be defined with hook_bulk_user_registration_extra_fields().
The values of extra fields are not automatically stored in the user object.
This should be done by using hook_bulk_user_registration_extra_fields().
