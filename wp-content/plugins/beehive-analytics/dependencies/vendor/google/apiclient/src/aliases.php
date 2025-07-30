<?php

namespace Beehive;

if (\class_exists('Beehive\\Google_Client', \false)) {
    // Prevent error with preloading in PHP 7.4
    // @see https://github.com/googleapis/google-api-php-client/issues/1976
    return;
}
$classMap = ['Beehive\\Google\\Client' => 'Google_Client', 'Beehive\\Google\\Service' => 'Google_Service', 'Beehive\\Google\\AccessToken\\Revoke' => 'Google_AccessToken_Revoke', 'Beehive\\Google\\AccessToken\\Verify' => 'Google_AccessToken_Verify', 'Beehive\\Google\\Model' => 'Google_Model', 'Beehive\\Google\\Utils\\UriTemplate' => 'Google_Utils_UriTemplate', 'Beehive\\Google\\AuthHandler\\Guzzle6AuthHandler' => 'Google_AuthHandler_Guzzle6AuthHandler', 'Beehive\\Google\\AuthHandler\\Guzzle7AuthHandler' => 'Google_AuthHandler_Guzzle7AuthHandler', 'Beehive\\Google\\AuthHandler\\AuthHandlerFactory' => 'Google_AuthHandler_AuthHandlerFactory', 'Beehive\\Google\\Http\\Batch' => 'Google_Http_Batch', 'Beehive\\Google\\Http\\MediaFileUpload' => 'Google_Http_MediaFileUpload', 'Beehive\\Google\\Http\\REST' => 'Google_Http_REST', 'Beehive\\Google\\Task\\Retryable' => 'Google_Task_Retryable', 'Beehive\\Google\\Task\\Exception' => 'Google_Task_Exception', 'Beehive\\Google\\Task\\Runner' => 'Google_Task_Runner', 'Beehive\\Google\\Collection' => 'Google_Collection', 'Beehive\\Google\\Service\\Exception' => 'Google_Service_Exception', 'Beehive\\Google\\Service\\Resource' => 'Google_Service_Resource', 'Beehive\\Google\\Exception' => 'Google_Exception'];
foreach ($classMap as $class => $alias) {
    \class_alias($class, $alias);
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
class Google_Task_Composer extends \Beehive\Google\Task\Composer
{
}
/**
 * This class needs to be defined explicitly as scripts must be recognized by
 * the autoloader.
 */
\class_alias('Beehive\\Google_Task_Composer', 'Google_Task_Composer', \false);
/** @phpstan-ignore-next-line */
if (\false) {
    class Google_AccessToken_Revoke extends \Beehive\Google\AccessToken\Revoke
    {
    }
    class Google_AccessToken_Verify extends \Beehive\Google\AccessToken\Verify
    {
    }
    class Google_AuthHandler_AuthHandlerFactory extends \Beehive\Google\AuthHandler\AuthHandlerFactory
    {
    }
    class Google_AuthHandler_Guzzle6AuthHandler extends \Beehive\Google\AuthHandler\Guzzle6AuthHandler
    {
    }
    class Google_AuthHandler_Guzzle7AuthHandler extends \Beehive\Google\AuthHandler\Guzzle7AuthHandler
    {
    }
    class Google_Client extends \Beehive\Google\Client
    {
    }
    class Google_Collection extends \Beehive\Google\Collection
    {
    }
    class Google_Exception extends \Beehive\Google\Exception
    {
    }
    class Google_Http_Batch extends \Beehive\Google\Http\Batch
    {
    }
    class Google_Http_MediaFileUpload extends \Beehive\Google\Http\MediaFileUpload
    {
    }
    class Google_Http_REST extends \Beehive\Google\Http\REST
    {
    }
    class Google_Model extends \Beehive\Google\Model
    {
    }
    class Google_Service extends \Beehive\Google\Service
    {
    }
    class Google_Service_Exception extends \Beehive\Google\Service\Exception
    {
    }
    class Google_Service_Resource extends \Beehive\Google\Service\Resource
    {
    }
    class Google_Task_Exception extends \Beehive\Google\Task\Exception
    {
    }
    interface Google_Task_Retryable extends \Beehive\Google\Task\Retryable
    {
    }
    class Google_Task_Runner extends \Beehive\Google\Task\Runner
    {
    }
    class Google_Utils_UriTemplate extends \Beehive\Google\Utils\UriTemplate
    {
    }
}
