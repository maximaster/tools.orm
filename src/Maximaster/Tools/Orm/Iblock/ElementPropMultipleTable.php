<?
namespace Maximaster\Tools\Orm\Iblock;
use Bitrix\Main\Entity;
use Bitrix\Main;
use Maximaster\Tools\Helpers\IblockStructure;

abstract class ElementPropMultipleTable extends Entity\DataManager
{
    protected static $iblockId;
    protected static $iblockCode;

    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_iblock_element_prop_m' . static::$iblockId;
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            'ID' => array(
                'data_type' => 'integer',
                'primary' => true,
                'autocomplete' => true,
                'title' => 'ID',
            ),
            'IBLOCK_ELEMENT_ID' => array(
                'data_type' => 'integer',
                'required' => true,
            ),
            'IBLOCK_PROPERTY_ID' => array(
                'data_type' => 'integer',
                'required' => true,
            ),
            'VALUE' => array(
                'data_type' => 'string',
            ),
            'DESCRIPTION' => array(
                'data_type' => 'string',
            ),
            'VALUE_ENUM' => array(
                'data_type' => 'integer',
            ),
            'VALUE_NUM' => array(
                'data_type' => 'float',
            ),
            new Entity\ReferenceField(
                'PROPERTY',
                '\Bitrix\Iblock\Property',
                array('this.IBLOCK_PROPERTY_ID' => 'ref.ID')
            ),
        );
    }

    /**
     * @param $iblockCode
     * @return Entity\DataManager
     * @throws Main\ArgumentException
     */
    public static function getInstance($iblockCode)
    {
        $iblock = IblockStructure::iblock($iblockCode);
        if (!$iblock)
        {
            throw new Main\ArgumentException('Указан код несуществующего инфоблока');
        }

        self::$iblockCode = $iblockCode;
        self::$iblockId = $iblock['ID'];

        return self::compileEntity();
    }

    private static function compileEntity()
    {
        $class = 'Iblock' . static::$iblockId . 'MultiplePropertyTable';
        if (!class_exists($class))
        {
            $eval = "class {$class} extends " . __CLASS__ . "{}";

            eval($eval);
        }

        return new $class;
    }
}
