<?php

namespace Innoweb\DMS\Model;

use Bummzack\SortableFile\Forms\SortableUploadField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use UncleCheese\DisplayLogic\Forms\Wrapper;

/**
 * A document set is attached to Pages, and contains many DMSDocuments
 *
 * @property Varchar Title
 * @property  Enum SortBy
 * @property Enum SortByDirection
 */
class DMSDocumentSet extends DataObject
{

    private static $table_name = 'DMSDocumentSet';

    private static $singular_name = 'DMS Document Set';

    private static $plural_name = 'DMS Document Sets';

    private static $db = [
        'Title' => 'Varchar(255)',
        'SortBy' => 'Enum(array("LastEdited","Created","Title","Manual"), "LastEdited")',
        'SortByDirection' => 'Enum(array("DESC","ASC"), "DESC")',
        'Sort' => 'Int',
    ];

    private static $has_one = [
        'Page' => SiteTree::class,
    ];

    private static $many_many = [
        'Documents' => File::class,
    ];

    private static $many_many_extraFields = [
        'Documents' => [
            // Flag indicating if a document was added directly to a set - in which case it is set - or added
            // via the query-builder.
            'ManuallyAdded' => 'Boolean(1)',
            'DocumentSort' => 'Int'
        ],
    ];

    private static $owns = [
        'Documents',
    ];

    private static $summary_fields = [
        'Title' => 'Title',
        'Documents.Count' => 'No. Documents'
    ];

    private static $field_labels = [
        'Title' => 'Title',
        'SortBy' => 'Sort Documents By',
        'SortByDirection' => 'Sort Direction',
    ];

    private static $defaults = [
        'SortBy' => 'LastEdited',
        'SortByDirection' => 'DESC',
    ];

    private static $default_sort = "Sort ASC";

    private static $dms_document_folder = 'dms-documents';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'SortBy',
            'SortByDirection',
            'Sort',
            'PageID',
            'Documents'
        ]);

        // add sorting options
        $fields->addFieldsToTab(
            'Root.Main',
            [
                OptionsetField::create(
                    'SortBy',
                    $this->fieldLabel('SortBy'),
                    DMSDocumentSet::singleton()->dbObject('SortBy')->enumValues()
                ),
                $direction = Wrapper::create(OptionsetField::create(
                    'SortByDirection',
                    $this->fieldLabel('SortByDirection'),
                    [
                        'DESC' =>  _t(__CLASS__ . '.DESC', 'Descending'),
                        'ASC' =>  _t(__CLASS__ . '.ASC', 'Ascending')
                    ]
                )),
                $info = Wrapper::create(LiteralField::create(
                    'SortByChangeInfo',
                    '<p class="alert alert-warning">Please save changes to update the interface.</p>'
                )),
            ]
        );
        $direction->hideIf('SortBy')->isEqualTo('Manual');
        $info->hideIf('SortBy')->isEqualTo($this->SortBy);

        // add upload field
        if ($this->isInDB()) {
            if ($this->SortBy == 'Manual') {
                $uploadField = SortableUploadField::create(
                    'Documents',
                    _t(__CLASS__ . '.Documents', 'Documents'),
                    $this->getSortedDocuments()
                );
            } else {
                $uploadField = UploadField::create(
                    'Documents',
                    _t(__CLASS__ . '.Documents', 'Documents'),
                    $this->getSortedDocuments()
                );
            }
            $fields->addFieldToTab(
                'Root.Documents',
                $uploadField
                    ->setFolderName(static::$dms_document_folder)
                    ->setAllowedFileCategories('document')
            );
        } else {
            $fields->addFieldToTab(
                'Root.Documents',
                LiteralField::create('DocumentsInfo', '<p class="alert alert-info">Please save this Document Set to add Documents.</p>')
            );
        }
        // add doc count to tab title
        $fields
            ->findOrMakeTab('Root.Documents')
            ->setTitle(_t(
                __CLASS__ . '.DocumentsTabTitle',
                'Documents ({count})',
                ['count' => $this->Documents()->count()]
            ));

        return $fields;
    }


    /**
     * Retrieve sorted documents in this set.
     *
     * @return DataList|null
     */
    private function getSortedDocuments()
    {
        $documents = $this->Documents()
            ->sort([
                ($this->SortBy == 'Manual' ? "DocumentSort" : $this->SortBy) => ($this->SortBy == 'Manual' ? "ASC" : $this->SortByDirection)
            ]);
        return $documents;
    }

    /**
     * Retrieve filtered documents in this set. An extension hook is provided before the result is returned.
     *
     * @return DataList|null
     */
    public function getFilteredDocuments()
    {
        $documents = $this->Documents()
            ->filterByCallback(function($item, $list) {
                return ($item->canView());
            })
            ->sort([
                ($this->SortBy == 'Manual' ? "DocumentSort" : $this->SortBy) => ($this->SortBy == 'Manual' ? "ASC" : $this->SortByDirection)
            ]);
        $this->extend('updateFilteredDocuments', $documents);
        return $documents;
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canView($member = null)
    {
        if ($this->Page()) {
            return $this->Page()->canView($member);
        }
        return false;
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        if ($this->Page()) {
            return $this->Page()->canEdit($member);
        }
        return false;
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        if ($this->Page()) {
            return $this->Page()->canDelete($member);
        }
        return false;
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        if ($this->Page()) {
            return $this->Page()->canCreate($member);
        }
        return false;
    }

    /**
     * Renames old DMS tables to _obsolete_[table] to prevent dev/build errors.
     * Documents can be migrated to the new structures using {@link Silverstripe4MigrationTask}
     *
     * {@inheritDoc}
     * @see \SilverStripe\ORM\DataObject::requireTable()
     */
    public function requireTable()
    {
        $checkTable = DB::query("SHOW TABLES LIKE 'DMSDocument_versions'")->numRecords();
        if ($checkTable > 0) {
            $checkField = DB::query("SHOW COLUMNS FROM `DMSDocument_versions` LIKE 'VersionCounter'")->numRecords();
            if ($checkField > 0) {
                // make tables obsolete
                $dbSchema = DB::get_schema();
                $dbSchema->schemaUpdate(function () {
                    return true;
                });
                if (!$dbSchema->hasTable('_obsolete_DMSDocument_versions')) {
                    DB::dont_require_table('DMSDocument_versions');
                }
                if (!$dbSchema->hasTable('_obsolete_DMSDocument')) {
                    DB::dont_require_table('DMSDocument');
                }
                if (!$dbSchema->hasTable('_obsolete_DMSDocument_EditorGroups')) {
                    DB::dont_require_table('DMSDocument_EditorGroups');
                }
                if (!$dbSchema->hasTable('_obsolete_DMSDocument_ViewerGroups')) {
                    DB::dont_require_table('DMSDocument_ViewerGroups');
                }
                if (!$dbSchema->hasTable('_obsolete_DMSDocument_Tags')) {
                    DB::dont_require_table('DMSDocument_Tags');
                }
                if (!$dbSchema->hasTable('_obsolete_DMSDocument_RelatedDocuments')) {
                    DB::dont_require_table('DMSDocument_RelatedDocuments');
                }
                DB::alteration_message("Archived SS3 DMS tables to _obsolete_[table]", "obsolete");
            }
        }
        parent::requireTable();
    }
}
