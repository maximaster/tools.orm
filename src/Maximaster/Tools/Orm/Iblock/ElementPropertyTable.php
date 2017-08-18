<?php

namespace Maximaster\Tools\Orm\Iblock;

use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class ElementPropertyTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'b_iblock_element_property';
    }

    public static function getMap()
    {
        $map = array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_VALUE_ID"),
            ),
            'IBLOCK_PROPERTY_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_ID"),
            ),
            'IBLOCK_ELEMENT_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_ELEMENT_ID"),
            ),
            'PROPERTY' => array(
                'data_type' => 'Bitrix\Iblock\PropertyTable',
                'reference' => array('=this.IBLOCK_PROPERTY_ID' => 'ref.ID'),
            ),
            'VALUE' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_VALUE"),
            ),
            'VALUE_TYPE' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_TYPE"),
            ),
            'VALUE_ENUM' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_ENUM_VALUE"),
            ),
            'VALUE_NUM' => array(
                'data_type' => 'float',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_ENUM_NUM"),
            ),
            'DESCRIPTION' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_DESCRIPTION"),
            ),
            new Entity\ReferenceField(
                'PROPERTY',
                '\Bitrix\Iblock\Property',
                array('this.IBLOCK_PROPERTY_ID' => 'ref.ID')
            ),
        );

        return $map;
    }
}