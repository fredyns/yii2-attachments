Yii2 attachments
================
[![Latest Stable Version](https://poser.pugx.org/fredyns/yii2-attachments/v/stable)](https://packagist.org/packages/fredyns/yii2-attachments)
[![License](https://poser.pugx.org/fredyns/yii2-attachments/license)](https://packagist.org/packages/fredyns/yii2-attachments)
[![Total Downloads](https://poser.pugx.org/fredyns/yii2-attachments/downloads)](https://packagist.org/packages/fredyns/yii2-attachments)

Extension for file uploading and attaching to the models

Demo
----
You can see the demo on the [krajee](http://plugins.krajee.com/file-input/demo) website

Installation
------------

1. The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

	Either run
	
	```
	php composer.phar require fredyns/yii2-attachments "~1.1.1"
	```
	
	or add
	
	```
	"fredyns/yii2-attachments": "~1.1.1"
	```
	
	to the require section of your `composer.json` file.

2.  Add module to `common/config/main.php`
	
	```php
	'modules' => [
		...
		'attachments' => [
			'class' => fredyns\attachments\Module::class,
			'tempPath' => '@app/uploads/temp',
			'storePath' => '@app/uploads/store',
			'rules' => [ // Rules according to the FileValidator
			    'maxFiles' => 10, // Allow to upload maximum 3 files, default to 3
				'mimeTypes' => 'image/png', // Only png images
				'maxSize' => 1024 * 1024 // 1 MB
			],
			'tableName' => '{{%attachments}}' // Optional, default to 'attach_file'
		]
		...
	]
	```

3.  Add S3 component `web.php`
	
	```php
	return [
	    //...
	    'components' => [
		//...
		'awss3Fs' => [
		    'class' => 'creocoder\flysystem\AwsS3Filesystem',
		    'key' => 'your-key',
		    'secret' => 'your-secret',
		    'bucket' => 'your-bucket',
		    'region' => 'your-region',
		    'endpoint' => 'http://your-region.digitaloceanspaces.com'
		    // 'version' => 'latest',
		    // 'baseUrl' => 'your-base-url',
		    // 'prefix' => 'your-prefix',
		    // 'options' => [],
		],
	    ],
	];
	```

3. Apply migrations


	```php
    	'controllerMap' => [
		...
		'migrate' => [
			'class' => 'yii\console\controllers\MigrateController',
			'migrationNamespaces' => [
				'fredyns\attachments\migrations',
			],
		],
		...
    	],
	```

	```
	php yii migrate/up
	```

4. Attach behavior to your model (be sure that your model has "id" property)
	
	```php
	public function behaviors()
	{
		return [
			...
			'fileBehavior' => [
				'class' => \fredyns\attachments\behaviors\FileBehavior::class,
			]
			...
		];
	}
	```
	
5. Make sure that you have added `'enctype' => 'multipart/form-data'` to the ActiveForm options
	
6. Make sure that you specified `maxFiles` in module rules and `maxFileCount` on `AttachmentsInput` to the number that you want

Usage
-----

1. In the `form.php` of your model add file input
	
	```php
	<?= \fredyns\attachments\components\AttachmentsInput::widget([
		'id' => 'file-input', // Optional
		'model' => $model,
		'options' => [ // Options of the Kartik's FileInput widget
			'multiple' => true, // If you want to allow multiple upload, default to false
		],
		'pluginOptions' => [ // Plugin options of the Kartik's FileInput widget 
			'maxFileCount' => 10 // Client max files
		]
	]) ?>
	```

2. Use widget to show all attachments of the model in the `view.php`
	
	```php
	<?= \fredyns\attachments\components\AttachmentsTable::widget([
		'model' => $model,
		'showDeleteButton' => false, // Optional. Default value is true
	])?>
	```

3. (Deprecated) Add onclick action to your submit button that uploads all files before submitting form
	
	```php
	<?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', [
		'class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary',
		'onclick' => "$('#file-input').fileinput('upload');"
	]) ?>
	```
	
4. You can get all attached files by calling ```$model->files```, for example:

	```php
	foreach ($model->files as $file) {
        echo $file->path;
    }
    ```

Using Events
------------
You may add the following function to your model
    
```php
public function init(){
    $this->on(\fredyns\attachments\behaviors\FileBehavior::EVENT_AFTER_ATTACH_FILES, function ($event) {
        /** @var $files \fredyns\attachments\models\File[] */
        $files = $event->files;
        //your custom code
    });
    parent::init();
}
```
    
