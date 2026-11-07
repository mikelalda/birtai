## 1.2.0

**Released on:** 2026-11-08

**Compatibility note:** This version is compatible with **Moodle 5.0 to 5.3**: it is the `main`
branch. Moodle 4.5 has its own branch, `MOODLE_405_STABLE` (versions up to 2026110899).

## Changed
- Moodle 5 only: no compatibility layer for the provider API of Moodle 4.5 any more, no
  settings.php (Moodle 5 does not load it).
- The upgrade to provider instances runs at version 2026110900, above every version of the 4.5
  branch, so it runs for any site that comes from it (again, harmlessly, on sites that ran 1.1.0).

## Fixed
- **Web service setup with the local API**: it no longer waits for a site registration that the
  local API does not do. That left the web service "not configured" and blocked the AI review
  actions of assign_ai.
- **Failed AI requests**: a failure lacked some of the error fields that Moodle passes, typed, to
  the response class, so an invalid answer or a blocked URL ended in a TypeError instead of an
  error message.

## 1.1.0

**Released on:** 2026-11-08

**Compatibility note:** This version is compatible with **Moodle 4.5 and Moodle 5.0 to 5.3**.

## Fixed
- **Moodle 5.0 and later**: the provider class follows the provider API of Moodle 5 (static action
  methods, action settings per instance). It caused a fatal error on Moodle 5, which also broke the
  service and action lists of the reports.
- **System instruction of "Generate text"**: the configured instruction was read from a setting name
  that was never saved, so the default one was always used.

## Added
- **Settings page for Moodle 5.0 and later** (`admin/settings.php`): Moodle 5 no longer loads the
  `settings.php` of AI provider plugins. The form of a birtai provider instance links to it and to the
  plugin's other pages. The defaults of the settings are written on install and upgrade.
- **Provider instance from the Moodle 4.5 settings**: on upgrade, a site that used birtai gets a
  provider instance (enabled if birtai was enabled), keeping the system instructions.
- PHPUnit tests of the provider on both provider APIs.

## 1.0.8

**Released on:** 2026-01-29

**Compatibility note:** This version is compatible **with Moodle 4.5 only**.

## Fixed
- **Suppress developer debug warning when listing rate-limited users**  
  Updated the rate-limit user selector query to load all required name fields (`firstnamephonetic`, `lastnamephonetic`, `middlename`, `alternatename`) so that `fullname()` no longer triggers the developer `debugging()` warning when building the allowed users lists.


## 1.0.7

**Released on:** 2026-01-26

**Compatibility note:** This version is compatible **from Moodle 4.5 to Moodle 5.1**.

## Added
- **Configurable base URLs for birtai AI services**  
  Added support for configurable base URLs for both the **standard** and **EU-hosted** birtai AI services, allowing greater flexibility across environments.
- **Optional base URL parameters in constructors**  
  Updated service constructors to accept optional base URL parameters, enabling explicit overrides when needed.

## Changed
- **Centralized base URL resolution via instance method**  
  Refactored base URL access to ensure the correct instance method is used when resolving the active base URL, improving consistency and maintainability.
- **Service initialization flow updated**  
  Adjusted internal initialization logic so all API requests correctly respect the configured base URL (standard or EU-hosted).
- **Version bump**  
  Release version bumped to **1.0.7**.


## 1.0.6

**Released on:** 2026-01-19

## Added

- **Enhanced webservice setup error logging.**  
Improved error reporting during webservice registration by including the original exception message, providing clearer diagnostics when the setup process fails.

## Changed

- **Improved boolean evaluation logic.**  
Adjusted the `is_for_ue` method to ensure proper and safe boolean comparison, preventing unintended conditional behavior.

## Fixed

- **Webservice setup debugging limitations.**  
Resolved an issue where webservice registration failures did not expose sufficient context, making troubleshooting difficult.

## Changed

- **Release bump to 1.0.6**  
Updated the plugin version and release metadata to **1.0.6** to reflect the included improvements and fixes.

## 1.0.5

**Released on:** 2025-12-04

 **Compatibility note:** This version is compatible **only with Moodle 4.5**.

## Fixed
- **Upgrade savepoint order corrected**  
  Reordered the upgrade savepoint to prevent upgrade failures related to the `aiprovider_birtai_userlimit`

## 1.0.4

**Released on:** 2025-12-02

Fixed
- add missing capabilities and web service functions

## 1.0.3

**Released on:** 2025-12-02

 **Compatibility note:** This version is compatible **only with Moodle 4.5**.

## Added
- **Automated release workflow for the plugin.**  
  A new GitHub Actions workflow was added to streamline/automate Moodle plugin releases.
- **Support only for Moodle 4.5.**  
  Added `$plugin->supported` in `version.php` to declare Moodle 4.5 as the only supported version.

## Changed
- **Release bump to 1.0.3**  
  The plugin release number was updated to **1.0.3**.

