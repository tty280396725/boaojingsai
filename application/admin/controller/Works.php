<?php
namespace app\admin\controller;
use app\admin\model\RegisterUser;
use app\admin\model\Ticket;
use think\Db;
use think\Validate;

class Works extends Common
{
    private $cModel;   //当前控制器关联模型
    private $cModel2;

    public function _initialize()
    {
        parent::_initialize();
        $this->cModel2 = new \app\admin\model\Works();   //别名：避免与控制名冲突
        $this->cModel = new \app\admin\model\Grade();
    }

    public function index()
    {
        $where = [];
        if (input('get.search')){
            $where['name|id'] = ['like', '%'.input('get.search').'%'];
        }
        if (input('get._sort')){
            $order = explode(',', input('get._sort'));
            $order = $order[0].' '.$order[1];
        }else{
            $order = 'id desc';
        }

        $dataList = $this->cModel2
            ->where($where)
            ->order($order)
            ->field('id,cid,uid,subject,name,desp,imgs,create_time,update_time')
            ->paginate('', false, page_param())
            ->each(function ($item,$key){
                 /**写处理过程*/
                $item['title'] = model('Competition') ->getComtitionInfo($item['cid'],array('title'))['title'];

            return $item; //返回结果集
        });

        $this->assign('dataList', $dataList);
        return $this->fetch();
    }

    /***
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 书画成绩的列表页
     */
    public function grade(){
        $where = [];
        if (input('get.search')){
            $where['a.id|a.uid|b.name'] = ['like', '%'.input('get.search').'%'];
        }
        if (input('get.cid')){
            $where['a.cid'] = input('get.cid');
            $this ->assign('cid',input('get.cid'));
        }

        if (input('get.is_pass') && in_array(input('get.is_pass'),array(-1,1))){
            $where['a.is_pass'] = input('get.is_pass');
            $this ->assign('is_pass',input('get.is_pass'));
        }

        if (input('get._sort')){
            $order = explode(',', input('get._sort'));
            $order = $order[0].' '.$order[1];
        }else{
            $order = 'a.id desc';
        }

        $compData = model('Competition') ->where('status',1)->where('subject','eq',5)->column('title','id');

        $this ->assign('compData',$compData); //竞赛数据

        foreach ($compData as $k=>$v){
            $idArr[] = $k;
        }

        if (!isset($where['a.cid'])){
            $where['a.cid'] = ['in',$idArr];
        }

        $dataList = $this->cModel->alias('a')
            ->join('apply b','a.aid=b.id')
            ->where($where)
            ->order($order)
            ->field('a.grade,a.id,a.uid,a.is_issue,a.is_pass,a.linkman,a.phone,a.area,a.address,b.subject,a.cid,b.age,b.name,b.pic')
            ->paginate('', false, page_param())
            ->each(function ($item,$key){
            $item['title'] = model('Competition') ->getComtitionInfo($item['cid'],array('title'))['title'];
            return $item;
        });

        $this->assign('dataList', $dataList);
        return $this->fetch();
    }


