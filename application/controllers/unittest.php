<?php

!defined('BASEPATH') && exit('No direct script access allowed');
/**
 * NodePrint
 *
 * Simple and Elegant Forum Software
 *
 * @package         NodePrint
 * @author          airyland <i@mao.li>
 * @copyright       Copyright (c) 2013, mao.li
 * @license         MIT
 * @link            https://github.com/airyland/nodeprint
 * @version         0.0.5
 */


/**
* Unit test
* @author airyland <i@mao.li>
*/
class Unittest extends CI_Controller{
	function __construct(){
		parent::__construct();
		$this->load->library('unit_test');
	}
	function index(){
		$test=1+1;
		$expected_result=2;
		$test_name="test";
		
		//$this->unit->run($test,$expected_result,$test_name);
		echo $this->unit->run($test, $expected_result,$test_name);
	}

/**
* ��¼���
*
*/
	function signin(){
		
	}

	function signup(){
		
	}

	function signout(){

	}

	function captcha(){
		
	}


//�������
	function list_topics(){
		
	}
	function delete_topic(){
		
	}
	function update_topic(){

	}
	
	
//�û����
	function list_users(){
		
	}

	function delete_user(){
		
	}
		function update_user(){
		
	}
	

	
//�ڵ����
	function list_nodes(){
		
	}
	
	function delete_node(){
		
	}



	function update_node(){
		
	}


//����Ա���
function add_admin(){

}


function delete_admin(){

	
}
	

	
}