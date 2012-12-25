<?php

!defined('BASEPATH') && exit('No direct script access allowed');
/**
 * NodePrint
 *
 * 基于HTML5及CSS3的轻论坛程序
 * 
 * NodePrint is an open source BBS System built on PHP and MySQL.
 *
 * @package	NodePrint
 * @author		airyland <i@mao.li>
 * @copyright	Copyright (c) 2012, mao.li.
 * @license		GNU General Public License 2.0
 * @link		https://github.com/airyland/nodeprint
 * @version	0.0.5
 */
/**
 * Api Controller
 * @author airyland <i@mao.li>
 * @version 0.5
 */
header("Content-type:text/html;charset=utf-8");

class Api extends CI_Controller {
    /*
     * 用户API允许行为
     * @var array
     */

    private $allow_user_action = array('send_pm_message', 'change_pwd');

    /**
     * 帖子API允许行为
     * @var array
     */
    private $allow_topic_action = array();

    /**
     * 评论允许行为
     * @var array
     */
    private $allow_comment_action = array();

    /**
     * 用户允许行为
     * @var array
     */
    private $allow_member_action = array();

    /**
     * 节点允许行为
     * @var array
     */
    private $allow_node_action = array();

    /**
     * 是否为ajax请求
     */
    private $is_ajax = FALSE;

    /**
     * 请求来源
     */
    public $from;

    /**
     * API构造
     * 检测是否是AJAX请求 
     */
    function __construct() {
        parent::__construct();
        $this->load->model('auth');
        $this->is_ajax = $this->input->is_ajax_request();
        $this->from = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : base_url();
    }

