# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [1.5.5]
### Fixed
- Fixed cache manager and configuration issue
- Fixed Dictionary::mergeDictionary issue

## [1.5.4]
### Changed
- Removed m_ vars from BreadCrumbControl, ContentControl, DataGridControl, ErrorControl

## [1.5.3]
### Fixed
- Escape incoming query string values for Url

## [1.5.2]
### Fixed
- SessionManager::delCookie(...) was not correctly "appizing" name variable, which was causing it not to actually delete the cookie
- Removed unnecessary Profiles::getCurrentProfile(), which is redundant Profiles::getProvider()
- Profiles::getCurrentProfileName() was incorrectly calling the provider's getProviders() method instead of getCurrentProfileName()
## [1.5.1]
### Fixed
- Issue with Control::_legacyInit(...) where old m_* properties were
not being set correctly because isset($this->$var_name) would return
false, even if the object property were defined.
- ControlManager::initialize() to initialize all auto loaded files
before beginning the control attachment process.  Otherwise nested auto loaded files were not being initialized correctly.

## [1.5.0]
### Added
- Ability to specify a callable for configuration values so that the configured valuable can be calculated at runtime

## [1.4.3]
### Added
- Added Controls::addText(...) as a shortcut to having to call Controls::addControl(new Literal(array('text' => 'the text'))) to add simple text to page.
## [1.4.2]
### Fixed
- Issues with LinkControl where "href" parameter value was not being exported to the parameter string correctly.
### Added
- Control::getValueForParamString(...) to allow inheriting classes to override a value that is exported as a parameter string.    
## [1.4.1]
### Added
- Added support for profile priorities
- Added support for profile domain mapping and domain association
### Changed
- Changed IProfile so that they now have access to the profile manager that is managing them
 
## [1.4.0]
### Changed
- Profile related functionality has been renamed/moved to WebImage\ExperienceManager\ProfileManager

### Added
- Added ProviderBase::setName(...)

### Fixed 
- Modified Dictionary to support instances of Traversable classes, in addition to arrays
- Added missing "public" keyword to ProviderBase methods init(...) and getName()

### Removed
- Legacy calls to ConfigurationManager::legacyInitConfigProfiles(...) and ConfigurationManager::legacyInitConfigDatabase(...)
- Removed old profile classes under libraries\providers\profiles\... Moved to WebImage\ExperienceManager namespace under lib\WebImage\ExperienceManager\...

## [1.3.0]
### Added
- Added autoload feature to FrameworkManager::init() where any files located in the app directory at /config/autoload/*.php are automatically loaded.

## [1.2.6]
### Changed
- Changed WebImage\Core\Dictionary::get(...) signature to add default value when a value cannot be found
### Fixed
- Allow configured pages/pathMappings to match and rewrite multiple paths. Previously matching would stop on first match found, even if the first match did not do anything functional, e.g. map to a specific request handler

## [1.2.5]
### Fixed
- Issue where call to ServiceManager::get(...) with an anonymous function would fail

### Added
- Class WebImage\Core\Collection
- Class WebImage\Core\Dictionary
- Class WebImage\Core\DictionaryField
- Class WebImage\Core\DictionaryFieldCollection
- Interface WebImage\Core\ICollection
- Class WebImage\String\Url

## [1.2.4]
### Changed
- Changed autoloader to load from any path returned by PathManager::getPaths(), rather than just the framework "base" directory.
- Changed the autoloader to potentially load any namespace, not just "WebImage"

## [1.2.3]
### Fixed
- Consolidated duplicate LinkControl::init() methods

## [1.2.2]
### Changed
- Updated site creation process so that cli\sites\create.php is called from the "app" directory in order for the new app to be created.
### Fixed
- Fixed control header bar CSS floating issue
- Correct HtmlControl::init(), which was marked as public instead of protected

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
