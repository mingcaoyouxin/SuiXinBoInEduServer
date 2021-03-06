<?php
/**
 * 房间成员上报接口 学生进出房间事件上报
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/ClassMember.php';
require_once DEPS_PATH . '/PhpServerSdk/TimRestApi.php';
require_once LIB_PATH . '/im/im_group.php';

class ReportRoomMemberCmd extends TokenCmd
{
   const OPERATE_ENTER=0;
   const OPERATE_EXIT=1;     
    
    private $roomNum;
    private $classMember;
    private $operate;

    public function parseInput()
    {
        if (!isset($this->req['roomnum']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of roomnum');
        }

        if (!is_int($this->req['roomnum'])) {
            if (is_string($this->req['roomnum'])) {
                $this->req['roomnum'] = intval($this->req['roomnum']);
            } else {
                return new CmdResp(ERR_REQ_DATA, ' Invalid roomnum');
            }
        }
        $this->roomNum=$this->req['roomnum'];
        
        if (!isset($this->req['operate']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of operate');
        }
        
        if (!is_int($this->req['operate']))
        {
             return new CmdResp(ERR_REQ_DATA, ' Invalid operate');
        }

        if ($this->req['operate'] != self::OPERATE_ENTER && $this->req['operate'] != self::OPERATE_EXIT)
        {
             return new CmdResp(ERR_REQ_DATA, ' Invalid operate');
        }

        $this->operate = $this->req['operate'];
        $this->classMember = new ClassMember($this->uin, $this->req['roomnum']);
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        $course = new Course();
        $course->setRoomID($this->roomNum);
        
        //检查课堂是否存在
        $ret=$course->load();
        if ($ret<=0)
        {
            return new CmdResp(ERR_AV_ROOM_NOT_EXIST, 'get room info failed');
        }

        //这个接口是学生专用
        if($course->getHostUin() == $this->account->getUin())
        {
            //老师上课过程中,如果异常退出,可以通过这个接口进入课堂
            //return new CmdResp(ERR_NO_PRIVILEGE, 'only the student can call this api.');
        }

        //检查课程状态是否正常
        if($course->getState()!=course::COURSE_STATE_LIVING)
        {
            return new CmdResp(ERR_ROOM_STATE, 'can only enter/exit a state=living room');
        }

        //检查直播房间是否已经存在
        if($this->classMember->getRoomId() <= 0)
        {
            return new CmdResp(ERR_AV_ROOM_NOT_EXIST, 'room is not exist'); 
        }

        //检查当前成员是否在课堂中
        $usrInfo=array();
        $checkClassMemberRet=ClassMember::getUserInfo($this->roomNum,$this->account->getUin(),$usrInfo);
        if($checkClassMemberRet<0)
        {
            return new CmdResp(ERR_SERVER, 'check class member info failed.inner code:'.$checkClassMemberRet); 
        }

        //重复进
        if($this->operate == self::OPERATE_ENTER 
            && $checkClassMemberRet>0 
            && array_key_exists("has_exited",$usrInfo)
            && $usrInfo["has_exited"]==ClassMember::HAS_EXITED_NO)
        {
            //先允许重复进.客户端机器重启时时间戳可能置0.需要再次上报进入动作
            //return new CmdResp(ERR_REPEATE_ENTER, 'can not repeat enter a room.'); 
        }

        //不在房间,但是退出
        if($this->operate == self::OPERATE_EXIT 
            && $checkClassMemberRet==0) 
        {
            return new CmdResp(ERR_REPEATE_EXIT, 'can not repeat exit a room.'); 
        }
        if($this->operate == self::OPERATE_EXIT 
            && $checkClassMemberRet>0 
            && array_key_exists("has_exited",$usrInfo)
            && $usrInfo["has_exited"]==ClassMember::HAS_EXITED_YES)
        {
            //允许重复退出
            //return new CmdResp(ERR_REPEATE_EXIT, 'can not repeat exit a room.'); 
        }

        $ret = false;
        if($this->operate == self::OPERATE_ENTER) //成员进入房间
        {
            $ret = $this->classMember->enterRoom();
        }
        if($this->operate == self::OPERATE_EXIT) //成员退出房间
        {
            $ret = $this->classMember->exitRoom();
        }

        if ($ret<0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error'); 
        }
        
        //进入房间需要发im消息记录客户端相对时间
        if($this->operate == self::OPERATE_ENTER)
        {
            $customMsg=array();
            $customMsg["type"]=1003;
            $customMsg["seq"]=rand(10000, 100000000);
            $customMsg["timestamp"]=$this->timeStamp;
            $customMsg["value"]=array('uid' =>$this->userName);
            $ret = ImGroup::SendCustomMsg($this->appID,(string)$this->roomNum,$customMsg);
            if($ret<0)
            {
                return new CmdResp(ERR_SEND_IM_MSG, 'save info to imgroup failed.');
            }
        }
 
        return new CmdResp(ERR_SUCCESS, '');
    }    
}