    /**
     * 用户API
     * @param string $username 用户名
     * @param string $action 行为
     * @API 
     */
    function user($username = '', $action = '') {
        $this->load->model('user');
        $user = $this->auth->get_user();
        switch ($action) {
            /**
             * 发送私信
             * @url /api/user/send_pm_message
             * @redirect /messages
             */
            case 'send_pm_message':
                $this->auth->check_login();
                $this->load->model('message');
                $slug = $this->input->post('touser');
                $content = $this->input->post('content');
                $this->message->send_message(4, $slug, $user['user_name'], $content, $post_id = 0);
                if ($this->is_ajax) {
                    json_output(0);
                } else {
                    redirect('/messages');
                }

                break;

            /**
             * 更改密码
             * @url /api/user/change_pwd
             * @redirct /settings#change_pwd
             */
            case 'change_pwd':
                $this->auth->check_login();
                $oldpwd = $this->input->post('old-pwd');
                $newpwd = $this->input->post('new-pwd');
                $newpwd2 = $this->input->post('new-pwd2');
                $rs = $this->user->user_update_password($user['user_id'], $oldpwd, $newpwd, $newpwd2);
                if ($this->is_ajax) {
                    json_output(0);
                } else {
                    alert($rs['msg'], '/settings#change_pwd');
                }

                break;

            /**
             * 确认邮箱
             */
            case 'email_confirm':
                $user_id = $this->input->get('u');
                $user = $this->auth->get_user();
                if ($user_id !== $user['user_id']) {
                    $this->auth->check_admin();
                }
                $this->load->model('mail');
                if ($this->mail->send($user_id)) {
                    $this->user->do_send_email($user_id);
                    if ($this->input->is_ajax_request()) {
                        json_output(0);
                    } else {
                        redirect(base_url() . 'settings');
                    }
                }

                break;
            /**
             * 根据 auth code 确认邮箱地址
             */
            case 'email_auth_confirm':
                $auth = $this->input->get('auth');
                echo $auth;
                if ($this->user->email_confirm($auth)) {
                    redirect(base_url() . 'settings#email-confirm-done');
                } else {
                    redirect(base_url() . 'settings#email-confirm-fail');
                }

                break;

            case 'my':
                $user_status = $this->user->get_user();
                echo json_encode($user_status);
                break;

            case 'check_username':
                $user_name = $this->input->get('user-name');
                $e = ($this->db->where('user_name', $user_name)->get('vx_user')->num_rows() == 0) ? 'true' : 'false';
                echo $e;
                break;
            /**
             * 发送找回密码的邮件
             * 只针对在网站上用邮箱注册的用户
             */
            case 'send_reset_password_email':

                break;
            /**
             * 通过邮箱注册用户的密码重置
             */
            case 'reset_password':
                $data = $this->post->post('data');
                $this->user->reset_password($data);
                break;

            /**
             * 检查邮箱是否已经被注册
             * @url /api/user/check_email
             * @return bool
             */
            case 'check_email':
                $user_email = $this->input->get('user-email');
                $e = ($this->db->where('user_email', $user_email)->get('vx_user')->num_rows() == 0) ? 'true' : 'false';
                echo $e;

                break;

            /**
             * 用户注册 
             * 
             */
            case 'register':
                session_start();
                $captcha = strtolower($this->input->post('captcha'));
                if ($captcha != $_SESSION['captcha'])
                    die('验证码不正确' . $captcha . $_SESSION['captcha']);
                $username = $this->input->post('user-name');
                $useremail = $this->input->post('user-email');
                $userpwd = $this->input->post('user-pwd');
                $register = $this->user->register_user($username, $useremail, $userpwd);
                if ($register['error'] == 0) {
                    redirect();
                } else {
                    redirect('/signup');
                }

                break;
            /**
             * 用户登录
             * @url /api/user/signin
             * @redirct /signin
             */
            case 'signin':
                $useremail = $this->input->post('user-name');
                $userpwd = $this->input->post('user-pwd');
                $is_ajax = $this->input->is_ajax_request();
                list($error, $msg, $user_id, $user_name) = $this->user->login_user($useremail, $userpwd);
                if ($error == 0) {
                    setcookie('vx_auth', authcode($user_id . "\t" . $user_name, 'ENCODE'), time() + 365 * 24 * 3600, '/');
                    if (!$is_ajax) {
                       if(strpos($this->from,'signin')){
                           redirect(base_url());
                       }else{
                           redirect($this->from);
                       }
                    }
                } else {
                    if (!$is_ajax) {
                        redirect('/signin?error=' . $error);
                    }
                }

                json_output($error, 'data', array('message' => $msg));

                break;
            /**
             * 退出登录
             * @url /api/user/logout
             * @todo 改名为 signout
             * @rediect 原来地址
             */
            case 'logout':
                setcookie('vx_auth', '', time() - 365 * 24 * 3600, '/');
                if (is_ajax())
                    die('{"code":0}');
                redirect();
                break;
            /**
             * 获得关注者
             * @url api/user/username/follower
             * @return json
             */
            case 'follower':
                $this->load->model('follow');
                $follower = $this->follow->list_follow($username, 'user_name', 1, $page = 1, $no = 20);
                echo json_encode(array('code' => 0, 'follower' => $follower));
                break;
            /**
             * 获得被关注用户
             * @url api/user/username/following
             * @return json
             */
            case 'following':
                $this->load->model('follow');
                $following = $this->follow->list_follow($username, 'user_name', 2, $page = 1, $no = 20);
                echo json_encode(array('code' => 0, 'following' => $following));
                break;
            /**
             * 获得用户关注节点
             * @url api/user/username/favnode
             * @return json
             */
            case 'favnode':
                $this->load->model('follow');
                $favnode = $this->follow->list_follow($username, 'user_name', 3, $page = 1, $no = 20);
                echo json_encode(array('code' => 0, 'favnode' => $favnode));
                break;
            /**
             * 获得用户收藏帖子
             * @url /api/user/username/favtopic
             * @return json
             */
            case 'favtopic':
                $this->load->model('follow');
                $favtopic = $this->follow->list_follow($username, 'user_name', 4, $page = 1, $no = 20);
                echo json_encode(array('code' => 0, 'favnode' => $favtopic));
                break;
            /**
             * 获得用户消息
             * @url api/user/$username/message
             * @ return json
             */
            case 'message':
                $this->auth->check_login();
                $this->load->model('message');
                $type = $this->input->get('type');
                $count = $this->input->get('count');
                $message = $this->message->list_message($user['user_name'], 'm_to_username', $type, 1, 20, $count);

                if ($count) {
                    echo json_encode(array('error' => 0, 'count' => $message));
                } else {
                    echo json_encode(array('error' => 0, 'message' => $message));
                }

                break;
            /**
             * get user topics
             * @return json
             */
            case 'topic':
                $topic = $this->post->query_post("user_name={$username}&user_type=user_name");
                echo json_encode(array('error' => 0, 'topic' => $topic));
                break;
            /**
             * 用户头像上传
             * 
             */
            case 'avatar':
                $user = get_user();
                $upload = array(
                    'upload_path' => APPPATH . '../img/avatar/',
                    'allowed_types' => 'gif|jpg|png',
                    'max_size' => '1024',
                    'max_width' => '1024',
                    'max_height' => '1024',
                    'overwrite' => TRUE,
                    'file_name' => $user['user_id'],
                );
                $this->load->library('upload', $upload);

                if (!$this->upload->do_upload('avatar')) {
                    $error = array('error' => $this->upload->display_errors());
                    //print_r($error);
                    alert('上传错误', '/settings');
                } else {
                    $data = array('upload_data' => $this->upload->data());
                    //print_r($data);
                    $path = $data['upload_data']['full_path'];

                    $config = array(
                        'image_library' => 'GD2',
                        'source_image' => $path,
                        'quality' => 100,
                        'new_image' => 'img/avatar/l/' . $user['user_id'] . '.png',
                        'width' => 73,
                        'height' => 73,
                    );

                    $this->load->library('image_lib');
                    $this->image_lib->initialize($config);
                    $this->image_lib->resize();
                    $this->image_lib->clear();
                    $config['width'] = 48;
                    $config['height'] = 48;
                    $config['new_image'] = 'img/avatar//m/' . $user['user_id'] . '.png';
                    $this->image_lib->initialize($config);
                    $this->image_lib->resize();
                    $this->image_lib->clear();
                    $config['width'] = 20;
                    $config['height'] = 20;
                    $config['new_image'] = 'img/avatar/s/' . $user['user_id'] . '.png';
                    $this->image_lib->initialize($config);
                    $this->image_lib->resize();
                    redirect('/settings#avatar');
                }

                break;

            /**
             * @ get user profile
             * @ url:api/user/$username
             * @ return json
             */
            default:
                $do = $this->input->get('do');
                switch ($do) {
                    /**
                     * @ follow a member
                     * @ url:api/user/$username/?do=fo
                     * @return return json
                     */
                    case 'fo':
                        $username = urldecode($username);
                        $this->auth->check_login();
                        $this->load->model('follow');
                        $this->load->model('user');
                        $error = 1;
                        $user = get_user();
                        if ($user['user_name'] == $username) {
                            $error = -1;
                            $msg = '亲,不要这么自恋行么';
                        } else {
                            if ($this->follow->check_follow($user['user_id'], $username, $field = 'f_keyname', 3)) {
                                $error = 0;
                                $msg = '已关注';
                            } else {
                                $keyid = $this->db->select('user_id')->where('user_name', $username)->get('vx_user')->row()->user_id;
                                $dofollow = $this->follow->add_follow($user['user_id'], 3, $keyid, $username);
                                if ($dofollow)
                                    $error = 0;
                            }
                        }
                        $this->user->refresh_user_info($user['user_id']);
                        if (is_ajax()) {
                            echo json_encode(array('error' => $error, 'msg' => $msg));
                            exit;
                        }
                        redirect('member/' . $username);
                        break;

                    case 'unfo':
                        $this->load->model('user');
                        $this->auth->check_login();
                        $user = get_user();
                        $this->db->where('f_keyname', urldecode($username))
                                ->where('user_id', $user['user_id'])
                                ->where('f_type', 3)
                                ->delete('vx_follow');
                        $this->user->refresh_user_info($user['user_id']);
                        if (is_ajax()) {
                            echo json_encode(array('error' => 0));
                            exit;
                        }
                        redirect('member/' . $username);
                        break;


                    case 'update':
                        $this->auth->check_login();
                        $user = get_user();
                        $user_email = $this->input->post('user-email');
                        $site = $this->input->post('user-site');
                        $location = $this->input->post('user-location');
                        $sign = $this->input->post('user-sign');
                        $intro = $this->input->post('user-intro');
                        $twitter = $this->input->post('twitter');
                        $github = $this->input->post('github');
                        $douban = $this->input->post('douban');
                        $weibo = $this->input->post('weibo');
                        $this->user->user_update_profile($user['user_id'], $user_email, $github, $twitter, $douban,$weibo,$site, $location, $sign, $intro);
                        redirect('settings');
                        break;

                    default:
                        $user = $this->user->get_user_profile($username);
                        $code = 1;
                        $msg = 'the user does not exist';
                        if ($user) {
                            $code = 0;
                            $msg = 'success';
                        }
                        echo json_encode(array('code' => $code, 'msg' => $msg, 'profile' => $user));
                        break;
                }

                break;
        }
    }

