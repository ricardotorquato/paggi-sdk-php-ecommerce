<?php
/**
 * This file holds the basic configuration for the RestClient
 *
 * PHP version 5.6, 7.0, 7.1, 7.2
 *
 * @category RestClient_File
 * @package  Paggi
 * @author   Paggi Integracoes <ti-integracoes@paggi.com>
 * @license  GNU GPLv3 https://www.gnu.org/licenses/gpl-3.0.en.html
 * @link     http://developers.paggi.com
 */

 namespace Paggi\SDK;

 use Paggi\SDK\EnvironmentConfiguration;
 use Paggi\SDK\Interfaces\IRestClient;
 use Doctrine\Common\Inflector\Inflector;

 /**
  * This class verify the RestClient
  *
  * PHP version 5.6, 7.0, 7.1, 7.2
  *
  * @category RestClient_Class
  * @package  Paggi
  * @author   Paggi Integracoes <ti-integracoes@paggi.com>
  * @license  GNU GPLv3 https://www.gnu.org/licenses/gpl-3.0.en.html
  * @link     http://developers.paggi.com
  */
class RestClient implements IRestClient
{
    private static $container;

    public function __construct()
    {
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions('ConfigDI.php');
        self::$container = $builder->build();
    }
    /**
     * Function who will get the environment configuration
     *
     * @return string
     */
    public function getEnvironment()
    {
        $envConfigure = self::$container->get('EnvironmentConfiguration');
        $env = $envConfigure->isStaging();
        if (isset($env) && !is_null($env) && !empty($env)) {
            return $env ? "Stage" : "Production";
        }
        return "Não foi possível localizar o environment";
    }

    /**
     * Function who will set the HTTP method
     *
     * @param string $method Método HTTP
     *
     * @return string
     */
    public function setMethod($method)
    {
        $Possiveis = array(
            "GET",
            "POST",
            "PUT",
            "DELETE"
        );
        $method = in_array(strtoupper($method), $Possiveis)
                ? strtoupper($method) : "";
        return $method;
    }

    /**
     * Function who transform the resource's name in the endpoint
     *
     * @param string $resource Name who will be transformed in the endpoint
     *
     * @return string
     */
    public function getEndPoint($resource)
    {
        $inflector = self::$container->get('Inflector');
        $endPoint = strtolower(
            preg_replace(
                '/(?<!^)[A-Z]/',
                '_$0',
                $inflector->pluralize($resource)
            )
        );
        return $endPoint;
    }

    /**
     * Function who verify if the token will expire within one month
     *
     * @param array $data Array with itens to be added to headers
     *
     * @return string
     */
    public function createHeaders(array $data)
    {
        $headers = [];
        foreach ($data as $key => $value) {
            $headers = array_merge($headers, array($key => $value));
        }
        return (empty($headers) ? [] : ["headers"=>$headers]);
    }

    /**
     * Function who will get the endpoint
     *
     * @param array $data dictionary with data to be put in the request`s body
     *
     * @return string
     */
    public function createBody($data = [])
    {
        $body = array();
        foreach ($data as $key => $value) {
            $body = array_merge($body, array($key => $value));
        }
        return (empty($body) ? [] : ["body"=>$body]);
    }

    /**
     * Function who will mount the request url
     *
     * @param string $endpoint   Token for authentication
     * @param string $env        String with the environment
     * @param array  $parameters Parameters for url
     *
     * @return string
     */
    public function mountUrl($endPoint, $env, $parameters = [])
    {
        $envConfigure = self::$container->get('EnvironmentConfiguration');
        $url = "https://api.";
        $url .= !strcmp($env, "Stage") ? "stg.": "";
        $url .= "paggi.com/v1/";
        $url .= (strcmp($endPoint, "banks"))
                ? "partners/" . $envConfigure->getPartnerId() . "/"
                : "";
        $url .= $endPoint;
        if (!empty($parameters)) {
            $url .= "?";
            $local = "";
            foreach ($parameters as $key => $value) {
                $local = $key . "=" . $value;
                $local .= "&";
            }
            $url .= substr($local, 0, -1);
        }
        return $url;
    }

    /**
     * Function who create a request
     *
     * @param string #method   Method used in the request
     * @param string $url      URL for request
     * @param array  $headers  Headers of the request
     * @param array  $body     Body for the request
     *
     * @return array
     */
    public function createRequest($method, $url, $headers = [], $body = [])
    {
        $client = self::$container->get("GuzzleClient");
        if (in_array($method, ["GET", "DELETE"])) {
            $response = $client->request(
                $method,
                $url,
                $headers
            );
            return $response;
        }
        $response = $client->request(
            $method,
            $url,
            $headers,
            $body
        );
        return $response;
    }
}
