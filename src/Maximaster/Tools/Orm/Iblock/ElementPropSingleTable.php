<?php

namespace Maximaster\Tools\Orm\Iblock;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity;
use Maximaster\Tools\Helpers\IblockStructure;

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
        if (!$properties) return $map;

        foreach ($properties as $prop)
        {
            if ($prop['MULTIPLE'] == 'Y') continue;

            $code = $prop['CODE'];
            $id = $prop['ID'];

            switch ($prop['PROPERTY_TYPE']) {
                case 'N':
                    $mapItem = new Entity\FloatField('PROPERTY_' . $id, array(
                        'column_name' => 'PROPERTY_' . $id,
                    ));
                    break;

                case 'L':
                case 'E':
                case 'G':
                    $mapItem = new Entity\IntegerField('PROPERTY_' . $id, array(
                        'column_name' => 'PROPERTY_' . $id
                    ));
                    break;

                case 'S':
                default:
                    $mapItem = new Entity\StringField('PROPERTY_' . $id, array(
                        'column_name' => 'PROPERTY_' . $id,
                    ));

                    break;
            }

            $map[ $code ] = $mapItem;

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
            throw new ArgumentException('Указан код несуществующего инфоблока');
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