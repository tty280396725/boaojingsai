<?php

namespace app\index\controller;
use app\index\model\Feedback;
use app\index\model\Grade;
use app\index\model\Apply;
use app\index\model\Competition;
use app\index\model\Works;
use think\Cache;
use think\Db;
use think\Session;

class My extends Common{

    public function _initialize(){
        parent::_initialize();
    }
    /***
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取我的报名列表的数据
     */
    public function apply_list(){

        if (request() ->isGet()){
            $param = input('get.');
            $p = isset($param['p']) ? intval($param['p']) : 1;
            $type = isset($param['type']) ?intval($param['type']) : 1;
            $compList = self::getMycompetition(0,$type);//获取我的竞赛信息和竞赛状态
            $count = count($compList);

            if ($compList){
                $page = 5; //每页5个
                $start = ($p-1)*$page;
                if (!empty($p)){
                    $compList = array_slice($compList,$start,$page);
                }
            }else{
                $compList = array();
            }

            $list['list'] = $compList;
            if ($count == 0){
                $count = 1;
            }
            $list['total'] = ceil($count/5);

            return parent::_responseResult('1','返回成功',$list);
        }
    }

    /***
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取我的竞赛的列表
     */
    public function event_list(){

        if (request()->isGet()){
            $param = input('get.');
            $user_id = $this ->user_id;
            $p = isset($param['p']) ? intval($param['p']) : 1;
            $compList = self::getMycompetition(2);

            $count = 1;
            if ($compList){
                $gradeObj = new Grade();
                $gradeData = $gradeObj ->where('uid',$user_id)->where('is_issue',1) ->field('cid,grade,is_pass,id as gid,aid') ->select();

                if (!empty($gradeData)){
                    foreach ($gradeData as $v){
                        foreach ($compList as $k=>$sv){
                            if ($v['aid'] == $sv['aid']){
                                $compList[$k]['grade'] = $v['grade'];
                                $compList[$k]['is_pass'] = $v['is_pass'];
                                $compList[$k]['gid'] = $v['gid'];
                            }
                        }
                    }
                }

                $count = count($compList);
                $page = 5; //每页6个
                $start = ($p-1)*$page;
                if ($p != 1 && !empty($p)){
                    $compList = array_slice($compList,$start,$page);
                }
            }


            $list['list'] = $compList;
            if ($count == 0){
                $count = 1;
            }
            $list['total'] = ceil($count/5);

            return parent::_responseResult('1','返回正常',$list);
        }
    }

    /***
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取我的竞赛信息和竞赛状态
     * @param   Type值决定了客户端的类型 不为0 时是移动端
     */
    public function getMycompetition($dstatus=0,$type=0){

        $user_id = $this ->user_id;
        $applyObj = new Apply();
        $compObj = new Competition();
        $where = array();
        if ($dstatus != 0){
            $where['dstatus'] = 2;
        }else{
            $where['dstatus'] = ['neq',2];
        }
        if ($type != 0){
            $where['dstatus'] = $type;
        }
        $applyData = $applyObj ->alias('a') ->join('apply_state b','a.id=b.aid') ->where('a.uid',$user_id)->where($where) ->field('a.cid,b.dstatus,b.content,a.id as aid,class') ->select();

        if (empty($applyData)){
            return array();die();
        }

        $list = array();
        foreach ($applyData as $v){
            $list[] = $v->toArray();
        }

        $compData = $compObj->where('status',1)->column('title,startTime,endTime,com_des,subject,id,type,date,time','id');

        if (empty($compData)){
            return array();die();
        }

        foreach ($applyData as $k=>$v){
            $applyData[$k]['title'] = $compData[$v['cid']]['title'];
            $applyData[$k]['startTime'] = date('Y-m-d',$compData[$v['cid']]['startTime']);
            $applyData[$k]['endTime'] = date('Y-m-d',$compData[$v['cid']]['endTime']);
            $applyData[$k]['com_des'] = $compData[$v['cid']]['com_des'];
            $applyData[$k]['subject'] = $compData[$v['cid']]['subject'];
            $applyData[$k]['exam_time'] = date('Y-m-d',$compData[$v['cid']]['date']).'-'.$compData[$v['cid']]['time'];
            $applyData[$k]['class'] = parent::getnewValue($v['class'],'class');
            $applyData[$k]['id'] = $compData[$v['cid']]['id'];
            $applyData[$k]['type'] = $compData[$v['cid']]['type'];
        }
        $outArr = $applyData;

        $worksObj = new Works();
        foreach ($outArr as $k=>$v){
            if ($v['subject'] == 5){   // 5 是书画
                $check = $worksObj ->where('uid',$user_id) ->where('cid',$v['id']) ->field('id') ->find();
                if ($check){
                    $outArr[$k]['is_upload'] = 1; //上传了
                    $outArr[$k]['kid'] = $check['id'];
                }else{
                    $outArr[$k]['is_upload'] = 0; //没上传
                }
            }else{
                $outArr[$k]['is_upload'] = 0; //没上传
            }

            $outArr[$k]['subject_name'] = parent::getnewValue($v['subject'],'subject');
        }


        return $outArr;
    }