    /***
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     * 导出书画成绩的功能
     */
    public function formExecl(){
        if (request() ->isGet()){

            $where = [];
            if (input('get.search')){
                $where['a.id|a.uid|b.name'] = ['like', '%'.input('get.search').'%'];
            }
            if (input('get.cid')){
                $where['a.cid'] = input('get.cid');
                $this ->assign('cid',input('get.cid'));
            }

            if (input('get.is_pass') && in_array(input('get.is_pass'),array(-1,1))){
                $where['a.is_pass'] = input('get.is_pass');
                $this ->assign('is_pass',input('get.is_pass'));
            }

            if (input('get._sort')){
                $order = explode(',', input('get._sort'));
                $order = $order[0].' '.$order[1];
            }else{
                $order = 'a.id asc';
            }

            //上面是检索的内容
            $header = array('姓名','科目','年龄','性别','比赛项目','比赛成绩','是否合格','联系人','地区','地址','电话');
            $data =array();
            $filename = '书画成绩表'.date('Y-m-d');


            $dataList = $this->cModel->alias('a') ->join('apply b','a.aid=b.id')->where($where)->order($order)->field('a.grade,a.is_pass,a.linkman,a.phone,a.area,a.address,b.subject,a.cid,b.age,b.name,b.sex') ->paginate('', false, page_param()) ->each(function ($item,$key){

                $item['title'] = model('Competition') ->getComtitionInfo($item['cid'],array('title'))['title'];

                return $item;
            });

            $list =array();
            foreach ($dataList as $k=>$v){
                $list[$k][] = $v ->name;
                $list[$k][] = parent::getnewValue($v ->subject,'subject');
                $list[$k][] = $v ->age;
                $list[$k][] = parent::getnewValue($v ->sex,'sex');
                $list[$k][] = $v ->title;
                $list[$k][] = $v ->grade;
                $list[$k][] = $v ->is_pass;
                $list[$k][] = $v ->linkman;
                $list[$k][] = $v ->area;
                $list[$k][] = $v ->address;
                $list[$k][] = $v ->phone;
            }

            foreach ($list as $k=>$v){
                foreach ($header as $sk => $sv){
                    $data[$k][$header[$sk]] = $v[$sk];
                }
            }

            parent::importExcel($header,$data,$filename);

            die();
        }
    }



    /***
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 上传书画成绩
     */
    public function loadExecl(){

        if (request()->isPost()) {

            $header = array('aid','name','subject','age','title','sex','date','time','grade');

            $result = parent::exportExcel($header);

            if (empty($result)){
                $this ->error('上传格式错误');
                die();
            }

            $applyObj = new \app\index\model\Apply();
            $competitionObj = new \app\admin\model\Competition();

            $compData = model('Competition') ->column('pass_line','id');

            $gradeArr = $this ->cModel ->where('tid',0)->column('cid,uid,aid','id');


            $aidArr = array();
            $checkArr = array(); //判断记录是否存在
            foreach ($gradeArr as $k=>$v){
                $aidArr[$v['aid']] = $k;
                $checkArr[] = $v['aid'];

            }

            $error = array();
            $insert = array();
            $update = array();
            foreach ($result as $k=>$v){
                $aid = intval($v['aid']); //去杂
                if ($aid){
                    $data = $applyObj ->where('id',$aid) ->limit(1)->field('id,cid,uid') ->find();

                    if ($data){
                        $is_pass = self::get_pass_status($v['grade'],$compData[$data['cid']]);
                        if (in_array($aid,$checkArr)){
                            /**该条记录已生成,执行修改分支**/
                            $update[$k]['id'] = $aidArr[$aid];
                            $update[$k]['grade'] = intval($v['grade']);
                            $update[$k]['update_time'] = time();
                            $update[$k]['is_pass'] = $is_pass;
                        }else{
                            /*执行添加操作*/
                            $idsArr[$k]['id'] = $data['id'];
                            $idsArr[$k]['issue'] = 1;

                            $insert[$k]['cid'] = $data['cid'];
                            $insert[$k]['aid'] = $data['id'];
                            $insert[$k]['uid'] = $data['uid'];
                            $insert[$k]['grade'] = intval($v['grade']);
                            $insert[$k]['create_time'] = time();
                            $insert[$k]['is_pass'] = $is_pass;
                        }

                    }else{

                    }
                }
            }

            if (empty($insert) && empty($update)){
                return $this ->error('不能上传空execl');die();
            }

            if (empty($insert) && $update){
                $res3 = $this ->cModel ->isUpdate(true)->saveAll($update); //批量信息grade表的数据
                if ($res3){
                    return $this->success('上传成功');
                }else{
                    return $this ->error('修改失败');
                }
            }

            if (empty($update) && $insert){

                $res = $this ->cModel ->saveAll($insert);
                if ($res){
                    return $this->success('上传成功');
                }else{
                    return $this ->error('修改失败');
                }

            }

            if ($insert && $update){
                $this ->cModel ->saveAll($insert);
                $this ->cModel ->isUpdate(true)->saveAll($update); //批量信息grade表的数据

                return $this->success('上传成功');

            }

        }

    }

