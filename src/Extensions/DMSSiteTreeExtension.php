<?php

/**
 * @package dms
 */
namespace Innoweb\DMS\Extensions;

use Innoweb\DMS\Model\DMSDocumentSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class DMSSiteTreeExtension extends DataExtension
{
    private static $has_many = [
        'DocumentSets' => DMSDocumentSet::class
    ];

    public function updateCMSFields(FieldList $fields)
    {
        // Ability to disable document sets for a Page
        if (!$this->owner->config()->get('documents_enabled')) {
            return;
        }

        // Hides the DocumentSets tab if the user has no permisions
        if (!Permission::checkMember(
            Security::getCurrentUser(),
            ['ADMIN', 'CMS_ACCESS_DMSDocumentAdmin']
        )
        ) {
            return;
        }

        $gridField = GridField::create(
            'DocumentSets',
            false,
            $this->owner->DocumentSets(),
            $config = new GridFieldConfig_RelationEditor()
        );

        // sort document sets
        $config->addComponent(GridFieldOrderableRows::create('Sort'));
        // Only show document sets in the autocompleter that have not been assigned to a page already
        $config->getComponentByType(GridFieldAddExistingAutocompleter::class)->setSearchList(
            DMSDocumentSet::get()->filter(['PageID' => 0])
        );

        $fields->addFieldToTab(
            'Root.DocumentSets',
            $gridField
        );

        $fields
            ->findOrMakeTab('Root.DocumentSets')
            ->setTitle(_t(
                __CLASS__ . '.DocumentSetsTabTitle',
                'Document Sets ({count})',
                ['count' => $this->owner->DocumentSets()->count()]
            ));
    }

    /**
     * Get a list of document sets for the owner page
     *
     * @return ArrayList
     */
    public function getFilteredDocumentSets()
    {
        $result = ArrayList::create();
        foreach ($this->owner->DocumentSets() as $documentSet) {
            if ($documentSet->getSortedDocuments()->count() > 0) {
                $result->push($documentSet);
            }
        }
        $this->owner->extend('updateFilteredDocumentSets', $result);
        return $result;
    }

    /**
     * Get a list of all documents from all document sets for the owner page
     *
     * @return ArrayList
     */
    public function getAllDocuments()
    {
        $documents = ArrayList::create();

        foreach ($this->owner->getFilteredDocumentSets() as $documentSet) {
            /** @var DocumentSet $documentSet */
            $documents->merge($documentSet->getSortedDocuments());
        }
        $documents->removeDuplicates();

        return $documents;
    }
}
