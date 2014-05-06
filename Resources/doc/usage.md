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


Using AdminGeneratorGenerator
-----------------------------

There are several options working out of the box with the AdminGeneratorGeneratorBundle.

```yml

generator: admingenerator.generator.propel
params:
    model: my\Bundle\Model\Person
    namespace_prefix: my
    concurrency_lock: ~
    bundle_name: Bundle
    pk_requirement: ~
    fields:
        # Provide single upload for a dedicated field specified in the behavior
        AvatarFile:
            dbType:     BLOB
            formType:   single_upload
            addFormOptions:
                # previewFilter requires the name of a liip_imagine or similar image converter filter
                previewFilter:  gallery_thumb
        
        # Embed collection upload for all attachments assigned to a single object, including specific ones.
        AllAttachmentsCollection:
            dbType:     collection
            formType:   collection_upload
            addFormOptions:
                # if nameable is set to true, the user may save a custom text for each uploaded file.
                nameable:           true
                # images only
                acceptFileTypes:    /^image\/(gif|jpeg|png)$/
                # all files
                acceptFileTypes:    /^.*$/
                # set width and height for the preview images
                previewMaxWidth:    80
                previewMaxHeight:   60
                allow_add:          true
                allow_delete:       true
                error_bubbling:     false
                
                # by_reference must be kept false
                by_reference:       false
                type:               \c33s\AttachmentBundle\Form\Type\Attachment\EditType
                options:
                    data_class:     c33s\AttachmentBundle\Model\Attachment

        # Embed collection upload for all attachments assigned to a single object, excluding specific ones ("avatar" and "icon" in the above example)
        GeneralAttachmentsCollection:
            dbType:     collection
            formType:   collection_upload
            addFormOptions:
                # if nameable is set to true, the user may save a custom text for each uploaded file.
                nameable:           true

                # images only
                acceptFileTypes:    /^image\/(gif|jpeg|png)$/
                # all files
                acceptFileTypes:    /^.*$/

                # set width and height for the preview images
                previewMaxWidth:    80
                previewMaxHeight:   60
                allow_add:          true
                allow_delete:       true
                error_bubbling:     false
                
                # by_reference must be kept false
                by_reference:       false
                type:               \c33s\AttachmentBundle\Form\Type\Attachment\EditType
                options:
                    data_class:     c33s\AttachmentBundle\Model\Attachment


builders:
    edit:
        params:
            title: "You're editing the object \"%object%\"|{ %object%: Person.title }|"
            display:
                - Name
                # use in edit forms
                - AvatarFile
                - AllAttachmentsCollection
                - GeneralAttachmentsCollection
                
```

For more configuration options check out [`avocode/form-extensions-bundle`](https://github.com/avocode/FormExtensions).
Much was possible after understanding [this issue documentation](https://github.com/avocode/FormExtensions/issues/32).