    /***
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 我的准考证列表
     */
    public function my_ticket(){
        if (request()->isGet()){
            $user_id = $this ->user_id;
            $time = time()-7*3600*24;//七天后过期

            $ticketData = model('Ticket') ->where('uid',$user_id) ->where('date','>',$time) ->order('id desc')->field('id,name,class,subject,sex,pic,date,time,addr,tnum,cid')->select();
            $competitionData = model('Competition') ->column('title','id');

            $ticketList = array();
            if (empty($ticketData)){
                return parent::_responseResult('1','没有数据');
            }

            foreach ($ticketData as $v){
                $v['date'] = date('Y-m-d',$v['date']);
                $v['sex'] = parent::getnewValue($v['sex'],'sex');
                $v['class'] = parent::getnewValue($v['class'],'class');
                $v['subject'] = parent::getnewValue($v['subject'],'subject');
                $v['pic'] = request()->domain().$v['pic'];
                $v['title'] = $competitionData[$v['cid']];
                $ticketList[] = $v->toArray();
            }
            return parent::_responseResult('1','返回正常',$ticketList);
        }
    }

    /***
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取我的系统信息的列表
     * @Cache  key  'get_my_user_msg_'.$user_id
     */
    public function msg_list() {
        if (request()->isGet()) {
            $user_id = $this->user_id;
            $msgList = Cache::get('get_my_user_msg_' . $user_id);
            if (empty($msgList)) {
                $msgData = model('Sysmsg')->where('uid', $user_id)->whereOr('uid', '=', 0)->field('title,is_read,id, update_time')->select();
                $msgList = array();
                if ($msgData) {
                    foreach($msgData as $k=>$v){
                        $msgList[] = $v->toArray();
                    }
                }
                Cache::set('get_my_user_msg_' . $user_id, $msgList, 3600 * 24);
            }
            return parent::_responseResult('1', '返回正常', $msgList);
        }
    }

    /**
     * 查看系统消息详情
     * @return \think\response\Json
     */
    public function msg_info(){
        $info = model('Sysmsg')->where(['id'=>input('id')])->find();
        $err = 0;
        !$info && $err = 1;
        // 判断接受者
        if($info['uid']){
            if($info['uid'] != $this->user_id){
                $err = 1;
            }
        }
        if($err){
            return parent::_responseResult('0', '信息有误');
        }
        model('Sysmsg')->where(['id'=>input('id')])->update(['is_read'=>1]);
        $info['update_time'] = date('Y/m/d H:i:s', $info['update_time']);
        return parent::_responseResult('1','ok',$info);
    }

    /****
     * @return \think\response\Json
     * 意见反馈
     */
    public function put_opinion(){
        if (request() ->isPost()){
            $data = input('param.');
            $data['uid'] = $this ->user_id;
            if (empty($data['content'])){
                return parent::_responseResult('0','内容不能为空');
            }
            $feedObj = new Feedback();
            $res = $feedObj ->allowField(true) ->save($data);
            if ($res){
                return parent::_responseResult('1','提交成功');
            }else{
                return parent::_responseResult('0','提交失败');
            }

        }
    }

    /**
     * 提交成绩合格后填写的收件地址
     * @return \think\response\Json
     */
    public function grade_addr()
    {
        if (request()->isPost()) {
            $data = input('param.');
            $err = "";
            !trim($data['address']) && $err = '详细地址不能为空';
            !trim($data['area']) && $err = '地区不能为空';
            !trim($data['phone']) && $err = '联系电话不能为空';
            !trim($data['linkman']) && $err = '联系人不能为空';
            !trim($data['gid']) && $err = '成绩id没有';
            if ($err) {
                return parent::_responseResult(0, $err);
            }
            $where['uid'] = $this->user_id;
            $where['id'] = $data['gid'];
            unset($data['gid']);
            $info = model('Grade')->where($where)->update($data);
            $info ? $res = parent::_responseResult(1, '提交成功') : $res = parent::_responseResult(0, '提交失败');
            return $res;
        }
    }

}
