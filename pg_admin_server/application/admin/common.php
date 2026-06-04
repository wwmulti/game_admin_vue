<?php

use app\common\model\Category;
use fast\Form;
use fast\Tree;
use think\Db;
use think\Loader;
use think\Session;
use think\Cache;



if (!function_exists('LogIdentityVerification')) {
    function LogIdentityVerification($type='',$mfield = 'merchant_id',$bfield = 'business_id',$pfield = 'platform_id')
    {
        if($type){
            $pfield = "{$type}.{$pfield}";
            $mfield = "{$type}.{$mfield}";
            $bfield = "{$type}.{$bfield}";
        }

        $admin = Session::get('admin');
        $where_array = [];
        $login_identity = $admin['login_identity'];//登录身份：0,管理员,1:平台，2：业务员队长，3：业务员
        $second_identity = Cache::store('redis')->get("second_identity:" . $admin['id']) ?: 0;//第二身份：0,管理员,1:平台，2：代理团队，3：业务员
        $choice_platform_id = Cache::store('redis')->get("platform_id:" . $admin['id']) ?: 0;//平台ID
        $choice_merchant = Cache::store('redis')->get("merchant_id:" . $admin['id']) ?: 0;//代理团队ID
        $choice_business = Cache::store('redis')->get("business_id:" . $admin['id']) ?: 0;//业务员ID
        if ($login_identity == 0) {
            if ($second_identity == 1) {
                $where_array[$pfield] = ['eq', $choice_platform_id];
            } else if ($second_identity == 2) {
                $where_array[$pfield] = ['eq', $choice_platform_id];
                if($mfield){
                    $where_array[$mfield] = ['eq', $choice_merchant];
                }
            } else if ($second_identity == 3) {
                $where_array[$pfield] = ['eq', $choice_platform_id];
                if($mfield){
                    $where_array[$mfield] = ['eq', $choice_merchant];
                }
                if($bfield){
                    $where_array[$bfield] = ['eq', $choice_business];
                }
            }
        } else if ($login_identity === 1) {
            $admin = session('admin');
            if($second_identity == 1){
                $where_array[$pfield] = ['eq', $admin['platform_id']];
                if(isset($admin['platform_ids'])) {
                    if (!empty($admin['platform_ids'])) {
                        $where_array[$pfield] = ['eq', $choice_platform_id];
                    }
                }
            }else if($second_identity == 2){
                $where_array[$pfield] = ['eq', $choice_platform_id];
                if($mfield){
                    $where_array[$mfield] = ['eq', $choice_merchant];
                }
            }else if($second_identity == 3){
                if($bfield){
                    $where_array[$bfield] = ['eq', $choice_business];
                }
            }else{
                if (isset($admin['platform_ids']) && !empty($admin['platform_ids'])) {
                    $where_array[$pfield] = ['in', $admin['platform_ids']];
                } else {
                    $where_array[$pfield] = ['eq', $admin['platform_id']];
                }
            }
        } else if ($login_identity === 2) {
            if ($second_identity == 3) {
                if($bfield){
                    $where_array[$bfield] = ['eq', $choice_business];
                }
            }
            if($mfield){
                $where_array[$mfield] = ['eq', $admin['merchant_id']];
            }
        } else if ($login_identity === 3) {
            if($bfield){
                $where_array[$bfield] = ['eq', $admin['business_id']];
            }
        }
        return $where_array;
    }
}



if (!function_exists('build_select')) {

    /**
     * 生成下拉列表
     * @param string $name
     * @param mixed  $options
     * @param mixed  $selected
     * @param mixed  $attr
     * @return string
     */
    function build_select($name, $options, $selected = [], $attr = [])
    {
        $options = is_array($options) ? $options : explode(',', $options ?? '');
        $selected = is_array($selected) ? $selected : explode(',', $selected ?? '');
        return Form::select($name, $options, $selected, $attr);
    }
}