    /**
     * Node APIlist
     */
    function node($slug, $action = '') {
        $this->load->model('nodes');
        $user = get_user();
        switch ($action) {
            /**
             * get node info
             * @return return json
             */
            case 'info':
                $node = $this->nodes->get_node($slug, 'node_slug');
                if ($node) {
                    echo json_encode(array('error' => 0, 'info' => $node));
                } else {
                    echo '不存在';
                }
                break;
            /**
             * get node topic
             * @return json
             *
             */
            case 'topic':
                $page = $this->input->get('page');
                $no = $this->input->get('no');
                if (!$page)
                    $page = 1;
                if (!$no)
                    $no = 15;
                $this->load->model('post');
                $posts = $this->post->query_post("node_id={$slug}&node_type=node_slug&page={$page}&no={$no}");
                echo json_encode($posts);
                break;

            case 'list':
                $page = $this->input->get('page');
                $no = $this->input->get('no');
                if (!$page)
                    $page = 1;
                if (!$no)
                    $no = 15;
                $this->load->model('node');
                $rs = $this->node->list_node();
                echo json_encode($rs);
                break;

            /**
             * add node
             *
             */
            case 'add':
                $this->auth->check_admin();
                $node_name = $this->input->post('node-name');
                $node_slug = $this->input->post('node-slug');
                $node_parent = $this->input->post('node-parent');
                $node_type = (!$node_parent) ? 1 : 2;
                $node_intro = $this->input->post('node-intro') || '';
                $add = $this->nodes->add_node($node_type, $node_name, $node_slug, $node_parent, $node_intro);
                $e = 1;
                if ($add)
                    $e = 0;
                if (is_ajax())
                    echo json_encode(array('error' => $e));
                redirect('/admin');
                break;


            default:

                $do = $this->input->get('do');
                switch ($do) {
                    /**
                     * 节点信息更新
                     * 目前只能更改介绍文字，名字，slug及icon不可修改。
                     * @modify ${date}
                     * @todo 弃用中
                     */
                    case 'update':
                        $this->auth->check_admin();
                        $node_intro = $this->input->post('node-intro');
                        $inline = $this->input->post('inline');
                        $this->nodes->update_node($slug, 'node_slug', $node_intro);
                        if ($this->input->is_ajax_request()) {
                            if ($inline) {
                                echo $node_intro;
                            } else {
                                json_output(0, 'node', array('node_intro' => $node_intro));
                            }
                        }
                        break;

                    case 'update_info':
                        $node_id = $this->input->post('node_id');
                        $node_info = $this->input->post('node_info');
                        print_r($node_info);
                        $this->nodes->update_node_info($node_id, $node_info);
                        break;

                    /**
                     * 是否可以删除??
                     * 考虑可能在调试时需要删除数据，还是给管理员权限吧
                     */
                    case 'delete':
                        $this->auth->check_admin();
                        $node_id = $this->input->post('node_id');
                        $this->nodes->delete_node($node_id);
                        //将所有帖子转移到默认节点0,default,default
                        $this->db->set('node_id', 0)->set('node_name', 'default')->set('node_slug', 'default')
                                ->where('node_id', $node_id)->update('vx_post');
                        break;
                    /**
                     * fav a node
                     * 
                     */
                    case 'fav':
                        $this->load->model('nodes');
                        $this->load->model('follow');
                        $this->load->model('user');
                        $node = $this->nodes->get_node($slug, 'node_slug');
                        $this->follow->add_follow($user['user_id'], 2, $node['node_id'], $node['node_slug']);
                        $this->user->refresh_user_info($user['user_id']);
                        if (is_ajax())
                            json_output(0);
                        redirect('/node/' . $slug);
                        break;
                    /**
                     * unfav a node
                     */
                    case 'unfav':
                        $this->load->model('nodes');
                        $this->load->model('follow');
                        $this->load->model('user');
                        $node = $this->nodes->get_node($slug, 'node_slug');
                        $this->follow->del_follow($user['user_id'], 2, $node['node_id']);
                        $this->user->refresh_user_info($user['user_id']);
                        if (is_ajax())
                            json_output(0);
                        redirect('/node/' . $slug);
                        break;

                    default:
                        $node = $this->nodes->get_node($slug, 'node_slug');
                        if ($node) {
                            echo json_encode(array('error' => 0, 'info' => $node));
                        } else {

                            echo '不存在';
                        }
                        break;
                }

                break;
        }
    }

