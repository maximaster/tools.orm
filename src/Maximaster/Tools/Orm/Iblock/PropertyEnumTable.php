<?php

namespace Maximaster\Tools\Orm\Iblock;

class PropertyEnumTable
{
    public static function getMap()
    {
        $map = array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => 'Идентификатор значения свойства типа "Список"',
            ),
            'PROPERTY_ID' => array(
                'data_type' => 'integer',
                'title' => 'Идентификатор свойства',
            ),
            'PROPERTY' => array(
                'data_type' => 'Bitrix\Iblock\PropertyTable',
                'reference' => array('=this.IBLOCK_ELEMENT_ID' => 'ref.ID'),
            ),
            'VALUE' => array(
                'data_type' => 'string',
                'title' => 'Значение свойства',
            ),
            'DEF' => array(
                'data_type' => 'boolean',
                'values' => array('N','Y'),
                'title' => 'По умолчанию',
            ),
            'SORT' => array(
                'data_type' => 'integer',
                'title' => 'Сортировка',
            ),
            'XML_ID' => array(
                'data_type' => 'string',
                'title' => 'Код значения свойства',
            ),
            'TMP_ID' => array(
                'data_type' => 'string',
                'title' => '',
            ),
        );
    }
}