AttachmentBundle
================

Attach files to any (Propel) object using Symfony2

*THIS IS WORK IN PROGRESS!*

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

Configure Gaufrette storages in [knplabs/knp-gaufrette-bundle](https://github.com/KnpLabs/KnpGaufretteBundle).

Configure c33sAttachmentBundle:

```yml

c33s_attachment:
    # work in progress

```

Add behavior to your propel models:

```xml
    <table name="person">
        <behavior name="c33s_attachable">
            <parameter name="single_columns" value="avatar, icon" />
        </behavior>
    </table>
```

The given example will add the columns `avatar` and `icon` to your `person` model and supply them with cool
file handling functions.

