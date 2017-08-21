<?php

/**
 * 用户登录
 */

require_once dirname(__FILE__) . '/../../Config.php';
require_once SERVICE_PATH . '/Cmd.php';
require_once SERVICE_PATH . '/CmdResp.php';
require_once ROOT_PATH . '/ErrorNo.php';
require_once MODEL_PATH . '/Account.php';

class AccountLoginCmd extends Cmd
{
    // 用户账号对象
    private $account;
    private $privatekey;
    private $publickey;
    
    public function __construct()
    {
        $this->account = new Account();
    }

    public function parseInput()
    {
        if (empty($this->req['id']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of id');
        }
        if (!is_string($this->req['id']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid id');
        }
        $this->account->setUser($this->req['id']);
        
        if (empty($this->req['pwd']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Lack of pwd');
        }
        if (!is_string($this->req['pwd']))
        {
            return new CmdResp(ERR_REQ_DATA, 'Invalid pwd');
        }

        $this->privatekey = KEYS_PATH . '/' . strval($this->appID) . '/private_key';
        $this->publickey = KEYS_PATH . '/' . strval($this->appID) . '/public_key';
        if(!file_exists($this->privatekey) || !file_exists($this->publickey)){
            return new CmdResp(ERR_REQ_DATA, 'Invalid appid');
        }
        
        return new CmdResp(ERR_SUCCESS, '');
    }

    public function handle()
    {
        $account = $this->account;
        $errorMsg = '';
        
        // 获取用户账号信息
        $ret = $account->getAccountRecordByUserID($errorMsg);
        if($ret != ERR_SUCCESS)
        {
            return new CmdResp($ret, $errorMsg);
        }
        
        // 密码验证
        $ret = $account->authentication($this->req['pwd'], $errorMsg);
        if($ret != ERR_SUCCESS)
        {
            return new CmdResp($ret, $errorMsg);
        }
        
        // 获取sig
        $userSig = $account->getUserSig();
        if(empty($userSig))
        {
            $userSig = $account->genUserSig($this->appID, $this->privatekey);
            // 更新对象account的成员userSig
            $account->setUserSig($userSig);
        } 
        else 
        {
            $ret = $account->verifyUserSig($this->appID, $this->publickey);
            if($ret == 1) //过期重新生成
            {
                $userSig = $account->genUserSig($this->appID, $this->privatekey);
                // 更新对象account的成员userSig
                $account->setUserSig($userSig);
            }
            else if($ret == -1) 
            {
                return new CmdResp(ERR_SERVER, 'Server error:gen sig fail');
            }
        }
        if(empty($userSig))
            return new CmdResp(ERR_SERVER, 'Server error: gen sig fail');

        //获取token
        $token = $account->genToken();
        if(empty($token))
        {
            return new CmdResp(ERR_SERVER, 'Server error');
        }
        $account->setToken($token);
        
        $account->setLoginTime(date('U'));

        //登录，更新DB    
        $ret = $account->login($errorMsg);
        
        if($ret != ERR_SUCCESS)
        {
            return new CmdResp($ret, $errorMsg);
        }

	$data['userSig'] = $userSig;
	$data['token'] = $token;
	$data['role'] = (int)$account->getRole();
	return new CmdResp(ERR_SUCCESS, '', $data);
    }
}
