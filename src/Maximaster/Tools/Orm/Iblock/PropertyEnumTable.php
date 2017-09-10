<?php

namespace Maximaster\Tools\Orm\Iblock;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PropertyEnumTable
{
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_ENUM_ID"),
            ),
            'PROPERTY_ID' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_ENUM_PROPERTY_ID"),
            ),
            'PROPERTY' => array(
                'data_type' => 'Bitrix\Iblock\PropertyTable',
                'reference' => array('=this.IBLOCK_ELEMENT_ID' => 'ref.ID'),
            ),
            'VALUE' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_ENUM_VALUE"),
            ),
            'DEF' => array(
                'data_type' => 'boolean',
                'values' => array('N','Y'),
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_ENUM_DEF"),
            ),
            'SORT' => array(
                'data_type' => 'integer',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_ENUM_SORT"),
            ),
            'XML_ID' => array(
                'data_type' => 'string',
                'title' => Loc::getMessage("MAXIMASTER_TOOLS_PROPERTY_ENUM_XML_ID"),
            ),
            'TMP_ID' => array(
                'data_type' => 'string',
                'title' => '',
            ),
        );
    }
}