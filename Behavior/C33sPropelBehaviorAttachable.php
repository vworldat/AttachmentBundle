<?php

class C33sPropelBehaviorAttachable extends Behavior
{
    protected $parameters = array(
        'single_columns' => '',
    );
    
    protected function getNamespacedClassName()
    {
        return $this->getTable()->getNamespace().'\\'.$this->getTable()->getPhpName();
    }
    
    protected function getSingleColumns()
    {
        $columns = explode(',', $this->getParameter('single_columns'));
        $columns = array_map('trim', $columns);
        
        return array_filter($columns);
    }
    
    /**
     * Add the create_column and update_columns to the current table
     */
    public function modifyTable()
    {
        foreach ($this->getSingleColumns() as $column)
        {
            if (!$this->getTable()->containsColumn($column))
            {
                $this->getTable()->addColumn(array(
                	'name' => $column,
                    'type' => 'VARCHAR',
                    'size' => '255',
                ));
            }
        }
    }

    public function objectAttributes()
    {
        $attributes = '';
        
        foreach ($this->getSingleColumns() as $column)
        {
            $phpName = $this->getTable()->getColumn($column)->getPhpName();
            
            $attributes .= <<<EOF

/**
 * @var Attachment
 */
protected \$a{$phpName}Attachment;

/**
 * @var UploadCollection
 */
protected \$aAllAttachmentsCollection;

/**
 * @var UploadCollection
 */
protected \$aGeneralAttachmentsCollection;

EOF;
            
        }
        
        return $attributes;
    }
    
