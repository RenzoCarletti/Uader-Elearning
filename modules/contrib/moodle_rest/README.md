Drupal Moodle Integration

# INTRODUCTION

The `moodle_rest` module includes just API integration. For the developer it
provides a tested service for direct interaction with the Moodle REST
Webservice and helper service for interacting with specific API functions
including error handling and normalizing parameters.

The submodules provides out-of-the-box implementations that use the services.

## Migrate

There is Migration Source Plugin that enables pulling data from Moodle into
Drupal entities. There is also a process plugin for retrieving associated
files. More documentation about both in (Moodle Rest Migration module)[modules/moodle_rest_migrate/README.md].

## Course

The course module is an example implantation of the migration. With a content
type for storing course data, and a migration (based on migrate plus so the
configuration can be extended).

## User

Still in development the user module will provide association with Moodle
Users, push and pull syncronisation. With the course content type it could
also allow direct manual enroll on courses.

# REQUIREMENTS

Your Moodle instance will need to be configured for REST Webservices.

https://docs.moodle.org/311/en/Web_services

Which functions are enabled for a particular user and service differ.
Depending on permissions these might be listed on the module configuration
page (see below).

# INSTALLATION

Enable as a normal Drupal Module.

# CONFIGURATION

Enter the Moodle REST API (see requirements) username and token at 
`/admin/config/services/moodle`.

Enable submodules as required, or use them as examples to extend.