    /***
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 下载书画的成绩模板
     */
    public function downloadExecl(){

        $where = [];
        if (input('get.cid')){
            $where['cid'] = input('get.cid');
            $this ->assign('cid',input('get.cid'));
        }

        $applyObj = new \app\admin\model\Apply();

        $dataList = $applyObj ->alias('a')
            ->join('apply_state b','b.aid=a.id')
            ->where('b.dstatus',2)
            ->where($where)
            ->field('a.id,a.cid,a.subject,a.name,a.sex,a.age,a.class')
            ->select();

        $compData = model('competition') ->where('id',intval(input('get.cid')))->column('title,date,time','id');
        $list = array();

        if ($dataList){
            foreach ($dataList as $k=>$v){
                $list[$k][] = $v->id;
                $list[$k][] = $v->name;
                $list[$k][] = parent::getnewValue($v ->subject,'subject');
                $list[$k][] = $v->class;
                $list[$k][] = $compData[$v['cid']]['title'];
                $list[$k][] = parent::getnewValue($v->sex,'sex');
                $list[$k][] = date('Y-m-d',$compData[$v['cid']]['date']);
                $list[$k][] = $compData[$v['cid']]['time'];
                $list[$k][] = '';
            }
        }


        $header = array('报名ID','姓名','科目','年龄','比赛项目','性别','考试日期','考试时间','考试成绩');
        $data =array();
        $filename = '书画成绩填写模板'.date('Y-m-d',time());

        foreach ($list as $k=>$v){
            foreach ($header as $sk => $sv){
                $data[$k][$header[$sk]] = $v[$sk];
            }
        }

        parent::importExcel($header,$data,$filename);
        die();
    }

    /***
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 发布书画成绩
     */
    public function issue(){
        if (request() ->isGet()){

            if (input('get.cid')){
                $cid = input('get.cid');
            }
            if (empty($cid)){
                return ajaxReturn('竞赛参数错误');
            }

            $dataList = model('Grade') ->where('cid',$cid) ->where('is_issue',0)->column('id');

            if ($dataList){
                $data =array();
                foreach ($dataList as $k=>$v){
                    $data[$k]['id'] = $v;
                    $data[$k]['is_issue'] = 1;
                }

                model('Grade') ->isUpdate(true) ->saveAll($data);
                return ajaxReturn('操作成功',1);
            }else{
                return ajaxReturn('发布的成绩数据为空',0);
            }
        }
    }

    /***
     * 删除用户的书画作品
     */
    public function delete()
    {
        if (request()->isPost()){
            $id = input('id');
            if (isset($id) && !empty($id)){
                $id_arr = explode(',', $id);
                $where = [ 'id' => ['in', $id_arr] ];
                $result = $this->cModel2->where($where)->delete();
                if ($result){
                    return ajaxReturn(lang('action_success'), url('index'));
                }else{
                    return ajaxReturn($this->cModel2->getError());
                }
            }
        }
    }


    //删除书画成绩
    public function delete2()
    {
        if (request()->isPost()){
            $id = input('id');
            if (isset($id) && !empty($id)){
                $id_arr = explode(',', $id);
                $where = [ 'id' => ['in', $id_arr] ];
                $result = $this->cModel->where($where)->delete();
                if ($result){
                    return ajaxReturn(lang('action_success'), url('grade'));
                }else{
                    return ajaxReturn($this->cModel->getError());
                }
            }
        }
    }

    /***
     * @param $grade
     * @param $pass_line
     * @return string
     * 处理考试成绩是否合格
     */
    public function get_pass_status($grade,$pass_line){
        $check = round($grade,1) - round($pass_line,1);
        if ($check >= 0){
            return '1';
        }else{
            return '-1';
        }
    }





}