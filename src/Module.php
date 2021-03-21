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

    public $tempPath = '@app/uploads/temp';

    public $rules = [];

    public $tableName = 'attach_file';

    public $filesystem = 'awss3Fs';

    public $directory = null;

    public function init()
    {
        parent::init();

        if (empty($this->filesystem)) {
            throw new Exception('Setup {filesystem} in module properties');
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
            $this->_flysystem = \Yii::$app->{$this->filesystem};
        }

        if (!$this->_flysystem) {
            throw new \Exception("Filesystem '{$this->filesystem}' module not found, may be you didn't add it to your config?");
        }

        return $this->_flysystem;
    }

    public function getTempPath()
    {
        return \Yii::getAlias($this->tempPath);
    }

    public function getUserDirPath()
    {
        \Yii::$app->session->open();

        $userDirPath = $this->getTempPath() . DIRECTORY_SEPARATOR . \Yii::$app->session->id;
        FileHelper::createDirectory($userDirPath);

        \Yii::$app->session->close();

        return $userDirPath . DIRECTORY_SEPARATOR;
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
            throw new Exception("File '{$filePath}' not exists");
        }

        // compose record
        $file = File::compose($owner, $filePath);

        // ensure unique name
        $baseName = $file->name;
        $i = 0;
        while ($this->getFlysystem()->has($file->getFlyPath())) {
            $file->name = $baseName . '_' . (++$i);
        }

        // copy to flysystem
        $stream = fopen($filePath, 'r+');
        $this->getFlysystem()->write($file->getFlyPath(), $stream);
        fclose($stream);

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
        $s3Exists = $this->getFlysystem()->has($file->getFlyPath());
        if ($s3Exists) {
            $this->getFlysystem()->delete($file->getFlyPath());
        }

        return $file->delete();
    }
}
