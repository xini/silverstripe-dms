<?php
/**
 * Simple exception extension so that we can tell the difference between internally
 * raised exceptions and those thrown by DMS.
 */
namespace SilverStripeDMS\Exceptions;

class FileNotFoundException extends \Exception
{
}
