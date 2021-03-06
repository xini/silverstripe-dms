<?php

namespace Innoweb\DMS\Controller;

use SilverStripe\Assets\File;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPStreamResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use InvalidArgumentException;

/**
 * Serves the new DMS Document links to /dms/[ID].
 */
class DMSDocumentController extends Controller
{
    /**
     * Mode to switch for testing. Does not return document download, just document URL.
     *
     * @var boolean
     */
    protected static $testMode = false;

    private static $allowed_actions = [
        'index'
    ];

    public function init()
    {
        Versioned::choose_site_stage($this->request);
        parent::init();
    }

    /**
     * Access the file download without redirecting user, so we can block direct
     * access to documents.
     */
    public function index(HTTPRequest $request)
    {
        if ($this->request->getVar('test')) {
            if (Permission::check('ADMIN')) {
                self::$testMode = true;
            }
        }
        $doc = $this->getDocumentFromID($request);

        if (!empty($doc) && $doc->canView()) {
            if (self::$testMode) {
                return $doc->Filename;
            }

            return $this->sendFile($doc);
        }

        if (self::$testMode) {
            return 'This asset does not exist.';
        }

        $this->httpError(404, 'This asset does not exist.');
    }

    /**
     * Returns the document object from the request object's ID parameter.
     * Returns null, if no document found
     *
     * @param  SS_HTTPRequest $request
     * @return DMSDocument|null
     */
    protected function getDocumentFromID($request)
    {
        $doc = null;

        $id = Convert::raw2sql($request->param('ID'));
        $versionID = Convert::raw2sql($request->param('VersionID'));

        // extract numeric ID from slug
        $id = $this->getDocumentIdFromSlug($id);

        if ($id && $versionID) {
            $doc = Versioned::get_version(File::class, intval($id), intval($versionID));
            $this->extend('updateVersionFromID', $doc, $request);
        } elseif ($id) {
            $doc = File::get()->byID(intval($id));
            $this->extend('updateDocumentFromID', $doc, $request);
        }

        return $doc;
    }

    /**
     * Get a document's ID from a "friendly" URL slug containing a numeric ID and slugged title
     *
     * @param  string $slug
     * @return int
     * @throws InvalidArgumentException if an invalid format is provided
     */
    protected function getDocumentIdFromSlug($slug)
    {
        $parts = (array) sscanf($slug, '%d-%s');
        $id = array_shift($parts);
        if (is_numeric($id)) {
            return (int) $id;
        }
        return false;
    }

    /**
     * @param File $file DMS Document
     */
    protected function sendFile(File $file)
    {
        $response = HTTPStreamResponse::create($file->getStream(), $file->getAbsoluteSize());
        $response->addHeader('Content-Type', $file->getMimeType());
        $response->addHeader('Content-Length', $file->getAbsoluteSize());
        $response->addHeader('Content-Disposition', 'attachment; filename="' . $file->Name . '"');
        $response->addHeader('Content-transfer-encoding', '8bit');
        $response->addHeader('Expires', '0');
        $response->addHeader('Pragma', 'cache');
        $response->addHeader('Cache-Control', 'private');
		$this->extend('updateResponse', $response);
        return $response;
    }
}