    function nodes($type = '') {
        $this->load->model('nodes');
        switch ($type) {
            /**
             * 节点列表
             * @todo 节省资源，只返回父节点
             * @todo 缓存节点信息到文件，减少数据库查询
             */
            case 'list':
                $type = $this->input->get('type');
                $order_by = $type == 'new' ? 'node_id' : 'node_post_no';
                $nodes = $this->nodes->list_node(1, 0, $order_by, 'DESC', 1, 15);
                foreach ($nodes as $k => $v) {
                    $nodes[$k]['child_node'] = $this->nodes->list_node(2, $nodes[$k]['node_id'], 'node_id', 'DESC', 1, 15);
                }
                json_output(0, 'nodes', $nodes);
                break;

            case 'add':
                $this->auth->check_admin();
                $node_name = $this->input->post('node-name');
                $node_slug = $this->input->post('node-slug');
                $node_parent = $this->input->post('node-parent');
                $node_type = (!$node_parent) ? 1 : 2;
                $node_intro = $this->input->post('node-intro') || '';
                $add = $this->nodes->add_node($node_type, $node_name, $node_slug, $node_parent, $node_intro);
                $e = 1;
                if ($add)
                    $e = 0;
                if ($this->input->is_ajax_request()) {
                    echo json_encode(array('error' => $e));
                } else {
                    redirect('/admin');
                }
                break;

            case 'delete':
                $this->auth->check_admin();
                $node_id = $this->input->post('node_id');
                $this->nodes->del_node($node_id);
                //将所有帖子转移到默认节点0,default,default
                json_output(0);
                break;

            default:
                show_404();
                break;
        }
    }

