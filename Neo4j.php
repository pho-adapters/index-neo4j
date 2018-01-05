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

use Pho\Kernel\Kernel;
use Pho\Kernel\Services\ServiceInterface;
use Pho\Kernel\Services\Index\IndexInterface;
use Pho\Lib\Graph\EntityInterface;

/**
 * Neo4j indexing adapter
 *
 * @author Emre Sokullu
 */
class Neo4j implements IndexInterface, ServiceInterface
{
     /**
     * Pho-kernel 
     * @var \Pimple
     */
    protected $kernel;

    /**
     * Neo4j DB
     * @var \Elasticsearch\Client
     */
    protected $db;

    /**
     * default index name for pho-indexing
     * @var string
     */
//    private $dbname    = 'phonetworks';


    /**
     * Setup function.
     * Init elasticsearch connection. Run indexing on runned events
     * @param Kernel $kernel Kernel of pho
     * @param array  $params Sended params to the index.
     */
    public function __construct(Kernel $kernel, array $params = [])
    {
        $this->kernel = $kernel;
        // connect
        // set up listeners to index

        // $kernel->on('kernel.booted_up', array($this, 'kernelBooted'));
    }


    /**
     * Searches through the index with given key and its value.
     *
     * @param string $query Cypher query
     * @param array $param Query params. Optional.
     *
     * @return array
     */
    public function query(string $query, array $params = array()) // : mixed
    {
        return $this->searchInIndex($value, $key, $classes);
    }


    public function kernelBooted()
    {
       // var_dump('Kernel booted');
       // $this->kernel->graph()->on('node.added', array($this, 'index'));
    }


}
