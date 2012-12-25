<?php
!defined('BASEPATH')&& ('No direct script asscess allowed');
include(APPPATH . 'libraries/smarty.php');
class S extends Smarty {
    protected $_ci;
    public function __construct() {
        parent::__construct();
        $this->template_dir = SMARTY_TEMPLATE_DIR;
        $this->compile_dir = SMARTY_COMPILE_DIR;
        $this->cache_dir = SMARTY_CACHE_DIR;
        $this->config_dir = SMARTY_CONFIG_DIR;
  
        $this->_ci = &get_instance();
        $this->_ci->load->model('user');
        $configs=$this->_ci->configs->get_config();
        $current_user = get_user();
        //模块编译改动检查，开发时请设为TRUE,线上时设为FALSE;
        $this->compile_check=TRUE;
        //是否开启缓存
        //$this->caching=FALSE;
        //缓存过期时间
        $this->setCaching(0);
        $this->assign('is_login', is_login());
        $this->assign('is_admin', $this->_ci->auth->is_admin());
        $this->assign('site', $configs);
        $this->assign('me', $this->_ci->user->get_user_profile($current_user['user_id'], 'user_id'));
        $this->assign('lang', lang($configs['lang']));
    }
}