    /**
     *  帖子接口
     * @param int $post_id 帖子id
     * @param string $aciton 帖子接口
     */
    function post($post_id, $aciton = '') {
        $this->load->model('post');
        $this->load->model('user');
        $page = $this->input->get('page') ? $this->input->get('page') : 1;
        $no = $this->input->get('no') ? $this->input->get('no') : 15;
        switch ($aciton) {
            case 'list':
                $post = $this->post->query_post("orderby=post_last_comment&page={$page}&no={$no}");
                json_output('0', 'post', $post);
                break;
            case 'info':
                $post = $this->post->post_info($post_id);
                $code = 1;
                if ($post)
                    $code = 0;
                echo json_encode(array('error' => $code, 'post' => $post));
                break;

            case 'comment':
                $this->load->model('comment');
                $rs = $this->comment->list_comment($post_id, $user_id = 0, 'cm_id', 'ASC', 1, $no = 50);
                json_output('0', 'comment', $rs);
                break;


            case 'add':
                $this->auth->check_login();
                $user = $this->auth->get_user();
                $this->load->helper('parse_content');
                $this->load->model('message');
                $post_title = $this->input->post('post-title');
                if (!$post_title)
                    die('没写标题啊，同学');

                $node_id = $this->input->post('node-id');
                $post_content = $this->input->post('post-content');
                $users = get_at_users(' ' . $post_title . ' ' . $post_content . ' ');
                $post_content = parse_content($post_content);
                $add = $this->post->add_post($post_title, $post_content, $user['user_id'], $user['user_name'], $node_id);
                $post_id = $this->db->insert_id();
                $this->load->model('nodes');
                $this->nodes->refresh_node_post_no();
                //send mentioned message
                if (is_array($users)) {
                    $users = array_unique($users);
                    foreach ($users as $k => $v) {
                        $this->message->send_message(3, $v, $user['user_name'], $post_content, $post_id);
                    }
                }
                if (is_ajax()) {
                    json_output(0, 'post', array('post_id' => $add));
                } else {
                    redirect('t/' . $add);
                }
                break;

            /**
             * 是否过后可以修改？
             * 可修改时间为300s
             * @todo 文字处理未完成，所以还不好做更新
             */
            case 'update':
                $this->auth->check_admin();
                //@todo 将表单中的字段值设为数组类型
                $post = $this->input->post('post');
                $this->post->update_post($post);
                json_output(0, 'msg', '更新成功');

                break;

            case 'search':
                $key = $this->input->get('search-key');
                if (is_ajax()) {
                    if (!$key) {
                        json_output(array('error' => 1, 'msg' => 'no specified search key'));
                        exit;
                    }
                    //$page = $this->input->get('page');
                    // $no = $this->input->get('no');
                    $this->load->model('post');
                    $post = $this->post->search_post($key, 1, 10);
                    json_output(0, 'posts', $post);
                }
                redirect('t/search/' . urldecode($key));
                break;

            default:
                $do = $this->input->get('do');
                if (!in_array($do, array(' ', 'fav', 'unfav', 'up', 'down', 'delete', 'transfer', 'list')))
                    die('wrong params');
                $user = get_user();
                switch ($do) {
                    /**
                     * 删除帖子
                     * @todo json输出操作结果
                     */
                    case 'delete':
                        $this->auth->check_admin();
                        $this->post->delete_post($post_id);
                        redirect($this->from);
                        break;

                    /**
                     * 收藏帖子
                     * 
                     */
                    case 'fav':
                        $this->auth->check_login();
                        $this->load->model('follow');
                        $this->load->model('user');
                        $this->follow->add_follow($user['user_id'], 1, $post_id, $keyname = '');
                        $this->user->refresh_user_info($user['user_id']);
                        if (is_ajax())
                            json_output(0);
                        redirect('t/' . $post_id);
                        break;

                    case 'unfav':
                        $this->auth->check_login();
                        $this->load->model('follow');
                        $this->load->model('user');
                        $this->follow->del_follow($user['user_id'], 1, $post_id);
                        $this->user->refresh_user_info($user['user_id']);
                        if (is_ajax())
                            json_output(0);
                        redirect('t/' . $post_id);
                        break;

                    case 'transfer':
                        $this->auth->check_admin();
                        $node_name = $this->input->post('node-name');
                        if (!$node_name)
                            die('no node name specified');
                        if ($this->post->transfer_post($post_id, $node_name, 'node_name')) {
                            json_output(0);
                        }
                        break;

                    case 'up':
                        $this->load->model('post');
                        $this->post->rate_post($post_id, $rate = 'up', $user['user_id']);
                        redirect('/t/' . $post_id);
                        break;

                    case 'down':
                        $this->load->model('post');
                        $this->post->rate_post($post_id, $rate = 'down', $user['user_id']);
                        redirect('/t/' . $post_id);
                        break;

                    default:
                        $post = $this->post->post_info($post_id);
                        $code = 1;
                        if ($post)
                            $code = 0;
                        json_output($code, 'info', $post);

                        break;
                }

                break;
        }
    }

