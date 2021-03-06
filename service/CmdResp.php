<?php
require_once dirname(__FILE__) . '/../Config.php';
require_once 'AbstractCmdResp.php';
/**
 * 
 */
class CmdResp extends AbstractCmdResp
{
    /**
     * @return array
     */
    public function toArray()
    {
        $data = $this->data;
        $result = array();
        $result['errorCode'] = $this->getErrorCode();
        $result['errorInfo'] = $this->getErrorInfo();
        if (is_array($data))
        {
            $result['data'] = $data;
        }
        return $result;
    }
}
