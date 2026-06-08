<?php

namespace app\admin\controller\room;

use app\admin\model\game\Type;
use app\common\controller\Backend;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Env;

/**
 * 游戏管理
 *
 * @icon fa fa-circle-o
 */
class Room extends Backend
{
    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = ['type_name','room_list'];

    /**
     * Room模型对象
     * @var \app\admin\model\Room
     */
    protected $model = null;
    protected $multiFields = 'status,reward_status';


    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Room;
        $this->view->assign("TypeList", Type::select());
    }

    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);
            $TypeList = Type::column('name','id');
            foreach ($list as $row) {
                $row->visible(['id','name','status','dir','history_table_name','type','reward_status','type_id']);
                $row['type_id'] = $TypeList[$row['type_id']]??'';
            }
            $result = array("total" => $list->total(), "rows" => $list->items());
            return json($result);
        }
        return $this->view->fetch();
    }


    public function api_post()
    {
        if ($this->request->isPost()) {
            $room_id = $this->request->post('room_id',0);
            $url = Env::get('app.game_request', '')."/api/config/update";
            $curl = curl_init();
            //设置抓取的url
            curl_setopt($curl, CURLOPT_URL, $url);
            //设置头文件的信息作为数据流输出
            curl_setopt($curl, CURLOPT_HEADER, 0);
            //设置获取的信息以文件流的形式返回，而不是直接输出。
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            //设置post方式提交
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(['room_id'=>$room_id]));
            //https
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            //执行命令
            $data = curl_exec($curl);
            //关闭URL请求
            curl_close($curl);
            return $this->jsonSucc();
        }
        $room_data = $this->model->where('status',1)->column('id,name');
        $this->assign('room_data',$room_data);
        return $this->view->fetch();
    }


    public function room_list()
    {
        $name = $this->request->post('name','');
        $where = [];
        if(!empty($name)){
            $where['name'] = ['like',$name."%"];
        }

        $type_id = $this->request->post('custom.type_id','');
        
        if(!empty($type_id)){
            $where["type_id"] = $type_id;
        }

        $room_data = $this->model->where($where)->where('status',1)->field('id,name')->select();
        $result = array("total" => count($room_data), "list" => $room_data);
        return json($result);

    }

    // 类型选择
    public function type_id_name(){
        return [
            "total"=>10,
            "list"=>[
                // ["id"=>1,"name"=>"wingo"],
                ["id"=>2,"name"=>"pg"],
                // ["id"=>3,"name"=>"gg5"],
                ["id"=>5,"name"=>"kess"],
                ["id"=>6,"name"=>"自研"],
                ["id"=>7,"name"=>"wg"],
                ["id"=>8,"name"=>"ambs"],
                ["id"=>9,"name"=>"cp"],
                ["id"=>10,"name"=>"uu"],
                ["id"=>11,"name"=>"pp"],
            ],
        ];
    }


  

    public function type_name()
    {
        $type_data = Type::column('id,name');
        $json = json($type_data);
        return $json;
    }



    public function batch_updates($ids)
    {
        if (false === $this->request->isPost()) {
            return $this->view->fetch();
        } else {
            $params = $this->request->post('row/a');
            $this->model->where('id','in',$ids)->update(['type_id'=>$params['type_id']]);
            $this->success('配置成功');
        }
    }


}
