<?php

namespace app\admin\controller\ttpg;

use app\common\controller\Backend;
use app\admin\model\RoomGroupConfig;
use think\Db;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

class Template extends Backend
{
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new RoomGroupConfig;
    }

    /**
     * 查看
     */
    public function index($ids=null)
    {
        list($where, $sort, $order, $offset, $limit) = $this->buildparams([], ['ids']);
        $list = $this->model
            ->where('room_id', $ids)
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);
        $result = array("total" => $list->total(), "rows" => $list->items());
        return $this->jsonSucc($result);
    }

    /**
     * 添加
     *
     * @return string
     * @throws \think\Exception
     */
    public function add($ids=null)
    {
        $params = $this->request->post();
        if (empty($params)) {
            return $this->jsonFail(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);

        if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
            $params[$this->dataLimitField] = $this->auth->id;
        }

        $group_ids = stripslashes($params['group_ids']);
        $params['group_ids'] = trim($group_ids);
        $params['room_id'] = $ids;
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException()->validate($validate);
            }
            $result = $this->model->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            return $this->jsonFail($e->getMessage());
        }
        if ($result === false) {
            return $this->jsonFail(__('No rows were inserted'));
        }
        return $this->jsonSucc($result);
    }




    /**
     * 编辑
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            return $this->jsonFail(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds) && !in_array($row[$this->dataLimitField], $adminIds)) {
            return $this->jsonFail(__('You have no permission'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post();
        if (empty($params)) {
            return $this->jsonFail(__('Parameter %s can not be empty', ''));
        }
        $params = $this->preExcludeFields($params);
        $group_ids = stripslashes($params['group_ids']);
        $params['group_ids'] = trim($group_ids);
        $result = false;
        Db::startTrans();
        try {
            //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                $row->validateFailException()->validate($validate);
            }
            $result = $row->allowField(true)->save($params);
            Db::commit();
        } catch (ValidateException|PDOException|\Exception $e) {
            Db::rollback();
            return $this->jsonFail($e->getMessage());
        }
        if (false === $result) {
            return $this->jsonFail(__('No rows were updated'));
        }
        return $this->jsonSucc($result);
    }

}
