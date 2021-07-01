<?php

namespace Innoweb\DMS\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextareaField;

class FormFactoryExtension extends Extension
{
    public function updateFormFields(FieldList $fields, $controller, $formName, $context)
    {
        $descField = TextareaField::create('Description', _t(DMSDocument::class . '.DESCRIPTION', 'Description'));

        $titleField = $fields->fieldByName('Editor.Details.Title');
        if ($titleField) {
            if ($titleField->isReadonly()) {
                $descField = $descField->performReadonlyTransformation();
            }
            $fields->insertAfter('Title', $descField);
        }
    }
}
