# Document Management Module (DMS)

## Overview

This is a very simplified version of the [Silverstripe 3 DMS module](https://github.com/silverstripe/silverstripe-dms) for SS4. 

It uses the standard Silverstripe `File` objects instead fo the custom storage management of the SS3 version. 

Taxonomy has been removed as a dependency. This can be implemented on a project basis if needed.

## Features

 * Relation of documents to pages
 * Management and upload of documents within a page context in the CMS
 * Based on standard Silverstripe files
 * Legacy download controller for SS3 document links

## Requirements

SilverStripe CMS ^4.1, see [composer.json](composer.json)

## Installation

Install the module using composer:

```
composer require innoweb/silverstripe-dms dev-master
```

Then run dev/build.

## Upgrading

A first `dev/build` will move all old DMS tables to `_obsolete_`. 
There is an upgrade task `dev/tasks/dms-upgrade` available that will migrate the old documents to the new structures.

## Issues

Document versions are not being migrated over at the moment. The default versioning of files doesn't keep the physical versions of the 
files. That can be enabled with:

```
SilverStripe\Assets\File:
  keep_archived_assets: true
```

But then this would not only be for DMS documents but for all files. PRs welcome.
