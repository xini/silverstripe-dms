<?php

namespace Innoweb\DMS\Extensions;

use SilverStripe\Control\Controller;
use SilverStripe\ORM\DataExtension;

class FileExtension extends DataExtension
{
    private static $db = [
        "Description" => 'Text',
        'OriginalDMSDocumentIDFile' => 'Int'
    ];

    private static $casting = [
        'DescriptionWithLineBreaks' => 'HTMLText'
    ];

    public function getDescriptionWithLineBreaks()
    {
        return nl2br($this->owner->Description);
    }

    public function DMSDownloadLink()
    {
        if ($this->owner->exists() && $this->owner->canView()) {
            return Controller::join_links('dms', $this->owner->ID . '-' . $this->owner->Name);
        }
        return null;
    }
}
