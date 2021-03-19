<?php

namespace fredyns\attachments\events;

use yii\base\Event;

class FileEvent extends Event
{
    /**
     * @var fredyns\attachments\models\File[]
     */
    private $_files;

    /**
     * @return fredyns\attachments\models\File[]
     */
    public function getFiles()
    {
        return $this->_files;
    }

    /**
     * @param fredyns\attachments\models\File[] $files
     */
    public function setFiles($files)
    {
        $this->_files = $files;
    }
}
