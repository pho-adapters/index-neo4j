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

use GraphAware\Bolt\Result\Type\Node;
use GraphAware\Neo4j\Client\Formatter\Type\Node as Node2;
use GraphAware\Bolt\Result\Type\Relationship;
use GraphAware\Neo4j\Client\Formatter\Type\Relationship as Relationship2;

/**
 * Neo4j adapter for Kernel's QueryResult class.
 * 
 * @author Emre Sokullu <emre@phonetworks.org>
 */
class QueryResult extends \Pho\Kernel\Services\Index\QueryResult 
{
    /**
     * Constructor
     *
     * @param  $results
     */
     public function __construct($results)
     {
         //error_log("Neo4J QueryResult executing");
         //error_log("Resuts is a: ".get_class($results));
         //error_log("Resuts are: ".print_r($results->records(), true));
         $i = 0;
        foreach($results->records() as $result) // $result would be a \GraphAware\Bolt\Result\Result 
        {
            $keys = $result->keys();
            $values = $result->values();
            foreach($values as $k=>$value) {
                if( 
                    $value instanceof Node || $value instanceof Node2 
                    || $value instanceof Relationship || $value instanceof Relationship2 
                ) { // if $value->values() then it's like n
                    $_ = $value->values();
                    foreach($_ as $old_key=>$v) {
                        $new_key = $keys[$k].".".$old_key;
                        $this->results[$i][$new_key] = $v;
                        unset($this->results[$i][$old_key]);
                    }
                }
                else { // otherwise it's like n.something as Something
                    $this->results[$i][($keys[$k])] = $value;
                }
            }
            $i++;
        }
        $stats = $results->summarize()->updateStatistics();
         if(!is_null($stats)) {
        $this->summary["nodesCreated"] = $stats->nodesCreated();
        $this->summary["nodesDeleted"] = $stats->nodesDeleted();
        $this->summary["edgesCreated"] = $stats->relationshipsCreated();
        $this->summary["edgesDeleted"] = $stats->relationshipsDeleted();
        $this->summary["propertiesSet"] = $stats->propertiesSet();
        $this->summary["containsUpdates"] = $stats->containsUpdates();
         }
         else {
             $this->summary["nodesCreated"] = 0;
            $this->summary["nodesDeleted"] = 0;
            $this->summary["edgesCreated"] = 0;
            $this->summary["edgesDeleted"] = 0;
            $this->summary["propertiesSet"] =0;
            $this->summary["containsUpdates"] = 0;
         }
     }
}
