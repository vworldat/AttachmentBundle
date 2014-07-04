<?php

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
        <behavior name="Attachable">
            <parameter name="single_columns" value="avatar, icon" />
        </behavior>
    </table>
</database>

EOF;
            return;
            require_once('C33sPropelBehaviorAttachable.php');
            //$class = new \ReflectionClass('C33sPropelBehaviorAttachable');
            $builder = new \PropelQuickBuilder();
            $builder->getConfig()->setBuildProperty('behaviorAttachableClass', 'C33sPropelBehaviorAttachable');
            $builder->setSchema($schema);
            $con = $builder->build();
        }
    }
    
    public function testBuildModelWithoutExistingColumns()
    {
        $this->assertTrue(
            method_exists('TestModelWithoutColumns', 'getAvatar()'),
            'Avatar column was not added automatically'
        );
        
        $this->assertTrue(
            method_exists('TestModelWithoutColumns', 'getIcon()'),
            'Icon column was not added automatically'
        );
    }
}
