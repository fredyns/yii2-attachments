<?php
/**
 * Created by PhpStorm.
 * User: Alimzhan
 * Date: 2/6/2016
 * Time: 10:29 PM
 */

use tests\models\Comment;

/** @var $model Comment */

echo \fredyns\attachments\components\AttachmentsInput::widget([
    'model' => $model
]);
