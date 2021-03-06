<?php
/**
 *  开课
 */
require_once dirname(__FILE__) . '/../../Path.php';

require_once SERVICE_PATH . '/TokenCmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/ClassMember.php';
require_once LIB_PATH . '/im/im_group.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

class StartCourseCmd extends TokenCmd
{

    private $course;

    public function parseInput()
    {
        $this->course = new Course();

        if (!isset($this->req['roomnum']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of roomnum');
        }
        if (!is_int($this->req['roomnum']))
        {
             return new CmdResp(ERR_REQ_DATA, ' Invalid roomnum');
        }
        $this->course->setRoomID($this->req['roomnum']);

        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        //检查课堂是否存在
        $ret=$this->course->load();
        if ($ret<=0)
        {
            return new CmdResp(ERR_AV_ROOM_NOT_EXIST, 'get room info failed');
        }
        //只有老师才可以开课
        if( $this->account->getRole()!=Account::ACCOUNT_ROLE_TEACHER
            || $this->course->getHostUin() != $this->account->getUin())
        {
            return new CmdResp(ERR_NO_PRIVILEGE, ' only the teacher who created the course can start it.');
        }
        //检查课程状态是否正常
        if($this->course->getState()!=course::COURSE_STATE_CREATED)
        {
            return new CmdResp(ERR_ROOM_STATE, ' only state=created room can start a course');
        }

        //发送IM消息记录时间戳
        $customMsg=array();
        $customMsg["type"]=1001;
        $customMsg["seq"]=rand(10000, 100000000);
        $customMsg["timestamp"]=$this->timeStamp;
        $customMsg["value"]=array('uid' =>$this->userName);
        $this->logstr.=("|imseq=".$customMsg["seq"]."|imtimstamp=".$this->timeStamp);
        $ret = ImGroup::SendCustomMsg($this->appID,(string)$this->course->getRoomID(),$customMsg);
        if($ret<0)
        {
            return new CmdResp(ERR_SEND_IM_MSG, 'save info to imgroup failed.');
        }
        $imSeqNum=$ret;

        //老师进入房间
        $classMember = new ClassMember($this->uin, $this->course->getRoomID());
        $ret = $classMember->enterRoom();
        if ($ret<0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error:enter room failed'); 
        }

        //更新课程信息
        $data = array();
        $data[course::FIELD_STATE] = course::COURSE_STATE_LIVING;
        $data[course::FIELD_START_TIME] = date('U');
        $data[course::FIELD_START_IMSEQ] = $imSeqNum;
        $data[course::FIELD_END_TIME] = 0;
        $data[course::FIELD_END_IMSEQ] = 0;
        $data[course::FIELD_LAST_UPDATE_TIME] = date('U');
        $ret = $this->course->update($this->course->getRoomID(),$data); 
        if ($ret<=0)
        {
            return new CmdResp(ERR_SERVER, 'Server internal error: update room info failed');
        }

        return new CmdResp(ERR_SUCCESS, '');
    } 
}
