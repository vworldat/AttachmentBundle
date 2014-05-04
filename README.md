AttachmentBundle
================

Attach files to any (Propel) object using Symfony2

*THIS IS WORK IN PROGRESS! USE AT YOUR OWN RISK!*

Installation
------------

Require [`c33s/attachment-bundle`](https://packagist.org/packages/c33s/attachment-bundle)
in your `composer.json` file:


```js
{
    "require": {
        "c33s/attachment-bundle": "@stable"
    }
}
```

Register the bundle and its dependencies in `app/AppKernel.php`:

    // app/AppKernel.php

    public function registerBundles()
    {
        return array(
            // ...

            new c33s\AttachmentBundle\c33sAttachmentBundle(),
            new Bazinga\Bundle\PropelEventDispatcherBundle\BazingaPropelEventDispatcherBundle(),
            new Knp\Bundle\GaufretteBundle\KnpGaufretteBundle(),
        );
    }

Add propel behaviors to your propel config in `config.yml`:

```yml
propel:
    # ...
    
    behaviors:
        c33s_attachable:                        vendor.c33s.attachment-bundle.c33s.AttachmentBundle.Behavior.C33sPropelBehaviorAttachable
        event_dispatcher:                       vendor.willdurand.propel-eventdispatcher-behavior.src.EventDispatcherBehavior

```

Configure Gaufrette filesystems as defined in [knplabs/knp-gaufrette-bundle](https://github.com/KnpLabs/KnpGaufretteBundle) and c33sAttachmentBundle:

```yml

knp_gaufrette:
    adapters:
        local_base:
            local:
                directory:  '%kernel.root_dir%/../web/storage/'
                
    filesystems:
        web_storage_fs:
            adapter:    local_base

c33s_attachment:
    # Configure knp_gaufrette filesystems to use. Make sure they are also defined in the knp_gaufrette config section.
    storages:
        # Don't use dashes ("-") in your storage names!
        web_storage:
            # Actual name of knp_gaufrette filesystem
            filesystem:     web_storage_fs
            
            # Subfolder / path prefix to use inside the filesystem
            path_prefix:    '' 
            
            # Base url (if available) to reach files stored in this filesystem
            # Can be either absolute path or full URL
            base_url:       /storage
            
        avatar_storage:
            # Actual name of knp_gaufrette filesystem
            filesystem:     web_storage_fs
            
            # Subfolder / path prefix to use inside the filesystem
            path_prefix:    'avatars/'
            
            # Base url (if available) to reach files stored in this filesystem
            # Can be either absolute path or full URL
            base_url:       /storage
            
    attachments:
        # These are the default config values for all attachments. Specific values follow in the sub sections.
        
        # Callable that takes a file path as first argument and returns a hash. Can be a function name or a static class call like ['MyHashClass', 'myMethod']
        hash_callable:  sha1_file
        
        # Number of directory levels to auto-generate
        storage_depth:  3
        
        # Name of the storage to use.
        storage:        web_storage
        
        # Now we have defined the default values we can override some of them as needed
        models:
            
            # Namespace and class name of specific attachable model. This should match the return value of the model's getAttachableClassName() method. 
            my\Super\Model\Person:
                
                # You get the idea ...
                #hash_callable:  sha1_file
                #storage_depth:  3
                #storage:        web_storage
                
                # For any given model you may override specific attachment fields you are using
                fields:
                    
                    # CamelCased field name.
                    Avatar:
                        hash_callable:  md5_file
                        storage_depth:  2
                        storage:        avatar_storage
            
```

Add behavior to your propel models:

```xml
    <table name="person">
        <behavior name="c33s_attachable">
            <parameter name="single_columns" value="avatar, icon" />
        </behavior>
        
        <...>
    </table>
```

The given example will add the columns `avatar` and `icon` to your `person` model and supply them with cool
file handling functions.

Use it!

```php

use Symfony\Component\HttpFoundation\File\File;

$person = new my\Super\Model\Person();
// the only limitation at the moment is that the object has to be saved before attaching any files to make the soft relation work
$person
    ->setName('first person')
    ->save()
;

$file = new File($this->get('kernel')->getRootDir() . '/../path/to/avatar.png');
$attachment1 = $person->addAvatarAttachmentFile($file);

$file = new File($this->get('kernel')->getRootDir() . '/../somefile.pdf');
$attachment2 = $person->attachFile($file, 'customType');

// save to store references inside the object.
$person->save();

```
