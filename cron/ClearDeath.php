<?php
/**
 */

require_once  __DIR__ . '/../Path.php';
require_once MODEL_PATH . '/Account.php';
require_once MODEL_PATH . '/Course.php';
require_once MODEL_PATH . '/ClassMember.php';

require_once LIB_PATH . '/log/FileLogHandler.php';
require_once LIB_PATH . '/log/Log.php';

function clear()
{
    //初始化日志,清掉的房间记录日志,备查
    $handler = new FileLogHandler(LOG_PATH . '/sxb_' . date('Y-m-d') . '.log');
    Log::init($handler);

   //找出N秒无心跳的直播课堂
   $roomList=Course::getDeathCourseList(60);
   if(!is_null($roomList))
   {
        foreach ($roomList as $room)
        {
            $roomID=(int)$room["room_id"];
            if($roomID==0)
            {
                continue;
            }
            //清掉房间内所有成员
            ClassMember::ClearRoomByRoomNum($roomID);
            
            //更改房间状态
            $data = array();
            $data[course::FIELD_STATE] = course::COURSE_STATE_HAS_LIVED;
            $data[course::FIELD_END_TIME] = date('U');
            $ret = Course::update($roomID,$data);

            Log::info("crontab, clear room $roomID.");
        }
   }

   //删除N秒内没有收到心跳包的课堂里的成员
   ClassMember::deleteDeathRoomMember(30,Account::ACCOUNT_ROLE_STUDENT);
}

ini_set('date.timezone','Asia/Shanghai');
clear();