if (!function_exists('build_radios')) {

    /**
     * 生成单选按钮组
     * @param string $name
     * @param array  $list
     * @param mixed  $selected
     * @return string
     */
    function build_radios($name, $list = [], $selected = null)
    {
        $html = [];
        $selected = is_null($selected) ? key($list) : $selected;
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        foreach ($list as $k => $v) {
            $html[] = sprintf(Form::label("{$name}-{$k}", "%s " . str_replace('%', '%%', $v)), Form::radio($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
        }
        return '<div class="radio">' . implode(' ', $html) . '</div>';
    }
}

if (!function_exists('build_checkboxs')) {

    /**
     * 生成复选按钮组
     * @param string $name
     * @param array  $list
     * @param mixed  $selected
     * @return string
     */
    function build_checkboxs($name, $list = [], $selected = null)
    {
        $html = [];
        $selected = is_null($selected) ? [] : $selected;
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        foreach ($list as $k => $v) {
            $html[] = sprintf(Form::label("{$name}-{$k}", "%s " . str_replace('%', '%%', $v)), Form::checkbox($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
        }
        return '<div class="checkbox">' . implode(' ', $html) . '</div>';
    }
}


if (!function_exists('build_category_select')) {

    /**
     * 生成分类下拉列表框
     * @param string $name
     * @param string $type
     * @param mixed  $selected
     * @param array  $attr
     * @param array  $header
     * @return string
     */
    function build_category_select($name, $type, $selected = null, $attr = [], $header = [])
    {
        $tree = Tree::instance();
        $tree->init(Category::getCategoryArray($type), 'pid');
        $categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = $header ? $header : [];
        foreach ($categorylist as $k => $v) {
            $categorydata[$v['id']] = $v['name'];
        }
        $attr = array_merge(['id' => "c-{$name}", 'class' => 'form-control selectpicker'], $attr);
        return build_select($name, $categorydata, $selected, $attr);
    }
}

if (!function_exists('build_toolbar')) {

    /**
     * 生成表格操作按钮栏
     * @param array $btns 按钮组
     * @param array $attr 按钮属性值
     * @return string
     */
    function build_toolbar($btns = null, $attr = [])
    {
        $auth = \app\admin\library\Auth::instance();
        $controller = str_replace('.', '/', Loader::parseName(request()->controller()));
        $btns = $btns ? $btns : ['refresh', 'add', 'edit', 'del', 'import'];
        $btns = is_array($btns) ? $btns : explode(',', $btns);
        $index = array_search('delete', $btns);
        if ($index !== false) {
            $btns[$index] = 'del';
        }
        $btnAttr = [
            'refresh' => ['javascript:;', 'btn btn-primary btn-refresh', 'fa fa-refresh', '', __('Refresh')],
            'add'     => ['javascript:;', 'btn btn-success btn-add', 'fa fa-plus', __('Add'), __('Add')],
            'edit'    => ['javascript:;', 'btn btn-success btn-edit btn-disabled disabled', 'fa fa-pencil', __('Edit'), __('Edit')],
            'del'     => ['javascript:;', 'btn btn-danger btn-del btn-disabled disabled', 'fa fa-trash', __('Delete'), __('Delete')],
            'import'  => ['javascript:;', 'btn btn-info btn-import', 'fa fa-upload', __('Import'), __('Import')],
        ];
        $btnAttr = array_merge($btnAttr, $attr);
        $html = [];
        foreach ($btns as $k => $v) {
            //如果未定义或没有权限
            if (!isset($btnAttr[$v]) || ($v !== 'refresh' && !$auth->check("{$controller}/{$v}", $auth->id))) {
                continue;
            }
            list($href, $class, $icon, $text, $title) = $btnAttr[$v];
            //$extend = $v == 'import' ? 'id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"' : '';
            //$html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '" ' . $extend . '><i class="' . $icon . '"></i> ' . $text . '</a>';
            if ($v == 'import') {
                $template = str_replace('/', '_', $controller);
                $download = '';
                if (file_exists("./template/{$template}.xlsx")) {
                    $download .= "<li><a href=\"/template/{$template}.xlsx\" target=\"_blank\">XLSX模版</a></li>";
                }
                if (file_exists("./template/{$template}.xls")) {
                    $download .= "<li><a href=\"/template/{$template}.xls\" target=\"_blank\">XLS模版</a></li>";
                }
                if (file_exists("./template/{$template}.csv")) {
                    $download .= empty($download) ? '' : "<li class=\"divider\"></li>";
                    $download .= "<li><a href=\"/template/{$template}.csv\" target=\"_blank\">CSV模版</a></li>";
                }
                $download .= empty($download) ? '' : "\n                            ";
                if (!empty($download)) {
                    $html[] = <<<EOT
                        <div class="btn-group">
                            <button type="button" href="{$href}" class="btn btn-info btn-import" title="{$title}" id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"><i class="{$icon}"></i> {$text}</button>
                            <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" title="下载批量导入模版">
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu" role="menu">{$download}</ul>
                        </div>
EOT;
                } else {
                    $html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '" id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"><i class="' . $icon . '"></i> ' . $text . '</a>';
                }
            } else {
                $html[] = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '"><i class="' . $icon . '"></i> ' . $text . '</a>';
            }
        }
        return implode(' ', $html);
    }
}

if (!function_exists('build_heading')) {

    /**
     * 生成页面Heading
     *
     * @param string $path 指定的path
     * @return string
     */
    function build_heading($path = null, $container = true)
    {
        $title = $content = '';
        if (is_null($path)) {
            $action = request()->action();
            $controller = str_replace('.', '/', Loader::parseName(request()->controller()));
            $path = strtolower($controller . ($action && $action != 'index' ? '/' . $action : ''));
        }
        // 根据当前的URI自动匹配父节点的标题和备注
        $data = Db::name('auth_rule')->where('name', $path)->field('title,remark')->find();
        if ($data) {
            $title = __($data['title']);
            $content = __($data['remark']);
        }
        if (!$content) {
            return '';
        }
        $result = '<div class="panel-lead"><em>' . $title . '</em>' . $content . '</div>';
        if ($container) {
            $result = '<div class="panel-heading">' . $result . '</div>';
        }
        return $result;
    }
}
