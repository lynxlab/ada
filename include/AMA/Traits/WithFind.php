<?php

/**
 * WithFind trait
 *
 * use this trait when you need a datahandler with
 * methods: findBy and findAll.
 *
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\AMA\Traits;

use Jawira\CaseConverter\Convert;
use Lynxlab\ADA\Main\AMA\AbstractAMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use ReflectionClass;
use ReflectionProperty;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

trait WithFind
{
    use WithExceptionClass;
    use WithWhereClause;

    /**
     * loads an array of objects of the passed className with matching where values
     * and ordered using the passed values by performing a select query on the DB
     *
     * @param string $className to use a class from your namespace, this string must start with "\"
     * @param array $whereArr
     * @param array $orderByArr
     * @param AbstractAMADataHandler $dbToUse object used to run the queries. If null, use 'this'
     * @throws Exception
     * @return array
     */
    public function findBy($className, array $whereArr = null, array $orderByArr = null, AbstractAMADataHandler $dbToUse = null)
    {
        $reflection = new ReflectionClass(static::class);
        $namespace = $reflection->getConstant('MODELNAMESPACE');

        if (
            stripos($className, '\\') !== 0 &&
            stripos($className, (string) $namespace) !== 0
        ) {
            $className = $namespace . $className;
        }

        $reflection = new ReflectionClass($className);
        $properties =  array_map(
            fn ($el) => $el->getName(),
            array_filter(
                $reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC),
                fn ($refEl) => $className === $refEl->getDeclaringClass()->getName()
            )
        );

        // get object properties to be loaded as a kind of join
        $joined = $className::loadJoined();
        // and remove them from the query, they will be loaded afterwards
        $properties = array_diff($properties, array_keys($joined));
        $properties = array_diff($properties, $className::doNotLoad());
        // check for customField class const and explode matching propertiy array
        $properties = $className::explodeArrayProperties($properties);
        $properties = array_map(function ($el) use ($className, &$whereArr) {
            if (method_exists($className, 'isUuidField') && $className::isUuidField($el)) {
                if (array_key_exists($el, $whereArr ?? [])) {
                    // fix $whereArr keys accordingly
                    $whereArr[$el . $className::BINFIELDSUFFIX] = $whereArr[$el];
                    unset($whereArr[$el]);
                }
                return $el . $className::BINFIELDSUFFIX;
            }
            return $el;
        }, $properties);

        $sql = sprintf("SELECT %s FROM `%s`", implode(',', array_map(fn ($el) => "`$el`", $properties)), $className::TABLE)
            . static::buildWhereClause($whereArr, $properties) . $this->buildOrderBy($orderByArr, $properties);

        if (is_null($dbToUse)) {
            $dbToUse = $this;
        }

        $result = $dbToUse->getAllPrepared($sql, (!is_null($whereArr) && count($whereArr) > 0) ? array_values($whereArr) : [], AMA_FETCH_ASSOC);
        if (AMADB::isError($result)) {
            throw static::buildException($result->getMessage(), (int)$result->getCode());
        } else {
            $retArr = array_map(fn ($el) => new $className($el, $dbToUse), $result);
            // load properties from $joined array
            foreach ($retArr as $retObj) {
                foreach ($joined as $joinKey => $joinData) {
                    if (array_key_exists('idproperty', $joinData)) {
                        // this is a 1:1 relation, load the linked object using object property
                        $retObj->{$retObj::ADDERPREFIX . ucfirst($joinKey)}(
                            $retObj->{$retObj::GETTERPREFIX . ucfirst($joinData['idproperty'])}(),
                            $dbToUse
                        );
                    } elseif (array_key_exists('reltable', $joinData)) {
                        if (!is_array($joinData['key'])) {
                            $joinData['key'] = [
                                'name' => $joinData['key'],
                                'getter' => (new Convert($retObj::GETTERPREFIX . ucfirst($joinData['key'])))->toCamel(),
                            ];
                        }
                        $joinSelFields = '';
                        if (array_key_exists('relproperties', $joinData) && is_array($joinData['relproperties']) && count($joinData['relproperties']) > 0) {
                            $joinSelFields = ',`' . implode('`,`', $joinData['relproperties']) . '`';
                        }
                        // this is a 1:n relation, load the linked objects querying the relation table
                        $sql = sprintf("SELECT `%s`%s FROM `%s` WHERE `%s`=?", $joinData['extkey'], $joinSelFields, $joinData['reltable'], $joinData['key']['name']);
                        $joinRes = $dbToUse->getAllPrepared($sql, [$retObj->{$joinData['key']['getter']}()], AMA_FETCH_ASSOC);
                        if (array_key_exists('callback', $joinData)) {
                            if (is_callable($joinData['callback'])) {
                                $joinRes = $joinData['callback']($joinRes);
                            } elseif (method_exists($retObj, $joinData['callback'])) {
                                $joinRes = $retObj->{$joinData['callback']}($joinRes);
                            }
                        }
                        $method = new Convert($retObj::SETTERPREFIX . ucfirst($joinKey));
                        $retObj->{$method->toCamel()}($joinRes);
                    }
                }
            }
            return $retArr;
        }
    }

    public function findOneBy($className, array $whereArr = null, array $orderByArr = null, AbstractAMADataHandler $dbToUse = null)
    {
        $retval = $this->findBy($className, $whereArr, $orderByArr, $dbToUse);
        if (is_array($retval) && count($retval) > 0) {
            $retval = reset($retval);
        } else {
            $retval = null;
        }
        return $retval;
    }

    /**
     * loads an array holding all of the passed className objects, possibly ordered.
     * Actually it's an alias for findBy($className, null, $orderby)
     *
     * @param string $className
     * @param array $orderBy
     * @param AbstractAMADataHandler $dbToUse object used to run the queries. If null, use 'this'
     * @return array
     */
    public function findAll($className, array $orderBy = null, AbstractAMADataHandler $dbToUse = null)
    {
        return $this->findBy($className, null, $orderBy, $dbToUse);
    }

    /**
     * Builds an sql orderby clause
     *
     * @param array $orderByArr
     * @param array $properties
     * @return string
     */
    private function buildOrderBy(&$orderByArr, $properties)
    {
        $sql = '';
        if (!is_null($orderByArr) && count($orderByArr) > 0) {
            $invalidProperties = array_diff(array_keys($orderByArr), $properties);
            if (count($invalidProperties) > 0) {
                throw static::buildException(translateFN('Proprietà ORDER BY non valide: ') . implode(', ', $invalidProperties));
            } else {
                $sql .= ' ORDER BY ';
                $sql .= implode(', ', array_map(function ($el) use ($orderByArr) {
                    if (in_array($orderByArr[$el], ['ASC', 'DESC'])) {
                        return "`$el` " . $orderByArr[$el];
                    } else {
                        throw static::buildException(sprintf(translateFN("ORDER BY non valido %s per %s"), $orderByArr[$el], $el));
                    }
                }, array_keys($orderByArr)));
            }
        }
        return $sql;
    }
}
