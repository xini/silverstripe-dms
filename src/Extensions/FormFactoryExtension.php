<?php

namespace Innoweb\DMS\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;

class FormFactoryExtension extends Extension
{
    public function updateFormFields(FieldList $fields)
    {
        $fields->insertAfter(
            'Title',
            TextareaField::create('Description', _t(DMSDocument::class . '.DESCRIPTION', 'Description'))
        );
    }
}
