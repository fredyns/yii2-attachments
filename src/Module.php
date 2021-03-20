<?php

namespace fredyns\attachments;

use fredyns\attachments\models\File;
use Yii;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\i18n\PhpMessageSource;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'fredyns\attachments\controllers';

    public $storePath = '@app/uploads/store';

    public $tempPath = '@app/uploads/temp';

    public $rules = [];

    public $tableName = 'attach_file';

    public $filesystem = 'awss3Fs';

    public function init()
    {
        parent::init();

        if (empty($this->filesystem) || empty($this->storePath) || empty($this->tempPath)) {
            throw new Exception('Setup {filesystem}, {storePath} and {tempPath} in module properties');
        }

        $this->rules = ArrayHelper::merge(['maxFiles' => 3], $this->rules);
        $this->defaultRoute = 'file';
        $this->registerTranslations();
    }

    public function registerTranslations()
    {
        \Yii::$app->i18n->translations['fredyns/*'] = [
            'class' => PhpMessageSource::className(),
            'sourceLanguage' => 'en',
            'basePath' => '@vendor/fredyns/yii2-attachments/src/messages',
            'fileMap' => [
                'fredyns/attachments' => 'attachments.php'
            ],
        ];
    }

    public static function t($category, $message, $params = [], $language = null)
    {
        return \Yii::t('fredyns/' . $category, $message, $params, $language);
    }

    /**
     * @var \creocoder\flysystem\Filesystem|null
     */
    private $_flysystem = null;

    /**
     * @return null|Module
     * @throws \Exception
     */
    public function getFlysystem()
    {
        if ($this->_flysystem == null) {
            $this->_flysystem = \Yii::$app->getModule($this->filesystem);
        }

        if (!$this->_flysystem) {
            throw new \Exception("Filesystem '{$this->filesystem}' module not found, may be you didn't add it to your config?");
        }

        return $this->_flysystem;
    }

    public function getStorePath()
    {
        return \Yii::getAlias($this->storePath);
    }

    public function getTempPath()
    {
        return \Yii::getAlias($this->tempPath);
    }

    /**
     * @param $fileHash
     * @return string
     */
    public function getFilesDirPath($fileHash)
    {
        $path = $this->getStorePath() . DIRECTORY_SEPARATOR . $this->getSubDirs($fileHash);

        FileHelper::createDirectory($path);

        return $path;
    }

    public function getS3DirPath($fileHash)
    {
        return $this->getSubDirs($fileHash);
    }

    public function getSubDirs($fileHash, $depth = 3)
    {
        $depth = min($depth, 9);
        $path = '';

        for ($i = 0; $i < $depth; $i++) {
            $folder = substr($fileHash, $i * 3, 2);
            $path .= $folder;
            if ($i != $depth - 1) $path .= DIRECTORY_SEPARATOR;
        }

        return $path;
    }

    public function getUserDirPath()
    {
        \Yii::$app->session->open();

        $userDirPath = $this->getTempPath() . DIRECTORY_SEPARATOR . \Yii::$app->session->id;
        FileHelper::createDirectory($userDirPath);

        \Yii::$app->session->close();

        return $userDirPath . DIRECTORY_SEPARATOR;
    }

    public function getShortClass($obj)
    {
        $className = get_class($obj);
        if (preg_match('@\\\\([\w]+)$@', $className, $matches)) {
            $className = $matches[1];
        }
        return $className;
    }

    /**
     * @param string $filePath
     * @param ActiveRecord $owner
     * @return bool|File
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function attachFile($filePath, $owner)
    {
        if (empty($owner->id)) {
            throw new Exception('Parent model must have ID when you attaching a file');
        }
        if (!file_exists($filePath)) {
            throw new Exception("File $filePath not exists");
        }

        $fileHash = md5(microtime(true) . $filePath);
        $fileType = pathinfo($filePath, PATHINFO_EXTENSION);
        $newFileName = "$fileHash.$fileType";

        // copy to flysystem
        $s3DirPath = $this->getS3DirPath($fileHash);
        $s3FilePath = $s3DirPath . DIRECTORY_SEPARATOR . $newFileName;
        $stream = fopen($filePath, 'r+');
        $this->getFlysystem()->write("attachments" . DIRECTORY_SEPARATOR . $s3FilePath, $stream);
        fclose($stream);

        $file = new File();
        $file->name = pathinfo($filePath, PATHINFO_FILENAME);
        $file->model = $this->getShortClass($owner); // saving its class name, not table name
        $file->itemId = $owner->id;
        $file->hash = $fileHash;
        $file->size = filesize($filePath);
        $file->type = $fileType;
        $file->mime = FileHelper::getMimeType($filePath);

        if ($file->save()) {
            unlink($filePath);
            return $file;
        } else {
            return false;
        }
    }

    public function detachFile($id)
    {
        /** @var File $file */
        $file = File::findOne(['id' => $id]);
        if (empty($file)) {
            return false;
        }

        // delete file from s3
        $s3Path = $this->getS3DirPath($file->hash) . DIRECTORY_SEPARATOR . $file->hash . '.' . $file->type;
        $s3Exists = $this->getFlysystem()->has("attachments" . DIRECTORY_SEPARATOR . $s3Path);
        if ($s3Exists) {
            $this->getFlysystem()->delete("attachments" . DIRECTORY_SEPARATOR . $s3Path);
        }

        return $file->delete();
    }
}
