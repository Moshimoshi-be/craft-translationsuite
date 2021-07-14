# Translation Suite Changelog

## 1.0.6 - Not released
### Added
- Added possibility to export translations back to static php files. 
  This way you can easily export translations from a staging/production environment so that you can put your translations back in git.

## 1.0.5 - 2021-07-14
### Added
- Import translations from excel or csv.

### Fixed
- Fixed an issue when adding translations for languages that are not added yet. 
  This happened when adding extra languages after already translating the message.

## 1.0.4 - 2021-07-11
### Added
- Export the translations to csv or Excel files
### Changed
- Allows the user to save missing translations that are used but not found
- Removed third party translations like Yii, App, other plugins, etc

## 1.0.3 - 2021-07-09
### Changed
- Reset the pagination when searching
- Changed paginator step to 15 translations per page.
- Fixed an issue that didn't allow you to see the last page of translations in some cases.

## 1.0.2 - 2021-07-06
### Changed
- Updated the documentation urls and the composer description
- Decreased the time for detecting changes even further.

## 1.0.1 - 2021-07-06
### Changed
- Added less time for detecting changes after the user stops typing

## 1.0.0 - 2021-07-06
### Added
- Initial release