    public function objectMethods(OMBuilder $builder)
    {
        $builder->declareClass('c33s\\AttachmentBundle\\Attachment\\AttachableObjectInterface');
        $builder->declareClass('c33s\\AttachmentBundle\\Model\\Attachment');
        $builder->declareClass('c33s\\AttachmentBundle\\Model\\AttachmentQuery');
        $builder->declareClass('c33s\\AttachmentBundle\\Collection\\UploadCollection');
        $builder->declareClass('Symfony\\Component\\HttpFoundation\\File\\File');
        $builder->declareClass('Symfony\\Component\\HttpFoundation\\File\\UploadedFile');
        $builder->declareClass('\\PropelCollection');
        
        $methods = '';
        
        $columnsText = '';
        foreach ($this->getSingleColumns() as $column)
        {
            $phpName = $this->getTable()->getColumn($column)->getPhpName();
            
            $columnsText .= "        '{$phpName}',\n";
            
            $methods .= <<<EOF

/**
 * Get the Attachment object assigned to the {$column} column.
 *
 * @return Attachment
 */
public function get{$phpName}Attachment()
{
    if ('' == \$this->get{$phpName}())
    {
        return null;
    }
    
    if (null === \$this->a{$phpName}Attachment)
    {
        \$this->a{$phpName}Attachment = \$this->getAttachmentHandler()->getAttachment(\$this->get{$phpName}());
    }
    
    return \$this->a{$phpName}Attachment;
}

/**
 * Set the Attachment object assigned to the {$column} column.
 * This is used by the AttachmentHandler to inject the object after creating it.
 *
 * You should never create Attachment objects yourself!
 *
 * @param Attachment
 *
 * @return {$this->getTable()->getPhpName()}
 */
public function set{$phpName}Attachment(Attachment \$attachment)
{
    \$this->a{$phpName}Attachment = \$attachment;
    \$this->set{$phpName}(\$attachment->getFileKey());
    
    return \$this;
}

/**
 * Check if the object does have an attached file for the {$column} column.
 *
 * @return boolean
 */
public function has{$phpName}()
{
    return null !== \$this->get{$phpName}();
}

/**
 * Attach a file to the {$column} column using an UploadedFile or File object. This is useful in forms.
 *
 * @param File \$file
 *
 * @return {$phpName}
 */
public function set{$phpName}File(File \$file = null)
{
    if (null !== \$file)
    {
        \$this->attachFile(\$file, '{$phpName}');
    }
    
    return \$this;
}

/**
 * Attach a file to the {$column} column using a local filesystem path. This is useful when loading fixtures.
 *
 * @param string \$filePath
 *
 * @return {$this->getTable()->getPhpName()}
 */
public function set{$phpName}FilePath(\$filePath)
{
    \$file = new File(\$filePath);
    \$this->attachFile(\$file, '{$phpName}');
    
    return \$this;
}

/**
 * Get the attached file object for the {$column} column.
 *
 * @return File
 */
public function get{$phpName}File()
{
    if (\$this->has{$phpName}())
    {
        return \$this->getAttachmentHandler()->getFile(\$this->get{$phpName}());
    }
    
    return null;
}

/**
 * Get the web path to display/access the {$phpName} file.
 *
 * @return File
 */
public function get{$phpName}FilePath()
{
    if (\$this->has{$phpName}())
    {
        return \$this->get{$phpName}File()->getPathname();
    }
    
    return null;
}

/**
 * Get the web path to display/access the {$phpName} file.
 *
 * @return File
 */
public function get{$phpName}FileWebPath()
{
    if (\$this->has{$phpName}())
    {
        return \$this->getAttachmentHandler()->getFileUrl(\$this->get{$phpName}());
    }
    
    return null;
}

EOF;
            
        }
        
        $methods .= <<<EOF

/**
 * Get the AttachmentHandler service
 *
 * @return AttachmentHandler
 */
protected function getAttachmentHandler()
{
    return Attachment::getAttachmentHandler();
}

/**
 * Get all attachments related to this object.
 *
 * @return PropelCollection[Attachment]
 */
public function getAllAttachments()
{
    return \$this
        ->getAllAttachmentsQuery()
        ->with('AttachmentLink')
        ->find()
    ;
}

/**
 * Get attachments related to this object but not referenced by a specific field.
 * This method returns an instance of c33s\AttachmentBundle\Collection\UploadCollection
 * to be used by the collection_upload form type.
 *
 * @return UploadCollection[Attachment]
 */
public function getAllAttachmentsCollection()
{
    if (null === \$this->aAllAttachmentsCollection)
    {
        \$this->aAllAttachmentsCollection = new UploadCollection(\$this->getAllAttachments()->getArrayCopy());
        \$this->aAllAttachmentsCollection->setModel('c33s\\AttachmentBundle\\Model\\Attachment');
    }
    
    return \$this->aAllAttachmentsCollection;
}

/**
 * Set collection of Attachment objects to keep as links. This will immediately delete the missing objects
 * and save the new ones. This is designed to be used with the collection_upload form type.
 *
 * @param UploadCollection \$collection
 *
 * @return {$this->getTable()->getPhpName()}
 */
public function setAllAttachmentsCollection(UploadCollection \$collection)
{
    \$toDelete = \$this->getAllAttachments()->diff(\$collection);
    
    foreach (\$toDelete as \$attachment)
    {
        \$attachment->deleteLoadedAttachmentLinks();
    }
    
    foreach (\$collection as \$attachment)
    {
        if (\$attachment->isNew())
        {
            \$attachment->saveNewDataToStorage();
        }
        else
        {
            \$attachment->saveRememberedCustomName();
        }
    }
    
    return \$this;
}

/**
 * Get query to fetch Attachment objects related to this object.
 * This is useful for enhanced filtering by meta data.
 *
 * @return AttachmentQuery
 */
public function getAllAttachmentsQuery()
{
    return AttachmentQuery::create()
        ->filterAllAttachmentsForObject(\$this)
    ;
}

/**
 * Get attachments related to this object but not referenced by a specific field.
 *
 * @return PropelCollection[Attachment]
 */
public function getGeneralAttachments()
{
    return \$this
        ->getGeneralAttachmentsQuery()
        ->with('AttachmentLink')
        ->find()
    ;
}

/**
 * Get attachments related to this object but not referenced by a specific field.
 * This method returns an instance of c33s\AttachmentBundle\Collection\UploadCollection
 * to be used by the collection_upload form type.
 *
 * @return UploadCollection[Attachment]
 */
public function getGeneralAttachmentsCollection()
{
    if (null === \$this->aGeneralAttachmentsCollection)
    {
        \$this->aGeneralAttachmentsCollection = new UploadCollection(\$this->getGeneralAttachments()->getArrayCopy());
        \$this->aGeneralAttachmentsCollection->setModel('c33s\\AttachmentBundle\\Model\\Attachment');
    }
    
    return \$this->aGeneralAttachmentsCollection;
}

/**
 * Set collection of Attachment objects to keep as links. This will immediately delete the missing objects
 * and save the new ones. This is designed to be used with the collection_upload form type.
 *
 * @param UploadCollection \$collection
 *
 * @return {$this->getTable()->getPhpName()}
 */
public function setGeneralAttachmentsCollection(UploadCollection \$collection)
{
    \$toDelete = \$this->getGeneralAttachments()->diff(\$collection);
    
    foreach (\$toDelete as \$attachment)
    {
        \$attachment->deleteLoadedAttachmentLinks();
    }
    
    foreach (\$collection as \$attachment)
    {
        if (\$attachment->isNew())
        {
            \$attachment->saveNewDataToStorage();
        }
        else
        {
            \$attachment->saveRememberedCustomName();
        }
    }
    
    return \$this;
}

/**
 * Get query to fetch Attachment objects related to this object but not referenced by a specific field.
 * This is useful for enhanced filtering by meta data.
 *
 * @return AttachmentQuery
 */
public function getGeneralAttachmentsQuery()
{
    return AttachmentQuery::create()
        ->filterGeneralAttachmentsForObject(\$this)
    ;
}

/**
 * Get attachments related to this object and linked to a specific field. This field does not have to exist.
 *
 * @param string \$fieldName
 *
 * @return PropelCollection[Attachment]
 */
public function getSpecificAttachments(\$fieldName)
{
    return \$this
        ->getSpecificAttachmentsQuery(\$fieldName)
        ->with('AttachmentLink')
        ->find()
    ;
}

/**
 * Get query to fetch Attachment objects related to this object and linked to a specific field. This field does not have to exist.
 * This is useful for enhanced filtering by meta data.
 *
 * @param string \$fieldName
 *
 * @return AttachmentQuery
 */
public function getSpecificAttachmentsQuery(\$fieldName)
{
    return AttachmentQuery::create()
        ->filterSpecificAttachmentsForObject(\$this, \$fieldName)
    ;
}

/**
 * Attach the given file to this object, optionally setting a field name that may or may not exist in the object.
 *
 * @param File \$file
 * @param string \$fieldName
 *
 * @return Attachment   The created Attachment object
 */
public function attachFile(File \$file, \$fieldName = null)
{
    return \$this->getAttachmentHandler()->storeAndAttachFile(\$file, \$this, \$fieldName);
}

/**
 * Get a class name (including namespace) to identify attachable objects of the same type.
 *
 * @return string
 */
public function getAttachableClassName()
{
    return '{$this->getNamespacedClassName()}';
}

/**
 * Get a numeric ID referencing the given object. Usually this is something like \$this->getId().
 *
 * @return int
 */
public function getAttachableId()
{
    return \$this->getPrimaryKey();
}

/**
 * Get a list of field names that are used to directly link attachments via key. These fields
 * should hold a key string each. 255 bytes varchar is recommended.
 *
 * example:
 * return array(
 * 'Logo',
 * 'Icon',
  * );
  * matching \$this->getLogo() and \$this->setLogo(\$logoKey) et cetera.
  *
  * @return array
 */
public function getAttachableFieldNames()
{
    return array(
{$columnsText}    );
}

EOF;
    
        return $methods;
    }
    
    public function objectFilter(&$script)
    {
        $this->addInterface($script, 'AttachableObjectInterface');
    }
    
    protected function addInterface(&$script, $interface)
    {
        $script = preg_replace('#(implements Persistent)#', '$1, '.$interface, $script);
    }
}
