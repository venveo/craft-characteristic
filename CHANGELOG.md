# Characteristic Changelog

## 1.0.0-beta.11 - 6/5/20
### Fixed
- Bug where saving characteristics wasn't working anymore

## 1.0.0-beta.10 - 5/1/20
### Fixed
- Bug where non-admin users could not sort characteristics

### Changed
- Drilldown will no longer show characteristics with only one option for the result set

### Added
- CharacteristicLinkBlocks can now eager-load their values

## 1.0.0-beta.9 - 3/4/20
### Changed
- Added icons

## 1.0.0-beta.8 - 2/26/20
### Fixed
- Issue with drilldown not properly ensuring exclusivity in relation query

## 1.0.0-beta.7 - 2/26/20
### Fixed
- Issue with some relations not being saved and drilldown not working properly
- Drilldown URL would not return an absolute URL
- Issues with relations not being localized properly

### Added
- You can now click on characteristic blocks to jump to the control panel edit screen for that characteristic

## 1.0.0-beta.6 - 2/25/20
### Fixed
- Errors caused by soft-deleting and restoring characteristic elements
- Fixed Drilldown not ignoring deleted characteristics

### Changed
- Minor style improvements

## 1.0.0-beta.5 - 2/24/20
### Fixed
- Issue where non-admin users could not edit characteristics

### Added
- Permissions for each characteristic group

## 1.0.0-beta.4 - 2/13/20
### Changed
- Refactored element structures such that we rely more on native Craft relations and Block Elements
- Rewrote frontend field input to be more maintainable
- Drilldown should be more performant 
- Now requires Craft 3.4
- Start using Vue Admin Table
- Removed characteristic behavior in favor of field
- Improved characteristic field to work more like Matrix fields
- Changed CharacteristicLink to CharacteristicLinkBlock
- Removed respectStructure from drilldown in favor of always respecting the structure
- Characteristics now support multiple propagation methods

### Fixed
- Various improvements to multi-site support
- Erroneous warning about unsaved changes on edit element screens
- Drilldown now respects existing query parameters and paths

## 1.0.0-beta.3 - 1/31/20
### Fixed
- Fixed error when saving drafts

## 1.0.0-beta.2 - 1/29/20
### Fixed
- Fixed error when saving an existing group ([#40](https://github.com/venveo/craft-characteristic/issues/40))

## 1.0.0-beta.1 - 1/28/20
### Added
- Initial release
