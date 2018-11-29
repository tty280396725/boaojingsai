<?php
namespace app\admin\controller;

class Grade extends Common
{
    private $cModel;   //当前控制器关联模型

    public function _initialize()
    {
        parent::_initialize();
        $this->cModel = new \app\admin\model\Grade();   //别名：避免与控制名冲突
    }

    public function index()
    {
        $where = [];
        if (input('get.search')){
            $where['a.uid|b.subject'] = ['like', '%'.input('get.search').'%'];
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
            $order = 'a.'.$order[0].' '.$order[1];
        }else{
            $order = 'a.cid desc';
        }

        $compData = model('Competition') ->where('status',1)->column('title','id');

        foreach ($compData as $k=>$v){
            $idArr[] = $k;
        }

        if (!isset($where['a.cid'])){
            $where['a.cid'] = ['in',$idArr];
        }

        $this ->assign('compData',$compData); //竞赛数据

        $dataList = $this->cModel->alias('a') ->join('apply b','a.aid=b.id')
            ->where($where)
            ->order($order)
            ->field('a.grade,a.is_pass,b.*,a.id,a.is_issue')
            ->paginate('', false, page_param())->each(function ($item,$key){
                $datas = model('Competition')->where('id',$item['cid']) ->field('title,time')->find();
                $item['title'] = $datas['title'];
                $item['time'] = $datas['time'];
                $item['subject'] = parent::getnewValue($item['subject'],'subject');
                return $item;
            });

        $this->assign('dataList', $dataList);
        return $this->fetch();
    }


    /***
     *删除成绩
     */
    public function delete()
    {
        if (request()->isPost()) {
            $id = input('id');
            if (isset($id) && !empty($id)) {
                $id_arr = explode(',', $id);
                $where = ['id' => ['in', $id_arr]];
                $result = $this->cModel->where($where)->delete();
                if ($result) {
                    return ajaxReturn(lang('action_success'), url('index'));
                } else {
                    return ajaxReturn($this->cModel->getError());
                }
            }
        }
    }

    /***
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @shanghcuan chengji 上传考试成绩
     */


    public function importExecl(){

        if (request()->isPost()) {

            $header = array('aid','name','subject','title','sex','date','time','grade');

            $result = parent::exportExcel($header);

            if (empty($result)){
                $this ->error('上传格式错误');
                die();
            }

            $applyObj = new \app\index\model\Apply();

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
                $this ->cModel ->isUpdate(true)->saveAll($update); //批量信息grade表的数据
                return $this->success('上传成功');
            }

            if (empty($update) && $insert){
                $this ->cModel ->saveAll($insert);
                return $this->success('上传成功');
            }

            if ($insert && $update){
                $this ->cModel ->saveAll($insert);
                $this ->cModel ->isUpdate(true)->saveAll($update); //批量信息grade表的数据
                return $this->success('上传成功');
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

    /***
     * 导出成绩表
     */
    public function exportExecl(){

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
                $order = 'a.id desc';
            }

            $etc = '';
            if (input('get.cid')){
                $etc = model('Competition')->getComtitionInfo(input('get.cid'),array('title'))['title'];
            }
            //上面是检索的内容
            $header = array('姓名','科目','性别','比赛项目','比赛成绩','是否合格','联系人','地区','地址','电话');
            $data =array();
            $filename = $etc.'成绩表'.date('Y-m-d');


            $dataList = $this->cModel->alias('a') ->join('apply b','a.aid=b.id')->where($where)->order($order)->field('a.grade,a.is_pass,a.linkman,a.phone,a.area,a.address,b.subject,a.cid,b.age,b.name,b.sex') ->paginate('', false, page_param()) ->each(function ($item,$key){
                $item['title'] = model('Competition') ->getComtitionInfo($item['cid'],array('title'))['title'];
                return $item;
            });

            $list =array();
            foreach ($dataList as $k=>$v){
                $list[$k][] = $v ->name;
                $list[$k][] = parent::getnewValue($v ->subject,'subject');
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

    /**下载成绩模板*/

    public function download(){

        $where = [];
        $etc = '';
        if (input('get.cid')){
            $where['cid'] = input('get.cid');
            $this ->assign('cid',input('get.cid'));
            $etc = model('Competition')->getComtitionInfo(input('get.cid'),array('title'))['title'];
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
                $list[$k][] = $compData[$v['cid']]['title'];
                $list[$k][] = parent::getnewValue($v->sex,'sex');
                $list[$k][] = date('Y-m-d',$compData[$v['cid']]['date']);
                $list[$k][] = $compData[$v['cid']]['time'];
                $list[$k][] = '';
            }
        }


        $header = array('报名ID','姓名','科目','比赛项目','性别','考试日期','考试时间','考试成绩');
        $data =array();
        $filename = $etc.'成绩填写模板'.date('Y-m-d',time());

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
     * 发布成绩
     */
    public function release(){
        if (request() ->isGet()){

            if (input('get.cid')){
               $cid = input('get.cid');
            }

            if (empty($cid)){
                return ajaxReturn('竞赛参数错误');
            }

            $dataList = model('Grade') ->where('cid',$cid) ->where('is_issue',0)->field('id') ->select();

            if ($dataList){
                $data =array();
                foreach ($dataList as $k=>$v){
                    $data[$k]['id'] = $v['id'];
                    $data[$k]['is_issue'] = 1;
                }
                model('Grade') ->isUpdate(true) ->saveAll($data);

                return ajaxReturn('操作成功',1);
            }else{
                return ajaxReturn('发布数据为空',0);
            }

        }
    }



}