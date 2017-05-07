<?php

namespace ApiApp\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Auth
{

    /**
     * isValidApiKey
     * Check whether a key-secret combination is valid and authorized or not
     * The use of this function is now for testing purposes only as I did not fully implement authorization
     * @param string $key       Client key
     * @param string $secret    Client secret
     * @return bool
     */
    private function isValidApiKey($key, $secret)
    {
        // Redis connection
        /*$reader = new \Mcustiel\Config\Drivers\Reader\yaml\Reader();
        $reader->read(__DIR__ . "/../../../config/database.yml");
        $config = $reader->getConfig();

        $client = new \Predis\Client([
            'scheme' => $config->get('auth')->get('schema'),
            'host'   => $config->get('auth')->get('host'),
            'port'   => $config->get('auth')->get('port'),
        ]);*/

        $api_keys_source = array(
            'VF9ExGI0Ww' => "-Nz!1H5h?Jb~4@AiI;T<%l45C)sTh-",
            '4WWJsOE3gH' => "v]&&1p1b]11oiI6sAiD9tV|{n88Rt;"
        );
        return (isset($api_keys_source[$key]) && $api_keys_source[$key] = $secret);
    }

    /**
     * authorizeReuest
     * Check if user request should be authorized or not
     * @param Request $request  User request
     * @return bool|Response
     */
    public function authorizeRequest(Request $request)
    {
        // Read authorization header
        $authorizationHeader = $request->headers->get('authorization', FALSE);

        if(!$authorizationHeader){
            // No token provided
            return new Response(json_encode(['error'=>'Bad Request!', 'msg'=>'No authentication token provided']), 400);
        }

        list($jwt) = sscanf( $authorizationHeader, 'Bearer %s'); // Get token from the header

        if ($jwt){

            try {
                // Get JWT key from configuration
                $key =  \HelloFresh\App::getConfig('auth:jwt_key');

                // eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJrZXkiOiJWRjlFeEdJMFd3Iiwic2VjcmV0IjoiLU56ITFINWg_SmJ-NEBBaUk7VDwlbDQ1QylzVGgtIn0.ZSTZoyt0rQFZqJJf9QLh3TXk2F1sZrcoDk-owoyI4fo
                // eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJrZXkiOiI0V1dKc09FM2dIIiwic2VjcmV0Ijoidl0mJjFwMWJdMTFvaUk2c0FpRDl0Vnx7bjg4UnQ7In0.asg5Zxzqd8dD5bCBeIonTiD_LgK1k2_BQDjPr3KqfJU

                $decoded = (array) \Firebase\JWT\JWT::decode($jwt, $key, array(\HelloFresh\App::getConfig('auth:algorithm')));

                if((!empty($decoded['key']) && !empty($decoded['secret'])) && $this->isValidApiKey($decoded['key'],$decoded['secret'])){
                    return TRUE;
                }
                else{
                    // 401 Unauthorized
                    return new Response(json_encode(['error'=>'Unauthorized!', 'msg'=>'You have a wrong API key']), 401);
                }

            }
            catch ( \Exception $e ){
                // 401 Unauthorized
                return new Response(json_encode(['error'=>'Unauthorized!', 'msg'=>'You have a wrong API key']), 401);
                // log($e->getMessage());
            }
        }
        else{
            // 400 Bad Request
            return new Response(json_encode(['error'=>'Bad Request!', 'msg'=>'No authentication token provided']), 400);
        }
    }
}

