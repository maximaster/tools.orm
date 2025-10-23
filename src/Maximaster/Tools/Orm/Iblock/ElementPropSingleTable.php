<?php

namespace Maximaster\Tools\Orm\Iblock;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Maximaster\Tools\Helpers\IblockStructure;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ElementPropSingleTable extends DataManager
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
                    $mapItem = new FloatField($fieldName, array(
                        'column_name' => $columnName,
                    ));
                    break;

                case 'L':
                case 'E':
                case 'G':
                    $mapItem = new IntegerField($fieldName, array(
                        'column_name' => $columnName,
                    ));
                    break;

                case 'S':
                default:
                    $mapItem = new StringField($fieldName, array(
                        'column_name' => $columnName,
                    ));

                    break;
            }

            $map[ $fieldName ] = $mapItem;

            if ($prop['WITH_DESCRIPTION'] == 'Y')
            {
                $map[ 'DESCRIPTION_' . $id ] = new StringField('DESCRIPTION_' . $id, array(
                    'column_name' => 'DESCRIPTION_' . $id,
                ));
            }
        }

        return $map;
    }

    /**
     * @param $iblockCode
     * @return DataManager
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