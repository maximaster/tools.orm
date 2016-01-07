<?php

namespace Maximaster\Tools\Orm;

use Maximaster\Tools\Helpers\IblockStructure;
use Maximaster\Tools\Interfaces\IblockRelatedTableInterface;
use Maximaster\Tools\Orm\Iblock\ElementTable;

/**
 * Расширенный класс запроса, который может добавить поля в сущность при необходимости
 * @package Maximaster\Tools\Orm
 */
class Query extends \Bitrix\Main\Entity\Query
{
    /**
     * @var array Массив с идентификаторами и кодами инфоблоков
     */
    private $iblockPrimary = array();
    private $useIblockSearch = null;

    private function useIblockSearch()
    {
        if ($this->useIblockSearch !== null) return $this->useIblockSearch;

        $entityClass = $this->getEntity()->getDataClass();
        $this->useIblockSearch = $entityClass === '\\Maximaster\\Tools\\Orm\\Iblock\\ElementTable';

        return $this->useIblockSearch;
    }

    protected function buildQuery()
    {
        if ($this->useIblockSearch())
        {
            $this->appendIblockRelatedData();
        }
        else
        {
            $entityClass = $this->getEntity()->getDataClass();
            $this->filter['IBLOCK_ID'] = $entityClass::getIblockId();
        }

        return parent::buildQuery();
    }

    /**
     * Метод инициализирует поиск данных, связанных с инфоблоками и начинает добавление свойств к списку возможных
     */
    private function appendIblockRelatedData()
    {
        if ($this->searchIblocks())
        {
            $maps = array();
            if (empty($this->iblockPrimary)) return $maps;

            foreach ($this->iblockPrimary as $iblockPrimary)
            {
                $iblock = IblockStructure::iblock($iblockPrimary);
                $maps[] = ElementTable::getAdditionalMap($iblock['ID']);
            }

            call_user_func_array(array($this, 'appendIblockFields'), $maps);
        }
    }

    private function searchIblocks()
    {
        $i = new \RecursiveArrayIterator(array($this->filter));
        iterator_apply($i, array($this, 'recursiveScan'), array($i));
        return !empty($this->iblockPrimary);
    }

    private function recursiveScan(\RecursiveArrayIterator $iterator)
    {
        while ( $iterator->valid() )
        {
            $this->checkIblockData($iterator);

            if ( $iterator->hasChildren() )
            {
                $this->recursiveScan($iterator->getChildren());
            }
            else
            {
                $this->checkIblockData($iterator);
            }

            $iterator->next();
        }
    }

    private function checkIblockData(\Iterator $iterator)
    {
        $key = $iterator->key();
        $value = $iterator->current();

        if (strpos($key, 'IBLOCK_ID') !== false || strpos($key, 'IBLOCK_CODE') !== false)
        {
            if (is_array($value))
            {
                foreach ($value as $v)
                {
                    $this->iblockPrimary[ $v ] = $v;
                }

                return true;
            }
            else
            {
                $this->iblockPrimary[ $value ] = $value;
            }
            return true;
        }

        return false;
    }

    private function appendIblockFields()
    {
        $maps = func_get_args();
        if (count($maps) === 0) return;

        //TODO Проверить на повторяющиеся свойства и сущности, т.к. запрос может вызываться для двух инфоблоков
        foreach ($maps as $map)
        {
            foreach ($map as $field)
            {
                $this->init_entity->addField($field);
            }
        }
    }
}