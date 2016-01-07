<?php


namespace Maximaster\Tools\Interfaces;


interface IblockRelatedTableInterface
{
    /**
     * Необходимо в наследнике определить метод, который позволит получить идентификатор инфоблок
     * @return int
     */
    static function getIblockId();
}