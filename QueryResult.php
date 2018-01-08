<?php

/*
 * This file is part of the Pho package.
 *
 * (c) Emre Sokullu <emre@phonetworks.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pho\Kernel\Services\Index\Adapters;

/**
 * Neo4j adapter for Kernel's QueryResult class.
 * 
 * @author Emre Sokullu <emre@phonetworks.org>
 */
class QueryResult extends \Pho\Kernel\Services\Index\QueryResult 
{
     public static function process($results): QueryResult
     {
        $qr = new QueryResult();
        foreach($results->records() as $result) // $result would be a \GraphAware\Bolt\Result\Result 
        {
            $qr->results[] = $result->values()[0]->values();
        }
        $stats = $results->summarize()->updateStatistics();
        $qr->summary["nodesCreated"] = $stats->nodesCreated();
        $qr->summary["nodesDeleted"] = $stats->nodesDeleted();
        $qr->summary["edgesCreated"] = $stats->relationshipsCreated();
        $qr->summary["edgesDeleted"] = $stats->relationshipsDeleted();
        $qr->summary["propertiesSet"] = $stats->propertiesSet();
        $qr->summary["containsUpdates"] = $stats->containsUpdates();
        return $qr;
     }
}