<?php

namespace C33s\AttachmentBundle\Form;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;

/**
 * Type guesser for models implementing AttachableInterface or using the c33s_attachable behavior.
 *
 * @author David Herrmann <office@web-emerge.com>
 */
class AttachmentTypeGuesser implements FormTypeGuesserInterface
{
    private $cache = array();

    /**
     * {@inheritdoc}
     */
    public function guessType($class, $property)
    {
        $interfaces = class_implements($class);

        if (!$table = $this->getTable($class) || false === $interfaces || !array_key_exists('C33s\\AttachmentBundle\\Attachment\\AttachableObjectInterface', $interfaces))
        {
            return;
        }
        /* @var $table \TableMap */

        // model is implementing AttachableObjectInterface
        // this information could be used to supply further logic, but for now we only check models explicitly using the propel behavior

        $behaviors = $table->getBehaviors();
        if (!array_key_exists('c33s_attachable', $behaviors))
        {
            return;
        }

        $singleColumns = $this->getSingleColumns($behaviors['c33s_attachable']['single_columns']);

        if (in_array($property, $singleColumns))
        {

        }


//         foreach ($table->getRelations() as $relation) {
//             if ($relation->getType() === \RelationMap::MANY_TO_ONE) {
//                 if (strtolower($property) === strtolower($relation->getName())) {
//                     return new TypeGuess('model', array(
//                         'class'    => $relation->getForeignTable()->getClassName(),
//                         'multiple' => false,
//                     ), Guess::HIGH_CONFIDENCE);
//                 }
//             } elseif ($relation->getType() === \RelationMap::ONE_TO_MANY) {
//                 if (strtolower($property) === strtolower($relation->getPluralName())) {
//                     return new TypeGuess('model', array(
//                         'class'    => $relation->getForeignTable()->getClassName(),
//                         'multiple' => true,
//                     ), Guess::HIGH_CONFIDENCE);
//                 }
//             } elseif ($relation->getType() === \RelationMap::MANY_TO_MANY) {
//                 if (strtolower($property) == strtolower($relation->getPluralName())) {
//                     return new TypeGuess('model', array(
//                         'class'     => $relation->getLocalTable()->getClassName(),
//                         'multiple'  => true,
//                     ), Guess::HIGH_CONFIDENCE);
//                 }
//             }
//         }

//         if (!$column = $this->getColumn($class, $property)) {
//             return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
//         }

//         switch ($column->getType()) {
//             case \PropelColumnTypes::BOOLEAN:
//             case \PropelColumnTypes::BOOLEAN_EMU:
//                 return new TypeGuess('checkbox', array(), Guess::HIGH_CONFIDENCE);
//             case \PropelColumnTypes::TIMESTAMP:
//             case \PropelColumnTypes::BU_TIMESTAMP:
//                 return new TypeGuess('datetime', array(), Guess::HIGH_CONFIDENCE);
//             case \PropelColumnTypes::DATE:
//             case \PropelColumnTypes::BU_DATE:
//                 return new TypeGuess('date', array(), Guess::HIGH_CONFIDENCE);
//             case \PropelColumnTypes::TIME:
//                 return new TypeGuess('time', array(), Guess::HIGH_CONFIDENCE);
//             case \PropelColumnTypes::FLOAT:
//             case \PropelColumnTypes::REAL:
//             case \PropelColumnTypes::DOUBLE:
//             case \PropelColumnTypes::DECIMAL:
//                 return new TypeGuess('number', array(), Guess::MEDIUM_CONFIDENCE);
//             case \PropelColumnTypes::TINYINT:
//             case \PropelColumnTypes::SMALLINT:
//             case \PropelColumnTypes::INTEGER:
//             case \PropelColumnTypes::BIGINT:
//             case \PropelColumnTypes::NUMERIC:
//                 return new TypeGuess('integer', array(), Guess::MEDIUM_CONFIDENCE);
//             case \PropelColumnTypes::ENUM:
//             case \PropelColumnTypes::CHAR:
//                 if ($column->getValueSet()) {
//                     //check if this is mysql enum
//                     $choices = $column->getValueSet();
//                     $labels = array_map('ucfirst', $choices);

//                     return new TypeGuess('choice', array('choices' => array_combine($choices, $labels)), Guess::MEDIUM_CONFIDENCE);
//                 }
//             case \PropelColumnTypes::VARCHAR:
//                 return new TypeGuess('text', array(), Guess::MEDIUM_CONFIDENCE);
//             case \PropelColumnTypes::LONGVARCHAR:
//             case \PropelColumnTypes::BLOB:
//             case \PropelColumnTypes::CLOB:
//             case \PropelColumnTypes::CLOB_EMU:
//                 return new TypeGuess('textarea', array(), Guess::MEDIUM_CONFIDENCE);
//             default:
//                 return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
//         }
    }

    /**
     * {@inheritdoc}
     */
    public function guessRequired($class, $property)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function guessMaxLength($class, $property)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function guessPattern($class, $property)
    {
    }

    /**
     *
     * @param string $class
     *
     * @return \TableMap
     */
    protected function getTable($class)
    {
        if (isset($this->cache[$class])) {
            return $this->cache[$class];
        }

        if (class_exists($queryClass = $class.'Query')) {
            $query = new $queryClass();

            return $this->cache[$class] = $query->getTableMap();
        }
    }

    /**
     *
     * @param string $class
     * @param string $property
     *
     * @return \ColumnMap
     */
    protected function getColumn($class, $property)
    {
        if (isset($this->cache[$class.'::'.$property])) {
            return $this->cache[$class.'::'.$property];
        }

        $table = $this->getTable($class);

        if ($table && $table->hasColumn($property)) {
            return $this->cache[$class.'::'.$property] = $table->getColumn($property);
        }
    }

    /**
     * Copied from C33sPropelBehaviorAttachable.
     *
     * @param string $columnsList
     * @return array
     */
    protected function getSingleColumns($columnsList)
    {
        $columns = explode(',', $columnsList);
        $columns = array_map('trim', $columns);

        return array_filter($columns);
    }
}
