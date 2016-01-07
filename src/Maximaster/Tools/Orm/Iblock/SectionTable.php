<?php

namespace Maximaster\Tools\Orm\Iblock;

use Bitrix\Main\ArgumentException;
use Maximaster\Tools\Helpers\IblockStructure;
use Maximaster\Tools\Interfaces\IblockRelatedTableInterface;
use Bitrix\Main\Entity;

class SectionTable extends \Bitrix\Iblock\SectionTable implements IblockRelatedTableInterface
{
    public static function getIblockId()
    {
        return null;
    }

    public static function getUfId()
    {
        $iblockId = static::getIblockId();
        if ($iblockId !== null)
        {
            return "IBLOCK_{$iblockId}_SECTION";
        }

        return null;
    }

    public static function getMap()
    {
        if (static::getIblockId() === null) return parent::getMap();

        $map = parent::getMap();

        foreach (self::getAdditionalMap() as $key => $mapItem)
        {
            $map[] = $mapItem;
        }

        return $map;
    }

    private static function getAdditionalMap($iblockId = null)
    {
        $map = array();

        $iblockId = $iblockId === null ? static::getIblockId() : $iblockId;

        return $map;

    }

    /**
     * @param $iblockId
     * @return Entity\Base
     * @throws ArgumentException
     */
    public static function compileEntity($iblockId)
    {
        $iblock = IblockStructure::iblock($iblockId);
        if (!$iblock)
        {
            throw new ArgumentException('Указан несуществующий идентификатор инфоблока');
        }

        $entityName = "Iblock" . Entity\Base::snake2camel($iblockId) . "SectionTable";
        $fullEntityName = '\\' . __NAMESPACE__ . '\\' . $entityName;

        $code = "
            namespace "  . __NAMESPACE__ . ";
            class {$entityName} extends SectionTable {
                public static function getIblockId(){
                    return {$iblock['ID']};
                }
                public static function getUfId(){
                    return 'IBLOCK_{$iblock['ID']}_SECTION';
                }
            }
        ";
        if (!class_exists($fullEntityName)) eval($code);

        return Entity\Base::getInstance($fullEntityName);
    }
}