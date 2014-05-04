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

EOF;
            
        }
        
        return $attributes;
    }
    
    public function objectMethods(OMBuilder $builder)
    {
        $builder->declareClass('c33s\\AttachmentBundle\\Attachment\\AttachableObjectInterface');
        $builder->declareClass('c33s\\AttachmentBundle\\Model\\Attachment');
        $builder->declareClass('c33s\\AttachmentBundle\\Model\\AttachmentQuery');
        $builder->declareClass('Symfony\\Component\\HttpFoundation\\File\\File');
        $builder->declareClass('\\PropelCollection');
        
        $methods = '';
        
        $columnsText = '';
        foreach ($this->getSingleColumns() as $column)
        {
            $phpName = $this->getTable()->getColumn($column)->getPhpName();
            
            $columnsText .= "        '{$phpName}',\n";
            
            $methods .= <<<EOF

/**
 * Get the Attachment object assigned to the {$column} column. This is provided through the attachable behavior.
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
        \$this->a{$phpName}Attachment = AttachmentQuery::create()
            ->filterByFileKey(\$this->get{$phpName}())
            ->findOne()
        ;
    }
    
    return \$this->a{$phpName}Attachment;
}

/**
 * Attach a file to the {$column} column. This is provided through the attachable behavior.
 *
 * @return Attachment   The generated Attachment object
 */
public function add{$phpName}AttachmentFile(File \$file)
{
    return \$this->attachFile(\$file, '{$phpName}');
}

EOF;
            
        }
        
        $methods .= <<<EOF

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
    return Attachment::getAttachmentHandler()->storeAndAttachFile(\$file, \$this, \$fieldName);
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
