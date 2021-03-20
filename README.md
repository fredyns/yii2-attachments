Yii2 Attachments
================
[![Latest Stable Version](https://poser.pugx.org/fredyns/yii2-attachments/v/stable)](https://packagist.org/packages/fredyns/yii2-attachments)
[![License](https://poser.pugx.org/fredyns/yii2-attachments/license)](https://packagist.org/packages/fredyns/yii2-attachments)
[![Total Downloads](https://poser.pugx.org/fredyns/yii2-attachments/downloads)](https://packagist.org/packages/fredyns/yii2-attachments)

Upload models attachment to Flysystem

Demo
----
You can see the demo of upload input on the [krajee](http://plugins.krajee.com/file-input/demo) website

Installation
------------

1. install [yii2-flysystem](https://github.com/creocoder/yii2-flysystem) and filesystem of your choice.

	Please look closely at its documentation for installation. (it may get some update)

	
2. The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

	Either run
	
	```
	php composer.phar require fredyns/yii2-attachments "dev-master"
	```
	
	or add
	
	```
	"fredyns/yii2-attachments": "dev-master"
	```
	
	to the require section of your `composer.json` file.

3.  Add module to `common/config/main.php` (advanced template) 
	
	for basic app you should add to both `config/web.php` & `config/console.php`. 
	
	```php
	'modules' => [
		...
		'attachments' => [
			'class' => fredyns\attachments\Module::class,
			'rules' => [ // Rules according to the FileValidator
			    'maxFiles' => 10, // Allow to upload maximum 3 files, default to 3
				'mimeTypes' => 'image/png', // Only png images
				'maxSize' => 1024 * 1024 // 1 MB
			],
			'tableName' => '{{%attachments}}' // Optional, default to 'attach_file'
			'filesystem' => 'awss3Fs' // you can change though
		]
		...
	]
	```

4. Apply migrations


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

5. Attach behavior to your model (be sure that your model has "id" property)
	
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
	
6. Make sure that you have added `'enctype' => 'multipart/form-data'` to the ActiveForm options
	
7. Make sure that you specified `maxFiles` in module rules and `maxFileCount` on `AttachmentsInput` to the number that you want

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

5. You can get all attached files by calling ```$model->files```, for example:

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
    
