<?php
/**
 * Created by PhpStorm.
 * User: ly
 * Date: 2019/1/28
 * Time: 15:09
 */
namespace EasySwooleMiddleware\Middleware;
use Closure;
use ReflectionClass;
use EasySwooleMiddleware\Middleware\MiddlewareOption;


class Middleware{

    /**
     * @var string
     * 默认执行方法
     */
    private $method = 'handel';

    /**
     * @var
     * 请求实例
     */
    private $request;

    /**
     * @var object
     * 响应实例
     */
    private $response;

    /**
     * @var array
     * 服务列表
     */
    private $middlewareList = [
        'web'=>\App\HttpController\Common\Web::class
    ];

    /**
     * @var array
     * 服务实例
     */
    public $middleware = [];

    /**
     * Auth constructor.
     * @param object $request
     * @param object $response
     */
    public function __construct(object &$request,object &$response)
    {
        $this->request = &$request;
        $this->response = &$response;
    }

    /**
     * 中间件实例
     * @param $serviceList
     * @param array $options
     * @return \EasySwoole\Middleware\MiddlewareOption
     */
    public function middleware($serviceList,array $options = []) :?object
    {

        $this->build($serviceList,$options);
        return new MiddlewareOption($options);
    }

    /**
     * 中间件执行入口
     * @param $action
     * @return bool|null
     */
    public function run($action)
    {
        if($this->middleware){
            $this->request->errMsg = '';
            $this->request->actionName = $action;
            return $this->then(function($request){
                if(isset($request->errMsg) && !empty($request->errMsg)){
                    return false;
                }
                return true;
            });
        }else{
            $this->request->errMsg = 'middleware not empty';
            return false;
        }
    }

    /**
     * 中间件执行
     * @param $passable
     * @return mixed
     */
    protected function then($passable)
    {
        $pipes = array_reverse($this->middleware);
        return call_user_func(array_reduce($pipes,$this->getSlice(),$passable),$this->request);
    }

    /**
     * 绑定中间件
     * @param $serviceList
     * @param array $options
     * @throws \Exception
     */
    protected function build($serviceList,array &$options)
    {

            foreach ((array)$serviceList as $item) {
                if (is_object($item)) {
                    $this->bind($item, $options);
                } elseif ($item instanceof Closure) {
                    $this->bind($item, $options);
                } elseif ($this->middlewareList[$item]) {
                    $this->make($this->middlewareList[$item], $options);
                } elseif (is_string($item) && class_exists($item)) {
                    $this->make($item, $options);
                } else {
                    throw new MiddlewareException("服务无法解析：".$item);
                }
            }

    }

    /**
     * 中间件实例
     * @param $services
     * @param array $options
     * @return bool
     */
    protected function make($services,array &$options)
    {
        if(is_string($services)){
            $class = new ReflectionClass($services);
            if($class->isInstantiable()){
                if(property_exists($class->newInstance(),'except')){
                    $options = $class->newInstance()->except;
                }
                $this->bind($class->newInstance(),$options);
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 中间件参数绑定
     * @param $instance
     * @param array $options
     */
    protected function bind($instance,array &$options){
        $this->middleware[] = [
            'middleware' =>$instance,
            'options' =>&$options
        ];
    }


    /**
     * 中间件包装
     * @return Closure
     */
    protected function getSlice()
    {
        return function ($stack, $pipe) {
            return function ($request) use ($stack, $pipe) {
                if($this->validation($this->request->actionName,$pipe['options'])) {
                    if ($pipe['middleware'] instanceof Closure) {
                        return call_user_func($pipe['middleware'], $request, $stack);
                    } else {
                        return call_user_func_array([$pipe['middleware'], $this->method], [$request, $stack]);
                    }
                }else{
                    return false;
                }
            };
        };
    }

    /**
     * 方法过滤
     * @param $action
     * @param array $options
     * @return bool|null
     */
    public function validation($action,array $options): ?void
    {
        if(isset($options['except'])){
            if(is_string($options['except'])){
                $options['except'] = explode(',',$options['except']);
            }

            if(in_array($action,$options['except'])){
                $this->request->errMsg = $action.' Method has no permission to access';
                return false;
            }
        }
        if(isset($options['only'])){
            if(is_string($options['only'])){
                $options['only'] = explode(',',$options['only']);
            }
            if(!in_array($action,$options['only'])){
                $this->request->errMsg = $action.' Method has no permission to access';
                return false;
            }
        }
        return true;
    }

}