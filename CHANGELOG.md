# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.2.1]
### Changed
- Changed Control class property renderNoContent to be an init parameter so that it can be modified as a tag attribute, e.g. <cms:Content renderNoContent="true|false" />
### Fixed
- Minor path issues when not using DIRECTORY_SEPARATOR constant.
## [1.2.0]
### Added
- New WebImage\Config\Config for better configuration merging capabilities.
### Changed
- Changed application configuration from being an array to being of the new type WebImage\Config\Config
- Moved several Application related classes out of the root namespace and into their own.
### Fixed
- Fixed minor admin home page bug.

## [1.1.7]
### Fixed
- Added database handle reference to mysqli_error(...) calls in database library

## [1.1.6]
### Fixed
- Updated ServiceManager to inject an instance of itself in created objects that implement IServiceManagerAware.
- Minor Control class issues.
### Changed
- Changed Page class so that it is injected with an instance of the ServiceManager.
- ControlManager is now created with new ControlManager.
- Cleaned up old commented out code.
 
## [1.1.5]
### Changed
- Modified ErrorRequestHandler so that the Page Not Found message is displayed within the standard template.

## [1.1.4]
### Changed
- Added new way to initialize a site with a default site configuration.  Now FrameworkManager::init(...) can also be called with FrameworkManager::init($config_site, $mode, $domain).

## [1.1.3]
### Fixed
Fixed an issue with loading legacy configuration via SITE_KEY
Fixed configuration issue with path mappings that do not have a requestHandler value

## [1.1.2]
### Fixed
Removed IRequestHandler methods that overlapped with methods in extended IServiceManagerAware

## [1.1.1]
### Fixed
Fixed custodian logging enable status

## [1.1.0]
### Changed
- Changed framework to use array based configuration.

### Added
- ServiceManager and related classes

## [1.0.0]
Initial release