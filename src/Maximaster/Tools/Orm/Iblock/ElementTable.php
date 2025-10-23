<?php

namespace Maximaster\Tools\Orm\Iblock;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Field;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Maximaster\Tools\Helpers\IblockStructure;
use Maximaster\Tools\Interfaces\IblockRelatedTableInterface;
use Maximaster\Tools\Orm\Query;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ElementTable extends \Bitrix\Iblock\ElementTable implements IblockRelatedTableInterface
{
    protected static $concatSeparator = '|<-separator->|';

    public static function getIblockId()
    {
        return null;
    }

    public static function getMap(): array
    {
        if (static::getIblockId() === null) return parent::getMap();

        $map = parent::getMap();

        foreach (self::getAdditionalMap() as $key => $mapItem)
        {
            $map[ $key ] = $mapItem;
        }

        return $map;
    }

    /**
     * Получает список дополнительных полей, которые нужно добавить в сущность
     *
     * @param null|int|string $iblockId Идентификатор инфоблока или код инфоблока или null. В случае null будет выполнена
     * попытка получить идентификатор инфоблока из сущности
     * @return array Список дополнительных полей для сущности
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function getAdditionalMap($iblockId = null)
    {
        $map = array();
        $iblockId = $iblockId === null ? static::getIblockId() : $iblockId;

        if (!$iblockId) return $map;

        $iblock = IblockStructure::iblock($iblockId);
        $props = IblockStructure::properties($iblockId);

        if (empty($props) || empty($iblock)) return $map;

        $isOldProps = $iblock['VERSION'] == 1;

        $singlePropTableLinked = false;
        $singlePropsEntityName = "PROPERTY_TABLE_IBLOCK_{$iblock['ID']}";

        if (!$isOldProps)
        {
            $singleProp = ElementPropSingleTable::getInstance($iblock['ID'])->getEntity()->getDataClass();
            $multipleProp = ElementPropMultipleTable::getInstance($iblock['ID'])->getEntity()->getDataClass();
        }
        else
        {
            $singleProp = ElementPropertyTable::getEntity()->getDataClass();
        }

        foreach ($props as $propCode => $prop)
        {
            if (is_numeric($propCode)) continue;

            $propId                 = $prop['ID'];
            $isMultiple             = $prop['MULTIPLE'] == 'Y';
            $useDescription         = $prop['WITH_DESCRIPTION'] == 'Y';
            $isNewMultiple          = $isMultiple && !$isOldProps;

            $propTableEntityName            = "PROPERTY_{$propCode}";
            $propValueShortcut              = "PROPERTY_{$propCode}_VALUE";
            $propValueDescriptionShortcut   = "PROPERTY_{$propCode}_DESCRIPTION";
            $concatSubquery                 = "GROUP_CONCAT(%s SEPARATOR '" .  static::$concatSeparator . "')";
            $propValueColumn                = $isMultiple || $isOldProps ? 'VALUE' : "PROPERTY_{$propId}";
            $valueReference = $valueEntity = $fieldReference = null;

            /**
             * Для не множественных свойств 2.0 цепляем только одну сущность
             */
            if (!$isOldProps && !$isMultiple)
            {
                $map[ $singlePropsEntityName ] = new Reference(
                    $singlePropsEntityName,
                    $singleProp,
                    array('=ref.IBLOCK_ELEMENT_ID' => 'this.ID'),
                    array('join_type' => 'LEFT')
                );

                $singlePropTableLinked = true;
            }

            $realValueStorage = !$isOldProps && !$isMultiple ?
                "{$singlePropsEntityName}.{$propValueColumn}" :
                "{$propTableEntityName}.{$propValueColumn}";

            if ($isMultiple)
            {
                $valueEntity = new ExpressionField(
                    $propValueShortcut,
                    $concatSubquery,
                    $realValueStorage
                );
                $valueEntity->addFetchDataModifier(array(__CLASS__, 'multiValuesDataModifier'));
            }
            else
            {
                switch ($prop['PROPERTY_TYPE'])
                {
                    case 'E':

                        $valueReference = array("=this.{$realValueStorage}" => 'ref.ID');
                        $entityName = '\\' . __CLASS__;
                        if ($prop['LINK_IBLOCK_ID'])
                        {
	                        $entityName = $iblockId == $prop['LINK_IBLOCK_ID']
		                        ? get_called_class()
		                        : self::compileEntity($prop['LINK_IBLOCK_ID'])->getDataClass();
                            $valueReference['=ref.IBLOCK_ID'] = new SqlExpression('?i', $prop['LINK_IBLOCK_ID']);
                        }

                        $valueEntity = new Reference(
                            $propValueShortcut,
                            $entityName,
                            $valueReference
                        );

                        break;
                    case 'G':

                        $valueReference = array("=this.{$realValueStorage}" => 'ref.ID');
                        $entityName = __NAMESPACE__ . '\\SectionTable';
                        if ($prop['LINK_IBLOCK_ID'])
                        {
                            $entityName = $entityName::compileEntity($prop['LINK_IBLOCK_ID'])->getDataClass();
                            $valueReference['=ref.IBLOCK_ID'] = new SqlExpression('?i', $prop['LINK_IBLOCK_ID']);
                        }

                        $valueEntity = new Reference(
                            $propValueShortcut,
                            $entityName,
                            $valueReference
                        );

                        break;

                    case 'L':

                        $valueReference = array(
                            '=ref.PROPERTY_ID' => new SqlExpression('?i', $prop['ID']),
                            "=this.{$realValueStorage}" => 'ref.ID'
                        );

                        $valueEntity = new Reference(
                            $propValueShortcut,
                            '\Bitrix\Iblock\PropertyEnumerationTable',
                            $valueReference
                        );

                        break;
                    case 'S':
                    case 'N':

                        $valueEntity = new ExpressionField(
                            $propValueShortcut,
                            '%s',
                            $realValueStorage
                        );
                        break;
                     case 'F':
                        $valueReference = array(
                            "=this.{$realValueStorage}" => 'ref.ID'
                        );

                        $valueEntity = new Reference(
                            $propValueShortcut,
                            '\Bitrix\Main\FileTable',
                            $valueReference
                        );

                        break;
                    default:
                        break;
                }
            }

            if (!$valueEntity) {
                continue;
            }

            /**
             * Для всех свойств, кроме одиночных 2.0
             */
            if ($isOldProps || $isMultiple)
            {
                /**
                 * Цепляем таблицу со значением свойства
                 */
                $map[ $propTableEntityName ] = new Reference(
                    $propTableEntityName,
                    $isNewMultiple ? $multipleProp : $singleProp,
                    array(
                        '=ref.IBLOCK_ELEMENT_ID' => 'this.ID',
                        '=ref.IBLOCK_PROPERTY_ID' => new SqlExpression('?i', $propId)
                    ),
                    array('join_type' => 'LEFT')
                );

                /**
                 * Модификатор для множественных значений
                 */
                if ($isMultiple) $valueEntity->addFetchDataModifier(array(__CLASS__, 'multiValuesDataModifier'));
                $map[ $propValueShortcut ] = $valueEntity;

                /**
                 * И для его описания, если оно есть
                 */
                if ($useDescription)
                {
                    $e = new ExpressionField(
                        $propValueDescriptionShortcut,
                        $isMultiple ? $concatSubquery : '%s',
                        "{$propTableEntityName}.DESCRIPTION"
                    );

                    if ($isMultiple) $e->addFetchDataModifier(array(__CLASS__, 'multiValuesDataModifier'));
                    $map[ $propValueDescriptionShortcut ] = $e;
                }
            }
            else
            {
                /**
                 * Цепляем таблицу со значением свойства. Она уже подцеплена, но для совместимости...
                 */
                $map[ $propTableEntityName ] = new Reference(
                    $propTableEntityName,
                    $singleProp,
                    array('=ref.IBLOCK_ELEMENT_ID' => 'this.ID'),
                    array('join_type' => 'LEFT')
                );

                /**
                 * Делаем быстрый доступ для значения свойства
                 */
                $map[ $propValueShortcut ] = $valueEntity;

                /**
                 * И для его описания, если оно есть
                 */
                if ($useDescription)
                {
                    $map[ $propValueDescriptionShortcut ] = new ExpressionField(
                        $propValueDescriptionShortcut,
                        '%s',
                        "{$singlePropsEntityName}.DESCRIPTION_{$propId}"
                    );
                }
            }
        }

        /**
         * Добавим DETAIL_PAGE_URL
         */
        $e = new ExpressionField('DETAIL_PAGE_URL', '%s', 'IBLOCK.DETAIL_PAGE_URL');
        $e->addFetchDataModifier(function($value, $query, $entry, $fieldName)
        {
            $search = array();
            $replace = array();
            foreach ($entry as $key => $val)
            {
                $search[] = "#{$key}#";
                $replace[] = $val;
            }
            return str_replace($search, $replace, $value);
        });
        $map['DETAIL_PAGE_URL'] = $e;

        return $map;
    }

    /**
     * Модификатор данных для множественных свойств. Разрезает строку со сгруппированным значением множественных свойств
     *
     * @param $value
     * @param $query
     * @param $entry
     * @param $fieldName
     * @return array
     */
    public static function multiValuesDataModifier($value, $query, $entry, $fieldName)
    {
        if (
            trim($value) == static::$concatSeparator
            || strpos($value, static::$concatSeparator) === false

        ) return array();

        return explode(static::$concatSeparator, $value);
    }

    /**
     * @param array        $prop
     * @param Field $tableField
     * @return ExpressionField
     */
    private static function linkPropertyValue(array $prop, Field &$tableField)
    {
        $propValueShortcut = "PROPERTY_{$prop['CODE']}_VALUE";
        $isMultiple = $prop['MULTIPLE'] == 'Y';
        $isOldProps = $prop['VERSION'] == 2;
        $concatSubquery = "GROUP_CONCAT(%s SEPARATOR '" .  static::$concatSeparator . "')";
        $propValueColumn = $isMultiple || $isOldProps ? 'VALUE' : "PROPERTY_{$prop['ID']}";

        $mapEntity = new ExpressionField(
            $propValueShortcut,
            $isMultiple ? $concatSubquery : '%s',
            "{$tableField->getName()}.{$propValueColumn}"
        );

        return $mapEntity;
    }

    /**
     * Подмена встроенного запроса на модифицированный
     *
     * @return Query
     */
    public static function query()
    {
        return new Query(static::getEntity());
    }

    public static function add(array $data): \Bitrix\Main\ORM\Data\AddResult
    {
        throw new \LogicException(Loc::getMessage("MAXIMASTER_TOOLS_USE_ELEMENT_TABLE"));
    }

    public static function update($primary, array $data): \Bitrix\Main\ORM\Data\UpdateResult
    {
        throw new \LogicException(Loc::getMessage('MAXIMASTER_TOOLS_USE_ELEMENT_TABLE'));
    }

    public static function delete($primary): \Bitrix\Main\ORM\Data\DeleteResult
    {
        throw new \LogicException(Loc::getMessage('MAXIMASTER_TOOLS_USE_ELEMENT_TABLE'));
    }

    /**
     * @param $iblockId
     * @return Entity
     * @throws ArgumentException
     */
    public static function compileEntity($iblockId)
    {
        $iblock = IblockStructure::iblock($iblockId);
        if (!$iblock)
        {
            throw new ArgumentException(Loc::getMessage("MAXIMASTER_TOOLS_WRONG_IBLOCK_ID"));
        }

        $entityName = "Iblock" . Entity::snake2camel($iblockId) . "ElementTable";
        $fullEntityName = '\\' . __NAMESPACE__ . '\\' . $entityName;

        $code = "
            namespace "  . __NAMESPACE__ . ";
            class {$entityName} extends ElementTable {
                public static function getIblockId(){
                    return {$iblock['ID']};
                }
            }
        ";
        if (!class_exists($fullEntityName)) eval($code);

        return Entity::getInstance($fullEntityName);
    }
}