    /**
     * 评论 API
     *
     *
     */
    function comment($action = '') {
        $this->load->model('comment');
        switch ($action) {

            case 'add':
                $this->load->model('user');
                $this->auth->check_login();
                $this->load->helper('parse_content');
                $this->load->model('message');
                $user = $this->auth->get_user();
                $this->load->library('user_agent');
                $cm_other = json_encode(array('bs' => strtolower($this->agent->browser()) . '-' . intval($this->agent->version())));
                $post_id = $this->input->post('post-id');
                $cm_reply_to = $this->input->post('cm-reply-to');
                $cm_content = $this->input->post('cm-content');
                $cm_reply_name = $this->input->post('cm-reply-name');
                $cm_reply_id = $this->user->get_userid_by_name($cm_reply_name);
                $users = get_at_users($cm_content);
                $author = $this->input->post('post-author');
                $cm_content = parse_content($cm_content);
                $do = $this->comment->add_comment($cm_content, $post_id, $user['user_id'], $user['user_name'], $cm_reply_to, $cm_reply_name, $cm_reply_id, $cm_other);
                //send mentioned message
                if (is_array($users)) {
                    foreach ($users as $k => $v) {
                        $this->message->send_message(2, $v, $user['user_name'], $cm_content, $post_id = $post_id);
                    }
                }

                //send comment message
                if ($author != $user['user_name'])
                    $this->message->send_message(1, $author, $user['user_name'], $cm_content, $post_id = $post_id);

                $cm = $this->db->where('cm_id', $do)->get('vx_comment')->row_array();

                if (is_ajax()) {
                    json_output(0, 'data', $cm);
                    exit;
                }

                redirect('t/' . $post_id);
                break;

            case 'edit':
                $this->auth->check_admin();
                break;

            /**
             * 删除评论
             * 
             */
            case 'delete':
                $this->auth->check_admin();
                $cm_id = intval($this->input->get('cm_id'));
                echo $cm_id;
                if (!$cm_id) {
                    echo 'wrong params';
                }
                if ($this->comment->del_comment($cm_id)) {
                    echo '删除成功';
                } else {
                    echo '未成功';
                }
                //redirect('/messages');
                break;

            default:

                break;
        }
    }

