<?php
/**
 * Created by PhpStorm.
 * User: ly
 * Date: 2019/1/28
 * Time: 15:15
 */
namespace EasySwooleMiddleware\Middleware;

class MiddlewareOption
{

    /**
     * @var
     * 服务验证数组
     */
    protected $options;

    /**
     * MiddinstanOption constructor.
     * @param $options
     */
    function __construct(&$options)
    {
        $this->options = &$options;
    }

    /**
     * @param array $method
     * @return null|object
     * 服务黑名单
     */
    function except($method = []): ?object
    {
        $this->options['except'] = is_array($method) ? $method :func_get_args();
        return $this;
    }

    /**
     * @param array $method
     * @return null|object
     * 服务白名单
     */
    function only($method = []): ?object
    {
        $this->options['only'] = is_array($method) ? $method :func_get_args();
        return $this;
    }
}