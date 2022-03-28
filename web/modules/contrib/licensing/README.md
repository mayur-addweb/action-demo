CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Licensing module allows administrators to moderate entity access by issuing
licenses to site users. These licenses may optionally be set to expire at a
predefined date and time.

This currently only manages the 'view' permission on the licensed entity and
does not affect create, update, or delete related permissions. These permissions
should be managed separately.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/licensing

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/licensing


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the Licensing module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > License types to list and add
       License type bundles.
    3. This module provide a new "license" entity which can be used to moderate
    access to any other entity type(s) and bundle(s). These may be created via a
    (provided) user interface or programmatically.

The license entity has the following base fields:
 * Owner (user reference)
 * Expiry date and time (datetime)
 * Expires automatically (boolean)
 * Licensed entity (entity reference)
 * Status (select)

You may create and customize your own license types (bundles) via the user
interface. Each license type (bundle) has the following configurable options:
 * Target entity type: the type of entity that is licensed.
 * Target bundles: the bundles that are licensed.
 * Restricted roles: the roles whose access are restricted by licenses.

The following default views will ship with the licensing module (work in
progress):
 * Licenses by status
 * Licenses referencing a particular entity
 * Licenses belonging to a particular user

Access control
This module currently only manages the "view" permission on the licensed entity
and does not affect create, update, or delete related permissions. This should
be managed separately. Please submit a patch or feature request if you have a
use case for more than the "view" permission.

If an entity type is managed by the licensing module, any user with a
"restricted role" will be forbidden to access any entity of that type unless
they have a license to do so.

Developers
Because licenses are entities, you can easily create license entities
programmatically and hook into the license creation process using the Entity
API.

You can also create custom license types (bundles) and export the configuration.


MAINTAINERS
-----------

 * Matthew Grasmick (grasmash) - https://www.drupal.org/u/grasmash
 * Nick Santamaria (nicksanta) - https://www.drupal.org/u/nicksanta