    /**
     * message controller
     * @param int $m_id
     * @param string $action 
     * @return json|html
     */
    function message($m_id, $action = '') {
        $this->auth->check_login();
        $this->load->model('message');
        switch ($action) {
            /**
             * 发送私信
             * @method post
             * @return json|html if is ajaxed, return json
             */
            case 'send':
                $this->message->send_message(3, $toname, $fromname, $content, 0);
                break;
            /**
             * 删除消息
             */
            case 'delete':
                $this->message->del_message($m_id);
                redirect('/messages');
                break;
            /**
             * 将消息设为已读
             */
            case 'set_read':
                $id = $this->input->post('id');
                $type = $this->input->post('type');
                $this->message->set_read($id, $type);
                break;
            /**
             * 消息列表
             */
            case 'list':
                $user_id = $this->input->get('user_id');
                $rs = $this->message->list_message($user_id, 0, 1, 20);
                json_output(0, 'message', $rs);
                break;
        }
    }

    function site($action = '') {
        $this->auth->check_admin();
        switch ($action) {
            case 'status':

                break;

            case 'update_config':
                $this->load->model('configs');
                $config = $this->input->post('config');
                $this->configs->save_config_item($config);
                if (!$this->input->is_ajax_request()) {
                    redirect('/admin');
                } else {
                    json_output(0, 'message', array('message' => '更新成功'));
                }
                break;

            default:

                break;
        }
    }

