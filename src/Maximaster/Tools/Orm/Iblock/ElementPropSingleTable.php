<?php

namespace Maximaster\Tools\Orm\Iblock;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity;
use Maximaster\Tools\Helpers\IblockStructure;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ElementPropSingleTable extends Entity\DataManager
{
    protected static $iblockCode;
    protected static $iblockId;

    public static function getTableName()
    {
        return 'b_iblock_element_prop_s' . static::$iblockId;
    }

    public static function getMap()
    {
        $map = array(
            'IBLOCK_ELEMENT_ID' => array(
                'data_type' => 'integer',
                'primary' => true,
            )
        );

        $properties = IblockStructure::properties(static::$iblockCode);
        if (!$properties) {
            return $map;
        }

        foreach ($properties as $prop) {
            if ($prop['MULTIPLE'] == 'Y') {
                continue;
            }

            $id = $prop['ID'];
            $fieldName = $columnName = 'PROPERTY_' . $id;

            switch ($prop['PROPERTY_TYPE']) {
                case 'N':
                    $mapItem = new Entity\FloatField($fieldName, array(
                        'column_name' => $columnName,
                    ));
                    break;

                case 'L':
                case 'E':
                case 'G':
                    $mapItem = new Entity\IntegerField($fieldName, array(
                        'column_name' => $columnName,
                    ));
                    break;

                case 'S':
                default:
                    $mapItem = new Entity\StringField($fieldName, array(
                        'column_name' => $columnName,
                    ));

                    break;
            }

            $map[ $fieldName ] = $mapItem;

            if ($prop['WITH_DESCRIPTION'] == 'Y')
            {
                $map[ 'DESCRIPTION_' . $id ] = new Entity\StringField('DESCRIPTION_' . $id, array(
                    'column_name' => 'DESCRIPTION_' . $id,
                ));
            }
        }

        return $map;
    }

    /**
     * @param $iblockCode
     * @return Entity\DataManager
     * @throws ArgumentException
     */
    public static function getInstance($iblockCode)
    {
        $iblock = IblockStructure::iblock($iblockCode);
        if (!$iblock)
        {
            throw new ArgumentException(Loc::getMessage("MAXIMASTER_TOOLS_WRONG_IBLOCK_CODE"));
        }

        self::$iblockCode = $iblockCode;
        self::$iblockId = $iblock['ID'];

        return self::compileEntity();
    }

    private static function compileEntity()
    {
        $class = 'Iblock' . static::$iblockId . 'SinglePropertyTable';
        if (!class_exists($class))
        {
            $eval = "class {$class} extends " . __CLASS__ . "{}";

            eval($eval);
        }

        return new $class;
    }
}