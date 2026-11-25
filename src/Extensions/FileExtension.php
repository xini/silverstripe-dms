<?php

namespace Innoweb\DMS\Extensions;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;

class FileExtension extends Extension
{
    private static array $db = [
        "Description" => 'Text',
        'OriginalDMSDocumentIDFile' => 'Int'
    ];

    private static array $casting = [
        'DescriptionWithLineBreaks' => 'HTMLText'
    ];

    public function getDescriptionWithLineBreaks(): string
    {
        return nl2br($this->getOwner()->Description);
    }

    public function DMSDownloadLink(): ?string
    {
        if ($this->getOwner()->exists() && $this->getOwner()->canView()) {
            return Controller::join_links('dms', $this->getOwner()->ID . '-' . $this->getOwner()->Name);
        }
        return null;
    }
}
