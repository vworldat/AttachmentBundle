<?php

use c33s\AttachmentBundle\Model\Attachment;
class C33sPropelBehaviorAttachableTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('TestModelWithoutColumns'))
        {
            $schema = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<database name="testdatabase" defaultIdMethod="native">
    <table name="test_model_without_columns">
        <behavior name="auto_add_pk" />
        <behavior name="c33s_attachable">
            <parameter name="single_columns" value="avatar, icon" />
        </behavior>
    </table>
</database>

EOF;
            
            $builder = new \PropelQuickBuilder();
            $builder->getConfig()->setBuildProperty('behaviorC33s_attachableClass', 'C33sPropelBehaviorAttachable');
            $builder->setSchema($schema);
            $con = $builder->build();
        }
        
        if (!class_exists('TestModelWithColumns'))
        {
            $schema = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<database name="testdatabase" defaultIdMethod="native">
    <table name="test_model_with_columns">
        <behavior name="auto_add_pk" />
        <behavior name="c33s_attachable">
            <parameter name="single_columns" value="avatar, icon" />
        </behavior>
        <column name="avatar" type="VARCHAR" size="255" />
        <column name="icon" type="LONGVARCHAR" />
    </table>
</database>

EOF;
            
            $builder = new \PropelQuickBuilder();
            $builder->getConfig()->setBuildProperty('behaviorC33s_attachableClass', 'C33sPropelBehaviorAttachable');
            $builder->setSchema($schema);
            $con = $builder->build();
        }
    }
    
    public function testBuildModelWithoutExistingColumns()
    {
        $this->assertHasMethod('TestModelWithoutColumns', 'getAvatar');
        $this->assertHasMethod('TestModelWithoutColumns', 'getIcon');
    }
    
    public function testBuildModelWithExistingColumns()
    {
        $this->assertHasMethod('TestModelWithColumns', 'getAvatar');
        $this->assertHasMethod('TestModelWithColumns', 'getIcon');
    }
    
    public function testInterfacesAdded()
    {
        $this->assertInstanceOf('c33s\\AttachmentBundle\\Attachment\\AttachableObjectInterface', new TestModelWithoutColumns());
        $this->assertInstanceOf('c33s\\AttachmentBundle\\Attachment\\AttachableObjectInterface', new TestModelWithColumns());
    }
    
    /**
     * @dataProvider addedMethodsProvider
     *
     * @param string $method
     */
    public function testHasAllPublicMethods($method)
    {
        $this->assertHasMethod(new TestModelWithoutColumns(), $method);
    }
    
    /**
     * These public methods should be added to each object.
     *
     * @return array[string]
     */
    public function addedMethodsProvider()
    {
        return array(
            array('getAvatarAttachment'),
            array('setAvatarAttachment'),
            array('hasAvatar'),
            array('setAvatarFile'),
            array('setAvatarFilePath'),
            array('getAvatarFile'),
            array('getAvatarFilePath'),
            array('getAvatarFileWebPath'),
            array('processNewAvatarFile'),
            array('getIconAttachment'),
            array('setIconAttachment'),
            array('hasIcon'),
            array('setIconFile'),
            array('setIconFilePath'),
            array('getIconFile'),
            array('getIconFilePath'),
            array('getIconFileWebPath'),
            array('processNewIconFile'),
            array('getAttachmentHandler'),
            array('getAllAttachments'),
            array('getAllAttachmentsCollection'),
            array('setAllAttachmentsCollection'),
            array('getAllAttachmentsQuery'),
            array('getGeneralAttachments'),
            array('getGeneralAttachmentsCollection'),
            array('setGeneralAttachmentsCollection'),
            array('getGeneralAttachmentsQuery'),
            array('getSpecificAttachments'),
            array('getSpecificAttachmentsQuery'),
            array('setAttachmentsLoadFromDirectory'),
            array('processAttachmentsLoadFromDirectory'),
            array('attachFile'),
            array('setDeleteNewAttachmentFiles'),
            array('getAttachableClassName'),
            array('getAttachableId'),
            array('getAttachableFieldNames'),
        );
    }
    
    protected function assertHasMethod($object, $method)
    {
        $this->assertTrue(
            method_exists($object, $method),
            "$object class does not implement expected method $method()"
        );
    }
    
    public function testAttachmentHandlerIsForwarded()
    {
        $handler = $this->getMock('c33s\\AttachmentBundle\\Attachment\\AttachmentHandlerInterface');
        Attachment::setAttachmentHandler($handler);
        
        $model = new TestModelWithColumns();
        
        $class = new \ReflectionClass($model);
        $method = $class->getMethod('getAttachmentHandler');
        $method->setAccessible(true);
        
        $this->assertEquals($method->invokeArgs($model, array()), $handler);
    }
}
