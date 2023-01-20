# Drupal Moodle migration intergation

A working example of a migration is in the `moodle_rest_course` module.

`moodle_rest_course/config/install/migrate_plus.migration.moodle_course_by_field.yml`

Points of note:

```
source:
  plugin: moodle_get_courses_by_field
```

This module provides two plugins a basic plugin `moodle_base` and
`moodle_get_courses_by_field` which extends it specifically for the
`core_course_get_courses_by_field` Moodle API method.

The Get Courses plugin sets the function, this will without arguments
try and retrieve all courses. To limit them it takes the same arguments as 
\Drupal\moodle_rest\Services\RestFunctions::getCoursesByField(): 'id',
'ids', 'shortname', 'idnumber', 'category'. So to retrieve only items in
the category 123:

```
source:
  plugin: moodle_get_courses_by_field
  arguments:
    field: 'category'
    value: 123
```

Retrieving courses is likely the most common requirement for migrate. Other
API methods might work directly using the base.

```
source:
  plugin: moodle_base
  function: moodle_api_function_name
  arguments:
    key: value
```

Examples of functions and arguments, and links to the documentation can be
found in the Moodle Rest module's RestFunctions service and
https://docs.moodle.org/dev/Web_service_API_functions. If the method returns
a nested set of results and exceptions an additional plugin will be needed as
with the get courses by field. Contributions of plugins others needed welcome.

## Files

There is a plugin to handle retrieving files from the described url in
summaryfiles and overviewfiles, as well as probably others. The file will be
retrived from the moodle end point with your credentials. The user will need
`core_files_get_files` permission.

```
process:
...
  course_image:
    plugin: moodle_file
    source: summaryfiles/0/fileurl
    destination_dir: 'public://moodle_course_images'
```
