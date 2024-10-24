<?php

namespace Lynxlab\ADA\Module\DebugBar\Storage;

use DebugBar\Storage\FileStorage;

class ADAFileStorage extends FileStorage
{
    public function get($id)
    {
        $retval = parent::get($id);
        if (is_file($this->makeFilename($id))) {
            unlink($this->makeFilename($id));
        }
        return $retval;
    }
}
