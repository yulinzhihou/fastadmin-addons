<?php

namespace think\addons;

use think\Container;
use think\facade\Config;
use think\exception\HttpException;
use think\facade\Hook;
use think\Loader;
use think\facade\Request;

/**
 * 插件执行默认控制器
 * @package think\addons
 */
class Route
{

    /**
     * 插件执行
     */
                            /*ueditor           api             config*/
    public function execute($addon = null, $controller = null, $action = null)
    {
        $request = Container::get('request');
        //是否自动转换控制器和操作名
        $convert = Config::get('app.url_convert');/*true*/
        $filter = $convert ? 'strtolower' : 'trim';/*trim*/

        $addon = $addon ? call_user_func($filter, $addon) : '';/*ueditor*/
        $controller = $controller ? call_user_func($filter, $controller) : 'index';/*api*/
        $action = $action ? call_user_func($filter, $action) : 'index';/*config*/
//        dump($addon);
//        dump($controller);
//        dump($action);
        Hook::listen('addon_begin', $request);
        if (!empty($addon) && !empty($controller) && !empty($action))
        {
            $info = get_addon_info($addon);
            /*
             * array (size=8)
                  'name' => string 'ueditor' (length=7)
                  'title' => string '百度ueditor插件' (length=19)
                  'intro' => string '修改后台默认编辑器为ueditor' (length=37)
                  'author' => string '云巅之上' (length=12)
                  'website' => string 'http://www.chenhuai.cc' (length=22)
                  'version' => string '1.0.1' (length=5)
                  'state' => string '1' (length=1)
                  'url' => string '/addons/ueditor.html' (length=20)
            */
//            dump($info);die;
            if (!$info)
            {
                throw new HttpException(404, __('addon %s not found', $addon));
            }
            if (!$info['state'])
            {
                throw new HttpException(500, __('addon %s is disabled', $addon));
            }
            // 设置当前请求的控制器、操作
            $request->controller($controller)->action($action);
            // 监听addon_module_init
            Hook::listen('addon_module_init', $request);
            // 兼容旧版本行为,即将移除,不建议使用
            Hook::listen('addons_init', $request);

            $class = get_addon_class($addon, 'controller', $controller);/*\addons\ueditor\controller\Api*/
//            dump($class);die;
            if (!$class)/*false*/
            {
                throw new HttpException(404, __('addon controller %s not found', Loader::parseName($controller, 1)));
            }
            $instance = new $class();
            $vars = [];
            $action = 'index';
//            dump(is_callable([$instance, $action]));die;
            if (is_callable([$instance, $action]))
            {
                 //执行操作方法
                $call = [$instance, $action];
//                dump($call);die;

            }
            elseif (is_callable([$instance, '_empty']))
            {
                // 空操作
                $call = [$instance, '_empty'];
                $vars = [$action];
            }
            else
            {
                // 操作不存在
                throw new HttpException(404, __('addon action %s not found', get_class($instance) . '->' . $action . '()'));
            }

            Hook::listen('addon_action_begin', $call);

            return call_user_func_array($call, $vars);
        }
        else
        {
            abort(500, lang('addon can not be empty'));
        }
    }

}
