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
use GraphAware\Neo4j\Client\ClientBuilder;

/**
 * Neo4j indexing adapter
 * 
 * Bolt mode of connection is recommended. Bolt is stateful and binary, 
 * hence more efficient than HTTP or HTTPS.
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
     * Neo4J Client
     * @var \GraphAware\Neo4j\Client\Client
     */
    protected $client;

    protected $logger;


    /**
     * Setup function.
     * Init neo4j connection. Run indexing on kernel signals.
     * Only neo4j bolt connection is accepted.
     * 
     * @param Kernel $kernel Kernel of pho
     * @param array  $params Sended params to the index.
     */
    public function __construct(Kernel $kernel, array $params = [])
    {
        $this->kernel = $kernel;
        $this->logger = $kernel->logger();
     
        $this->client = ClientBuilder::create()
            ->addConnection($params["scheme"], $this->_unparse_url($params)) 
            ->build();
        
        $this->kernel->events()->on('kernel.booted_up', function() {
            $this->subscribeGraphsystem();
        });
    }

    protected function subscribeGraphsystem(): void
    {
        $this->kernel->events()->on('graphsystem.touched',  
            function(array $var) {
                $this->index($var);
            })
            ->on('graphsystem.node_deleted',  
            function(string $id) {
                $this->nodeDeleted($id);
            })
            ->on('graphsystem.edge_deleted',  
            function(string $id) {
                $this->edgeDeleted($id);
            }
        );
    }

    /**
     * Helper method to form a URL 
     *
     * Does the exact opposite of PHP's parse_url function.
     * 
     * @see http://php.net/manual/en/function.parse-url.php#106731 for original snippet
     * 
     * @param array $parsed_url
     * @return string
     */
    private function _unparse_url(array $parsed_url): string 
    { 
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
        $pass     = ($user || $pass) ? "$pass@" : ''; 
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
        return "$scheme$user$pass$host$port$path$query$fragment"; 
    } 

    /**
     * Searches through the index with given key and its value.
     *
     * @param string $query Cypher query
     * @param array $param Query params. Optional.
     *
     * @return mixed In this case, it's
     */
    public function query(string $query, array $params = array()) // : mixed
    {
        return $this->client->run($query, $params);
    }

    /**
     * Direct access to the Neo4J client
     * 
     * This class does also provide direct read-only access to the 
     * client, for debugging purposes.
     *
     * @return \GraphAware\Neo4j\Client\Client
     */
    public function client(): \GraphAware\Neo4j\Client\Client
    {
        return $this->client;
    }

    public function index(array $entity): void 
    {
        $this->logger->info("Index request received by %s, a %s.", $entity["id"], $entity["label"]);
        $header = (int) substr($entity["id"],0, 1);
        if($header>0 && $header<6) 
        {
            $this->logger->info("Header qualifies it to be indexed");
            $entity["attributes"]["udid"] = $entity["id"];
            $cq = sprintf("MERGE (n:%s {udid: {udid}}) SET n = {data}", $entity["label"]);
            $this->logger->info(
                "The query will be as follows; %s with data ", 
                $cq
            //    print_r($entity["attributes"], true)
            );
            $result = $this->client->run($cq, [
                "udid" => $entity["id"],
                "data" => $entity["attributes"]
            ]);
        }
        $this->logger->info("Moving on");
    }

    public function nodeDeleted(string $id): void 
    {
        $this->logger->info("Node deletion request received by %s.", $id);
        $cq = "MATCH (n {udid: {udid}}) OPTIONAL MATCH (n)-[e]-()  DELETE e, n";
        $this->client->run($cq, ["udid"=>$id]);
        $this->logger->info("Node deleted. Moving on.");
    }

    public function edgeDeleted(string $id): void
    {
        $this->logger->info("Edge deletion request received by %s.", $id);
        $cq = "MATCH ()-[e {udid: {udid}}]-()  DELETE e";
        $this->client->run($cq, ["udid"=>$id]);
        $this->logger->info("Edge deleted. Moving on.");
    }


}
