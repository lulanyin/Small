<?php
/**
 * DB快捷方式
 * @return \DB\Query\QueryBuilder
 */
function db() : \DB\Query\QueryBuilder{
    return \DB\DB::getQuery();
}