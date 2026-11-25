# Document Management Module (DMS)

## Overview

This is a very simplified version of the [Silverstripe 3 DMS module](https://github.com/silverstripe/silverstripe-dms) for Silverstripe 6. 

It uses the standard Silverstripe `File` objects instead of the custom storage management of the SS3 version. 

Taxonomy has been removed as a dependency. This can be implemented on a project basis if needed.

## Features

 * Relation of documents to pages
 * Management and upload of documents within a page context in the CMS
 * Based on standard Silverstripe files
 * Legacy download controller for SS3 document links

## Requirements

Silverstripe CMS ^6, see [composer.json](composer.json)

## Installation

Install the module using composer:

```
composer require innoweb/silverstripe-dms dev-master
```

Then run dev/build.

## Document versioning

The default versioning of files doesn't keep the physical versions of the files. That can be enabled with:

```
SilverStripe\Assets\File:
  keep_archived_assets: true
```

This then applies not only be for DMS documents, but for all files.
