<?php
namespace app\index\model;
use think\Model;

class Competition extends Model{

    private static $param; //请求参数

    protected static function init() {
        self::$param = input();
    }

    //申报开始时间
    public function getStartTimeAttr($val){
        return date('Y/m/d', $val);
    }
    //申报结束时间
    public function getEndTimeAttr($val){
        return date('Y/m/d', $val);
    }
    //申报时间戳开始时间
    public function getStartTimeDataAttr($val, $data){
        return $data['startTime'];
    }
    //申报时间戳结束时间
    public function getEndTimeDataAttr($val, $data){
        return $data['endTime'];
    }

    // 获取竞赛列表
    public function get_list(){
        $data = self::$param;
        $time = time();
        $day_time = strtotime(date("Y-m-d", $time)); //今天0时0分0秒时间戳
        !isset($data['p']) && $data['p'] = 1;
        $where = ['status'=>1];
        $field = ['id', 'title', 'com_des', 'class', 'startTime', 'endTime','date'];
        /**if(isset($data['search'])){
            $search = trim($data['search']);
            $search && $where['title'] = ['LIKE', '%'.$search.'%'];
        }**/
        // 竞赛状态  1 报名中 2 比赛中 3 已结束
        !isset($data['status']) && $data['status']=1;
        if($data['status'] == 1){
            $where['startTime'] = ['<', $time];
            $where['endTime'] = ['>', $day_time-86400];
        }elseif($data['status'] == 2){
            $where['date'] = ['BETWEEN', [$day_time, $day_time+86400]];
        }else{
            $where['date'] = ['<', $day_time];
        }
        $list = $this->where($where)->field($field)->order('startTime asc')->page($data['p'], 5)->select();

        $page_total = ceil($this->where($where)->field($field)->order('startTime asc')->count() / 5);
        return ['list'=>$list, 'page_total'=>$page_total];
    }

    // 竞赛信息
    public function get_info(){
        $time = time();
        $field = ['c.id', 'c.title','c.date','c.time','c.class', 'c.content', 'c.startTime', 'c.endTime', 'c.type', 's.id'=>'sid', 's.name', 's.is_level'];
        $info = $this->alias('c')->join('__SUBJECT__ s', 'c.subject=s.id')->where(['c.id'=>self::$param['id']])->field($field)->find();
        if($info){
            $info['content'] = htmlspecialchars_decode($info['content']);
            $info['exam_time'] = date('Y-m-d').' '.$info['time'];
            // 竞赛状态  1 报名中 2 未开始 3 报名结束
            if($info['startTimeData']<$time && $info['endTimeData']+86400>$time){
                $info['status'] = 1;
                $info['level_msg'] = '立即报名';
                //如果该学科分初赛决赛
                if($info['is_level']){
                    $level = db('config')->where(['k'=>'competition_level'])->value('v');
                    $level_arr = explode('*', $level);
                    isset($level_arr[$info['type']]) && $info['level_msg'] = $level_arr[$info['type']].'报名';
                }
            }elseif($info['startTimeData']>$time){
                $info['status'] = 2;
                $info['level_msg'] = '报名未开始';
            }else{
                $info['status'] = 3;
                $info['level_msg'] = '报名结束';
            }
        }
        return $info;
    }

    //表单需要的数据
    public function get_form($user_id){
        $id = self::$param['id'];
        $info = [];
        if($id){
            $subject = $this->alias('c')->join('__SUBJECT__ s', 'c.subject=s.id')->where(['c.id'=>$id])->field('s.id, s.name, s.age_cover_class')->find();
            if($subject){
                $info['subject_name'] = $subject['name'];
                $info['subject_id'] = $subject['id'];
                //判断该分类是否用年龄代替班级
                if($subject['age_cover_class']){
                    $info['is_age'] = 1;
                }else{
                    $info['is_age'] = 0;
                    $info['classes'] = db('classes')->field('id,name')->order('sort desc')->select();
                }
                $info['organization'] = db('organization')->field('id, name')->select();
            }
            // 如果是修改报名表单信息
            $apply_id = self::$param['apply_id'];
            if($apply_id){
                $info['user'] = db('apply')->where(['id'=>$apply_id, 'cid'=>$id, 'uid'=>$user_id])->field('subject,cost,uid,is_create,create_time,update_time', true)->find();
                if($info['user']){
                    $info['user']['pic'] = request()->domain().$info['user']['pic'];
                }else{
                    $info = 0;
                }
            }
        }
        return $info;
    }

}