<?php

namespace Innoweb\DMS\Tasks;

use Innoweb\DMS\Model\DMSDocumentSet;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

class Silverstripe4MigrationTask extends BuildTask
{

    protected $enabled = true;

    protected $title = 'DMS Upgrade to SS4';

    protected $description = 'Upgrade the DMS to SS4';

    private static $segment = 'dms-upgrade';

    private static $old_dms_folder = 'assets/_dmsassets';

    public function run($request)
    {
        $this->log("Starting upgrade...", 2);

        set_time_limit(0);

        $this->migrateDocuments();

        $this->log("Upgrade done.");
    }

    /**
     * Migrate DMS documents to new structures
     * - read old documents
     * - create and save new files
     * - update DocumentSets
     */
    private function migrateDocuments()
    {
        // check if old table exists
        $checkTable = DB::query("SHOW TABLES LIKE '_obsolete_DMSDocument'")->numRecords();
        if ($checkTable > 0) {
            // check that it's a SS3 version of the table
            $checkField = DB::query("SHOW COLUMNS FROM `_obsolete_DMSDocument` LIKE 'CanEditType'")->numRecords();
            if ($checkField > 0) {
                $rows = DB::query('SELECT * FROM "_obsolete_DMSDocument";');

                $this->log("found " . $rows->numRecords() . " records", 2);

                foreach ($rows as $row) {

                    $this->log("migrating " . $row['Filename'] . "... ");

                    // make sure file doesn't exist yet
                    if (File::get()->filter(['OriginalDMSDocumentIDFile' => $row['ID']])->count() == 0) {

                        // get old file path
                        $oldFilePath = PUBLIC_PATH. DIRECTORY_SEPARATOR . self::config()->old_dms_folder . DIRECTORY_SEPARATOR . $row['Folder'] . DIRECTORY_SEPARATOR . $row['Filename'];
                        if (file_exists($oldFilePath)) {

                            $this->log("old file exists, copying... ", 0);

                            $newFolder = Folder::find_or_make(DMSDocumentSet::config()->dms_document_folder);

                            $newFile = File::create();
                            $newFile->Created = $row['Created'];
                            $newFile->LastEdited = $row['LastEdited'];
                            $newFile->Name = $row['Filename'];
                            $newFile->Title = $row['Title'];
                            $newFile->Description = $row['Description'];
                            $newFile->CreatedByID = $row['CreatedByID'];
                            $newFile->LastEditedByID = $row['LastEditedByID'];
                            $newFile->CanViewType = $row['CanViewType'];
                            $newFile->CanEditType = $row['CanEditType'];
                            $newFile->OriginalDMSDocumentIDFile = $row['ID'];
                            $newFile->write();

                            $newFile->generateFilename();
                            $newFile->setFromLocalFile($oldFilePath, $this->getFilenameWithoutID($row['Filename']));
                            $newFile->ParentID = $newFolder->ID;
                            $newFile->write();

                            $newFile->publishRecursive();

                            $this->log("done.");

                            $this->log("updating document sets... ", 0);

                            DB::query("UPDATE DMSDocumentSet_Documents SET FileID = " . $newFile->ID . " WHERE DMSDocumentID = " . $row['ID']);

                            $this->log("done.", 2);
                        } else {
                            $this->log("skipping, old file does not exist: " . $oldFilePath, 2);
                        }
                    } else {
                        $this->log("skipping, already migrated.", 2);
                    }
                }
            } else {
                $this->log("Nothing to migrate.", 2);
            }
        } else {
            $this->log("Nothing to migrate.", 2);
        }
    }

    protected function getFilenameWithoutID($filename)
    {
        $parts = (array) sscanf($filename, '%d~%s');
        $name = array_pop($parts);
        if (is_string($name)) {
            return $name;
        }
    }

    protected function log($message, $newLines = 1)
    {
        if (Director::is_cli()) {
            echo "{$message}" . str_repeat("\n", $newLines);
        } else {
            echo "{$message}" . str_repeat("<br />", $newLines);
        }
        flush();
    }
}
