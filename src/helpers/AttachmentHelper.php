<?php

namespace fredyns\attachments\helpers;

use fredyns\attachments\models\File;
use fredyns\attachments\Module;
use Yii;
use yii\helpers\Inflector;
use yii\db\ActiveRecord;

abstract class AttachmentHelper
{
    /**
     * @var null|Module
     */
    private static $_module = null;

    /**
     * @return null|Module
     * @throws \Exception
     */
    public static function getModule()
    {
        if (static::$_module == null) {
            static::$_module = Yii::$app->getModule('attachments');
        }

        if (!static::$_module) {
            throw new \Exception("Yii2 attachment module not found, may be you didn't add it to your config?");
        }

        return static::$_module;
    }

    /**
     * @param ActiveRecord $model
     * @return string
     */
    public static function getModelLabel($model)
    {
        $class = get_class($model);
        $tableName = $class::tableName();
        $tableName = str_replace('{', '', $tableName);
        $tableName = str_replace('%', '', $tableName);

        return $tableName;
    }

    public static function getNewFileName($filePath)
    {
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        $fileName = Inflector::slug($fileName);

        return $fileName;
    }
}