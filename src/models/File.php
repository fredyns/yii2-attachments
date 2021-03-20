<?php

namespace fredyns\attachments\models;

use fredyns\attachments\helpers\AttachmentHelper;
use fredyns\attachments\ModuleTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\helpers\Url;

/**
 * This is the model class for table "attach_file".
 *
 * @property integer $id
 * @property string $name
 * @property string $model
 * @property integer $itemId
 * @property string $hash
 * @property integer $size
 * @property string $type
 * @property string $mime
 */
class File extends ActiveRecord
{
    use ModuleTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return Yii::$app->getModule('attachments')->tableName;
    }

    /**
     * @param ActiveRecord $owner
     * @param string $filePath
     * @return File
     */
    public static function compose($owner, $filePath)
    {
        $file = new static();
        $file->name = AttachmentHelper::getNewFileName($filePath);
        $file->model = AttachmentHelper::getModelLabel($owner); // saving its class name, not table name
        $file->itemId = $owner->id;
        $file->hash = md5(microtime(true) . $filePath);
        $file->size = filesize($filePath);
        $file->type = pathinfo($filePath, PATHINFO_EXTENSION);
        $file->mime = FileHelper::getMimeType($filePath);

        return $file;
    }

    /**
     * @inheritDoc
     */
    public function fields()
    {
        return [
            'url'
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'model', 'itemId', 'hash', 'size', 'mime'], 'required'],
            [['itemId', 'size'], 'integer'],
            [['name', 'model', 'hash', 'type', 'mime'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'model' => 'Model',
            'itemId' => 'Item ID',
            'hash' => 'Hash',
            'size' => 'Size',
            'type' => 'Type',
            'mime' => 'Mime'
        ];
    }

    public function getUrl()
    {
        return Url::to(['/attachments/file/download', 'id' => $this->id]);
    }

    public function getFlyDir()
    {
        $dirArray = AttachmentHelper::getModule()->directory ? [AttachmentHelper::getModule()->directory] : [];
        $dirArray[] = $this->model;
        $dirArray[] = $this->itemId;

        return implode(DIRECTORY_SEPARATOR, $dirArray);
    }

    public function getFlyPath()
    {
        if (empty($this->_flyPath)) {
            $this->_flyPath = $this->getFlyDir() . DIRECTORY_SEPARATOR . $this->name . '.' . $this->type;
        }

        return $this->_flyPath;
    }

    private $_flyPath;
}
