How to use AttachmentBundle
===========================

General usage
-------------

Add behavior to your propel models:

```xml
<!-- my/Bundle/Resources/config/schema.xml -->

    <table name="person">
        <behavior name="c33s_attachable">
            <parameter name="single_columns" value="avatar, icon" />
        </behavior>
        
        <...>
    </table>

```

The given example will add the columns `avatar` and `icon` to your `person` model and supply them with cool
file handling functions.

```php

use Symfony\Component\HttpFoundation\File\File;

// the only limitation at the moment is that the object has to be saved before attaching any files to make the soft relation work
$person = new my\Bundle\Model\Person();
$person
    ->setName('first person')
    ->save()
;

// set file using File object. Useful when using Symfony forms
$file = new File($this->get('kernel')->getRootDir() . '/../path/to/avatar.png');
$person->setAvatarFile($file);

// set file using path string. Useful when using fixture files
$person->setAvatarFilePath($this->get('kernel')->getRootDir() . '/../path/to/avatar.png');

// You can also attach File objects using a general-purpose method. This allows adding 
// attachments with different "roles"
$file = new File($this->get('kernel')->getRootDir() . '/../somefile.pdf');
$attachment2 = $person->attachFile($file, 'customType');

// save the object to store references inside the dedicated object fields
$person->save();

```


AdminGeneratorGenerator integration
-----------------------------

Read-to-use Admingeneratorgenerator is available through [`c33s/attachment-admin-bundle`](https://packagist.org/packages/c33s/attachment-admin-bundle)
