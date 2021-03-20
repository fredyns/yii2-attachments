<?php

namespace fredyns\attachments\controllers;

use fredyns\attachments\models\File;
use fredyns\attachments\models\UploadForm;
use fredyns\attachments\ModuleTrait;
use Yii;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class FileController extends Controller
{
    use ModuleTrait;

    public function actionUpload()
    {
        $model = new UploadForm();
        $model->file = UploadedFile::getInstances($model, 'file');

        if ($model->rules()[0]['maxFiles'] == 1 && sizeof($model->file) == 1) {
            $model->file = $model->file[0];
        }

        if ($model->file && $model->validate()) {
            $result['uploadedFiles'] = [];
            if (is_array($model->file)) {
                foreach ($model->file as $file) {
                    $path = $this->getModule()->getUserDirPath() . DIRECTORY_SEPARATOR . $file->name;
                    $file->saveAs($path);
                    $result['uploadedFiles'][] = $file->name;
                }
            } else {
                $path = $this->getModule()->getUserDirPath() . DIRECTORY_SEPARATOR . $model->file->name;
                $model->file->saveAs($path);
                $result['uploadedFiles'][] = $model->file->name;
            }
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $result;
        } else {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return [
                'error' => $model->getErrors('file')
            ];
        }
    }

    public function actionDownload($id)
    {
        /* @var $file File */
        $file = File::findOne(['id' => $id]);

        // search in s3
        $s3Exists = $this->getModule()->getFlysystem()->has($file->getFlyPath());
        if ($s3Exists) {
            $content = $this->getModule()->getFlysystem()->read($file->getFlyPath());
            return Yii::$app->response->sendContentAsFile($content, "$file->name.$file->type");
        }

        throw new NotFoundHttpException();
    }

    public function actionDelete($id)
    {
        if ($this->getModule()->detachFile($id)) {
            return true;
        } else {
            return false;
        }
    }

    public function actionDownloadTemp($filename)
    {
        $filePath = $this->getModule()->getUserDirPath() . DIRECTORY_SEPARATOR . $filename;

        return Yii::$app->response->sendFile($filePath, $filename);
    }

    public function actionDeleteTemp($filename)
    {
        $userTempDir = $this->getModule()->getUserDirPath();
        $filePath = $userTempDir . DIRECTORY_SEPARATOR . $filename;
        unlink($filePath);
        if (!sizeof(FileHelper::findFiles($userTempDir))) {
            rmdir($userTempDir);
        }

        Yii::$app->response->format = Response::FORMAT_JSON;
        return [];
    }
}
