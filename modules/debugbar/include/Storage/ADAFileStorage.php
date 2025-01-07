<?php

namespace Lynxlab\ADA\Module\DebugBar\Storage;

use DebugBar\Storage\FileStorage;

class ADAFileStorage extends FileStorage
{
    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (file_exists($this->makeFilename($id))) {
            return parent::get($id);
        }
        return [];
    }
}
