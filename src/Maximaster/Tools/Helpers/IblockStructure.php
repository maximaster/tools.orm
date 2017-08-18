<?php

namespace Maximaster\Tools\Helpers;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class IblockStructure
{
    public static function iblock($primary)
    {
        if (!$primary)
        {
            throw new ArgumentException(Loc::getMessage("MAXIMASTER_TOOLS_MISSING_IBLOCK_ID"));
        }

        $cache = new \CPHPCache();
        $path = self::createPath(__METHOD__);
        $cacheId = md5($primary);
        if ($cache->InitCache(86400, $cacheId, $path))
        {
            $iblock = $cache->GetVars();
        }
        else
        {
            $field = is_numeric($primary) ? 'ID' : 'CODE';
            $db = IblockTable::query()->addFilter($field, $primary)->setSelect(array('*'))->exec();
            if ($db->getSelectedRowsCount() == 0)
            {
                $cache->AbortDataCache();
                throw new ArgumentException(Loc::getMessage("MAXIMASTER_TOOLS_WRONG_IBLOCK_ID"));
            }
            elseif ($db->getSelectedRowsCount() > 1)
            {
                $cache->AbortDataCache();
                throw new ArgumentException(Loc::getMessage("MAXIMASTER_TOOLS_IBLOCK_MULTIPLE_FIELDS", array('#CNT#' => $db->getSelectedRowsCount(), '#FIELD#' => $field, '#PRIMARY#' => $primary)));
            }

            $iblock = $db->fetch();


            if ($cache->StartDataCache())
            {
                $cache->EndDataCache($iblock);
            }
        }

        return $iblock;

    }

    public static function properties($primary)
    {
        if (!$primary)
        {
            throw new ArgumentException(Loc::getMessage("MAXIMASTER_TOOLS_MISSING_IBLOCK_ID"));
        }

        $cache = new \CPHPCache();
        $path = self::createPath(__METHOD__);
        $cacheId = md5($primary);
        if ($cache->InitCache(86400, $cacheId, $path))
        {
            $props = $cache->GetVars();
        }
        else
        {
            $field = is_numeric($primary) ? 'IBLOCK_ID' : 'IBLOCK.CODE';

            $db = PropertyTable::query()->addFilter($field, $primary)->addSelect('*')->exec();
            $props = array();
            while ($prop = $db->fetch())
            {
                $code = $prop['CODE'];
                if (isset($props[ $code ]))
                {
                    throw new \LogicException(Loc::getMessage('MAXIMASTER_TOOLS_IBLOCK_DUPLICATE_PROPERTY_CODE', array('#PRIMARY#' => $primary, '#CODE#' => $code)));
                }

                if (strlen($code) === 0)
                {
                    throw new \LogicException(Loc::getMessage('MAXIMASTER_TOOLS_IBLOCK_MISSING_PROPERTY_CODE', array('#PRIMARY#' => $primary, '#PROP#' => $prop['NAME'])));
                }

                $props[ $code ] = $prop;
            }

            if ($cache->StartDataCache())
            {
                $cache->EndDataCache($props);
            }
        }


        return $props;
    }

    public static function sectionUFields($primary)
    {
        if (!$primary)
        {
            throw new ArgumentException(Loc::getMessage('MAXIMASTER_TOOLS_MISSING_IBLOCK_ID'));
        }

        $cache = new \CPHPCache();
        $path = self::createPath(__METHOD__);
        $cacheId = md5($primary . $path);
        if ($cache->InitCache(86400*2, $cacheId, $path))
        {
            $uFields = $cache->GetVars();
        }
        else
        {
            if (!is_numeric($primary))
            {
                $iblock = self::iblock($primary);
                if (!$iblock)
                {
                    $cache->AbortDataCache();
                    return null;
                }

                $primary = $iblock['ID'];
            }

            global $USER_FIELD_MANAGER;
            $uFields = $USER_FIELD_MANAGER->getUserFields("IBLOCK_{$primary}_SECTION");

            if ($cache->StartDataCache())
            {
                $cache->EndDataCache($uFields);
            }
        }

        return $uFields;
    }

    public static function full($primary)
    {
        return array(
            'iblock' => self::iblock($primary),
            'properties' => self::properties($primary),
            'sectionUserFields' => self::sectionUFields($primary),
        );
    }

    private static function createPath($string)
    {
        return str_replace(array('\\', ':'), '/', $string);
    }
}