    function member($username = "") {
        $this->load->model('user');
        switch ($username) {
            default:
                $user_info = $this->user->get_user_profile($username);
                json_output(0, 'member', $user_info);
                break;
        }
    }

    function page($action = '') {
        $this->load->model('pages');
        $this->load->library('form_validation');
        switch ($action) {
            case 'add':
                $page = $this->input->post('page');
                //   if($this->pages->add_page($page)==1){
                //      echo 'slug重复';
                //  }
                $this->form_validation->set_rules('page[page_title]', '标题', 'required|min_length[5]|xss_clean|trim');
                $this->form_validation->set_rules('page[page_slug]', 'slug', 'required|xss_clean|trim|is_unique[vx_page.page_slug]');
                $this->form_validation->set_rules('page[page_md_content]', '内容', 'required|xss_clean|trim');
                if ($this->form_validation->run()) {
                    $this->pages->add_page($page);
                } else {
                    echo validation_errors();
                }
                break;
            /**
             * Markdown 书写预览
             *
             */
            case 'preview':
                $this->auth->check_login();
                $this->load->library('Markdown');
                $raw = $this->input->post('content');
                echo Markdown($raw);
                break;

            case 'edit':
                show_404();
                break;
        }
    }

    /**
     * 图片上传
     * 
     * @access public
     */
    public function upload() {
        $this->load->model('configs');
        $lang = $this->configs->get_config_item('lang');

        $config = array(
            'upload_path' => './np-content/upload/',
            'allowed_types' => 'gif|jpg|png',
            'max_width' => 1024,
            'encrypt_name' => TRUE,
            'max_size' => 2048,
            'lang' => $lang
        );

        $this->load->library('upload', $config);

        if ($this->upload->do_upload('file')) {
            json_output(0, 'img', $this->upload->data());
        } else {
            json_output(1, 'msg', $this->upload->display_errors());
        }
    }

    function fetch() {

        function set_filename($path, $ext) {
            mt_srand();
            $filename = md5(uniqid(mt_rand())) . '.' . $ext;
            if (!file_exists($path . $filename)) {
                return $filename;
            }
            $filename = str_replace($ext, '', $filename);

            $new_filename = '';
            for ($i = 1; $i < 100; $i++) {
                if (!file_exists($path . $filename . $i . $ext)) {
                    $new_filename = $filename . $i . $ext;
                    break;
                }
            }

            if ($new_filename == '') {
                $this->set_error('upload_bad_filename');
                return FALSE;
            } else {
                return $new_filename;
            }
        }

        $path = 'np-content/upload/';
        $type = array(
            'image/gif' => 'gif',
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png'
        );

        $url = 'http://qzapp.qlogo.cn/qzapp/100343915/09A4CE4BEE02C91D86B76BE2B0AD37BF/100';
        $header = get_headers($url, 1);
        if (is_array($header)) {
            $contentType = $header['Content-Type'];
        }
        $ch = curl_init($url);
        $name = set_filename($path, $type[$contentType]);
        $fp = fopen($path . $name, "w");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    /**
     * 管理员相关API
     * 
     * @access public
     * @param string $action
     */
    public function admin($action = '') {
        $this->auth->check_admin();
        $this->load->model('admins');
        switch ($action) {
            /**
             * 添加管理员
             * 
             * error 为-1时表示参数缺失，0 时表示无错误，1时用户不存在，2时已是管理员，msg为错误说明
             * @param string $user_name
             * @return array
             */
            case 'add_admin':
                $user_name = $this->input->post('user_name');
                if (!$user_name) {
                    json_output(-1);
                    die('没有指定用户名');
                }
                $rs = $this->admins->add_admin($user_name);
                if ($this->is_ajax) {
                    json_output($rs['error'], 'msg', $rs['msg']);
                } else {
                    print_r($rs);
                    //redirect('/admin/settings?error=' . $rs['error'] . '&msg=' . $rs['msg']);
                }
                break;

            default:
                show_404();
                break;
        }
    }

}