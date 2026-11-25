<?php

namespace Innoweb\DMS\Model;

use Bummzack\SortableFile\Forms\SortableUploadField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Assets\File;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
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

    private static string $table_name = 'DMSDocumentSet';

    private static string $singular_name = 'DMS Document Set';

    private static string $plural_name = 'DMS Document Sets';

    private static array $db = [
        'Title' => 'Varchar(255)',
        'SortBy' => 'Enum(array("LastEdited","Created","Title","Manual"), "LastEdited")',
        'SortByDirection' => 'Enum(array("DESC","ASC"), "DESC")',
        'Sort' => 'Int',
    ];

    private static array $has_one = [
        'Page' => SiteTree::class,
    ];

    private static array $many_many = [
        'Documents' => File::class,
    ];

    private static array $many_many_extraFields = [
        'Documents' => [
            // Flag indicating if a document was added directly to a set - in which case it is set - or added
            // via the query-builder.
            'ManuallyAdded' => 'Boolean(1)',
            'DocumentSort' => 'Int'
        ],
    ];

    private static array $owns = [
        'Documents',
    ];

    private static array $summary_fields = [
        'Title' => 'Title',
        'Documents.Count' => 'No. Documents'
    ];

    private static array $field_labels = [
        'Title' => 'Title',
        'SortBy' => 'Sort Documents By',
        'SortByDirection' => 'Sort Direction',
    ];

    private static array $defaults = [
        'SortBy' => 'LastEdited',
        'SortByDirection' => 'DESC',
    ];

    private static string $default_sort = "Sort ASC";

    private static string $dms_document_folder = 'dms-documents';

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
                        'DESC' =>  _t(self::class . '.DESC', 'Descending'),
                        'ASC' =>  _t(self::class . '.ASC', 'Ascending')
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
                    _t(self::class . '.Documents', 'Documents'),
                    $this->getSortedDocuments()
                )->setSortColumn('DocumentSort');
            } else {
                $uploadField = UploadField::create(
                    'Documents',
                    _t(self::class . '.Documents', 'Documents'),
                    $this->getSortedDocuments()
                );
            }
            $fields->addFieldToTab(
                'Root.Documents',
                $uploadField
                    ->setFolderName(self::config()->get('dms_document_folder'))
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
                self::class . '.DocumentsTabTitle',
                'Documents ({count})',
                ['count' => $this->getDocuments()->count()]
            ));

        return $fields;
    }

    public function getDocuments()
    {
        return $this->getManyManyComponents('Documents');
    }

    public function getPage()
    {
        return $this->getComponent('Page');
    }

    /**
     * Retrieve sorted documents in this set.
     *
     * @return DataList|null
     */
    private function getSortedDocuments()
    {
        return $this->getDocuments()
            ->sort([
                ($this->SortBy == 'Manual' ? "DocumentSort" : $this->SortBy) => ($this->SortBy == 'Manual' ? "ASC" : $this->SortByDirection)
            ]);
    }

    /**
     * Retrieve filtered documents in this set. An extension hook is provided before the result is returned.
     *
     * @return DataList|null
     */
    public function getFilteredDocuments()
    {
        $documents = $this->getDocuments()
            ->filterByCallback(fn($item, $list) => $item->canView())
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
        if ($page = $this->getPage()) {
            return $page->canView($member);
        }
        return parent::canView($member);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        if ($page = $this->getPage()) {
            return $page->canEdit($member);
        }
        return parent::canEdit($member);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        if ($page = $this->getPage()) {
            return $page->canDelete($member);
        }
        return parent::canDelete($member);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        if ($page = $this->getPage()) {
            return $page->canCreate($member, $context);
        }
        return parent::canCreate($member, $context);
    }

    /**
     * Renames old DMS tables to _obsolete_[table] to prevent dev/build errors.
     * Documents can be migrated to the new structures using {@link Silverstripe4MigrationTask}
     *
     * {@inheritDoc}
     * @see \SilverStripe\ORM\DataObject::requireTable()
     */
    public function requireTable(): void
    {
        $checkTable = DB::query("SHOW TABLES LIKE 'DMSDocument_versions'")->numRecords();
        if ($checkTable > 0) {
            $checkField = DB::query("SHOW COLUMNS FROM `DMSDocument_versions` LIKE 'VersionCounter'")->numRecords();
            if ($checkField > 0) {
                // make tables obsolete
                $dbSchema = DB::get_schema();
                $dbSchema->schemaUpdate(fn(): true => true);
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
