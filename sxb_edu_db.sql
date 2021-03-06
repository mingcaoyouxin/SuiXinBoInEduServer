-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 2017-08-14 10:01:00
-- 服务器版本： 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `sxb_edu_db`
--
CREATE DATABASE IF NOT EXISTS `sxb_edu_db` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `sxb_edu_db`;
-- --------------------------------------------------------

--
-- 账号管理表的结构 `t_account`
--

CREATE TABLE IF NOT EXISTS `t_account` (
 `uin` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户id,后台维护和使用',
 `uid`                varchar(50)   NOT  NULL COMMENT '用户名',          
 `appid` int(11) NOT NULL DEFAULT 0 COMMENT 'appid',
 `role`              tinyint(1)    NOT  NULL DEFAULT  0   COMMENT '角色,0:学生,1:老师',   
 `pwd`                varchar(50)   NOT  NULL  COMMENT '用户密码',         
 `token`              varchar(50)   DEFAULT NULL COMMENT '用户token',           
 `user_sig`           varchar(512)  DEFAULT NULL COMMENT 'sig',        
 `register_time`      int(11)       NOT  NULL DEFAULT  0   COMMENT '注册时间戳',             
 `login_time`         int(11)       NOT  NULL DEFAULT  0   COMMENT '登录时间戳',            
 `logout_time`        int(11)       NOT  NULL DEFAULT  0   COMMENT '退出时间戳',           
 `last_request_time`  int(11)       NOT  NULL DEFAULT  0   COMMENT '最新请求时间戳',
    
  PRIMARY KEY (`uin`),
  UNIQUE KEY `idx_uid` (`uid`)
)ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表' AUTO_INCREMENT=10001;


-- --------------------------------------------------------

--
-- 课程表的结构 `t_course`

CREATE TABLE IF NOT EXISTS `t_course` (
  `room_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '课程ID',
  `create_time` int(11)      NOT  NULL DEFAULT  0 COMMENT '创建时间戳',
  `start_time` int(11)      NOT  NULL DEFAULT  0 COMMENT '上课时间戳',
  `start_imseq` int(11)      NOT  NULL DEFAULT  0 COMMENT '上课时的im消息seqno',
  `end_time` int(11)      NOT  NULL DEFAULT  0 COMMENT '下课时间戳',
  `end_imseq` int(11)      NOT  NULL DEFAULT  0 COMMENT '下课时的im消息seqno',
  `last_rec_imseq` int(11)      NOT  NULL DEFAULT  0 COMMENT '最近一次录制结束对应的消息seqno',
  `last_update_time`  int(11)      NOT  NULL DEFAULT  0  COMMENT '心跳时间戳',                       
  `can_trigger_replay_idx_time`  int(11)      NOT  NULL DEFAULT  0  COMMENT '可以触发索引文件生成指令的UTC', 
  `trigger_replay_idx_time`  int(11)      NOT  NULL DEFAULT  0  COMMENT '本课程触发索引文件生成指令的UTC', 
  `trigger_replay_idx_result`  int(11)      NOT  NULL DEFAULT  0  COMMENT '本课程触发索引文件生成指令的结果', 
  `title` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '标题',
  `cover` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '封面URL',
  `host_uin` int(11) NOT NULL DEFAULT 0 COMMENT '老师UIN',
  `state` int(11)      NOT  NULL DEFAULT  0 COMMENT '课程状态,0-created,已创建未上课,1-living,正在上课中,2-has_lived,已下课但不能回放,3-can_playback,可以回放',
  `im_group_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'im群组号',
  `replay_idx_created_time`  int(11)      NOT  NULL DEFAULT  0  COMMENT '索引文件生成的UTC', 
  `replay_idx_created_result`  int(11)      NOT  NULL DEFAULT  0  COMMENT '索引文件生成的结果', 
  `replay_idx_url` text COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT '播放索引文件地址',
  PRIMARY KEY (`room_id`),
  KEY `idx_host_uin` (`host_uin`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='课程表' AUTO_INCREMENT=10001 ;

-- --------------------------------------------------------

--
-- (正在直播)课程成员表的结构 `t_class_member`
--

CREATE TABLE IF NOT EXISTS `t_class_member` (
 `uin`   int(11)      NOT  NULL DEFAULT  0  COMMENT '成员id',            
 `room_id`   int(11)      NOT  NULL DEFAULT  0  COMMENT '成员所在房间ID',            
 `has_exited`   int(11)      NOT  NULL DEFAULT  0  COMMENT '该成员是否已经退出此房间', 
 `last_heartbeat_time`  int(11)      NOT  NULL DEFAULT  0   COMMENT '成员心跳时间戳',           
  PRIMARY KEY (`uin`,`room_id`)
);

-- --------------------------------------------------------

--
-- 录制文件 `t_video_record`
-- 主播没打开一次摄像头就会开始一个新的录制
--

CREATE TABLE IF NOT EXISTS `t_video_record` (
 `id`           int(11)       NOT  NULL   AUTO_INCREMENT  COMMENT 'id',
 `uin`   int(11)      NOT  NULL DEFAULT  0  COMMENT '视频的拥有者',            
 `room_id`   int(11)      NOT  NULL DEFAULT  0  COMMENT '课程id',               
 `video_id`     varchar(50)   NOT  NULL   DEFAULT ''  COMMENT '视频id',                            
 `video_url`     varchar(128)  NOT  NULL   DEFAULT ''  COMMENT '视频url',                            
 `start_time`  int(11)       NOT  NULL   DEFAULT  0  COMMENT '视频录制开始时间戳',                                      
 `end_time`  int(11)       NOT  NULL   DEFAULT  0  COMMENT '视频录制结束时间戳',                                     
 `media_start_time`  int(11)       NOT  NULL   DEFAULT  0  COMMENT '视频录制开始客户端时间戳',                                      
 `file_size`  int(11)       NOT  NULL   DEFAULT  0  COMMENT '文件大小',
 `duration`  int(11)       NOT  NULL   DEFAULT  0  COMMENT '时长', 
  PRIMARY KEY (`id`)
)AUTO_INCREMENT=10001;

-- --------------------------------------------------------


--
-- 关联文件 `t_bind_file`
--

CREATE TABLE IF NOT EXISTS `t_bind_file` (
 `id`           int(11)       NOT  NULL   AUTO_INCREMENT  COMMENT 'id',
 `uin`   int(11)      NOT  NULL DEFAULT  0  COMMENT '资源的拥有者', 
 `room_id`   int(11)      NOT  NULL DEFAULT  0  COMMENT '课程id', 
 `bind_time`   int(11)      NOT  NULL DEFAULT  0  COMMENT '操作时的UTC时间戳', 
 `type`   int(11)      NOT  NULL DEFAULT  0  COMMENT '0:课件,1:播片',
 `file_name`   varchar(256)      NOT  NULL DEFAULT  0  COMMENT '文件名',
 `url`     text  NOT  NULL   DEFAULT ''  COMMENT '视频url',                            
  PRIMARY KEY (`id`),
  KEY `idx_uin` (`uin`),
  KEY `idx_roomid` (`room_id`)
)AUTO_INCREMENT=10001;

-- --------------------------------------------------------
