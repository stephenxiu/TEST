<?php

defined('haipinlegou') or exit('Access Invalid!');
class member_fleaControl extends BaseMemberControl{
	public function __construct() {
		parent::__construct();
		
		Language::read('member_layout,member_store_goods_index,member_flea,home_flea_index');
	}
	
	public function indexOp() {
        $this->flea_listOp() ;
    }
	
    public function flea_listOp() {
		
		$model_store_goods	= Model('flea');
		
		$page	= new Page();
		$page->setEachNum(10);
		$page->setStyle('admin');

		$list_goods	= array();
		$search_array['member_id']	= $_SESSION['member_id'];
		$search_array['keyword']	= trim($_GET['keyword']);
		$search_array['order']	= 'goods_id desc';
		$list_goods	= $model_store_goods->listGoods($search_array,$page,'');

		if(is_array($list_goods) and !empty($list_goods)) {
			foreach ($list_goods as $key => $val) {
				$list_goods[$key]['goods_image'] = $list_goods[$key]['goods_image'] == '' ? '' : ATTACH_FLEAS.'/'.$_SESSION['member_id'].'/'.str_replace('_small', '_tiny', $val['goods_image']);
			}
		}
		Tpl::output('show_page',$page->show());
		Tpl::output('list_goods',$list_goods);

		$this->get_member_info();
    	self::profile_menu('goods','flea_list');
		Tpl::output('menu_sign','flea');
		Tpl::output('menu_sign_url','index.php?act=member_flea');
		Tpl::output('menu_sign1','flea_list');
        Tpl::showpage('store_flea_list');
    }
	
	public function add_goodsOp() {
		$lang	= Language::getLangContent();
	
		$model_flea = Model('flea');
		$goods_num=$model_flea->countGoods(array('member_id'=>$_SESSION['member_id']));
		if($goods_num >= 10){
			showMessage(Language::get('store_goods_index_flea_notice1'),'','','error');
		}
		
		$model_class		= Model('flea_class');
		$goods_class		= $model_class->getTreeClassList(1);
		Tpl::output('goods_class',$goods_class);
		
		$this->area_show();
	
		$goods_image_path	= ATTACH_FLEAS.'/'.$_SESSION['member_id'].'/';	
		Tpl::output('goods_image_path',$goods_image_path);
		Tpl::output('item_id','');
		$this->get_member_info();
		self::profile_menu('add_goods','goods_add');
		Tpl::output('menu_sign','flea');
		Tpl::output('menu_sign_url','index.php?act=member_flea');
		Tpl::output('menu_sign1','add_flea_goods');
		Tpl::showpage('store_flea_goods_add');
	}
	
	public function save_goodsOp() {
		$lang	= Language::getLangContent();
		
		
		$model_upload = Model('flea_upload');
		$upload_array = array();
		$upload_array['store_id']		= $_SESSION['member_id'];
		$upload_array['upload_type']	= '12';
		$upload_array['item_id']		= '0';
		$upload_array['upload_time_lt']	= time()-24*60*60;
		$model_upload->delByWhere($upload_array);
		unset($upload_array);
		
		if (!empty($_POST['form_submit']) && $_POST['form_submit'] == 'ok'){
			
			$obj_validate = new Validate();
			$obj_validate->validateparam = array(
			array("input"=>$_POST["goods_name"],"require"=>"true","message"=>$lang['store_goods_index_flea_name_null']),
			array("input"=>$_POST["goods_price"],"require"=>"true","validator"=>"Double","message"=>$lang['store_goods_index_flea_price_null'])
			);
			$error = $obj_validate->validate();
			if ($error != ''){
				showMessage($lang['error'].$error,'','html','error');
			}
			
			$model_store_goods	= Model('flea');

			$goods_array			= array();
			$goods_array['goods_name']		= $_POST['goods_name'];
			$goods_array['gc_id']			= $_POST['cate_id'];
			$goods_array['gc_name']			= $_POST['cate_name'];
			$goods_array['flea_quality']	= $_POST['sh_quality'];
			$goods_array['flea_pname']		= $_POST['flea_pname'];
			$goods_array['flea_area_id']	= $_POST['area_id'];
			$goods_array['flea_area_name']	= $_POST['area_info'];
			$goods_array['flea_pphone']		= $_POST['flea_pphone'];
			$goods_array['goods_tag']		= $_POST['goods_tag'];
			$goods_array['goods_price']		= $_POST['goods_price'];
			$goods_array['goods_store_price']= $_POST['price'][0] != '' ? $_POST['price'][0] : $_POST['goods_store_price'];
			$goods_array['goods_show']		= '1';
			$goods_array['goods_commend']	= $_POST['goods_commend'];
			$goods_array['goods_body']		= $_POST['goods_body'];
			$goods_array['goods_keywords']		= $_POST['seo_keywords'];
			$goods_array['goods_description']   = $_POST['seo_description'];
			$state = $model_store_goods->saveGoods($goods_array);
			if($state) {
				
				$upload_array = array();
				$upload_array['store_id']	= $_SESSION['member_id'];
				$upload_array['item_id']	= '0';
				$upload_array['upload_type_in'] = "'12','13'";
				$upload_array['upload_id_in']	= "'".implode("','", $_POST['goods_file_id'])."'";
				$model_upload->updatebywhere(array('item_id'=>$state),$upload_array);
				
				if(!empty($_POST['goods_file_id'][0])) {
					$image_info	= $model_store_goods->getListImageGoods(array('upload_id'=>intval($_POST['goods_file_id'][0])));
					$goods_image	= $image_info[0]['file_thumb'];
					$model_store_goods->updateGoods(array('goods_image'=>$goods_image),$state);
				}
				showMessage($lang['store_goods_index_flea_add_success'],'index.php?act=member_flea');
			} else {
				showMessage($lang['store_goods_index_flea_add_fail'],'index.php?act=member_flea','html','error');
			}
		}
	}
	
	public function drop_goodsOp() {
		$lang	= Language::getLangContent();
		
		$model_store_goods	= Model('flea');
	
		$goods_id = trim($_GET['goods_id']);
		if(empty($goods_id)) {
			showMessage(Language::get('para_error'),'','html','error');
		}
		$goods_id_array = explode(',',$goods_id);
		$input_goods_count = count($goods_id_array);
		$para = array();
		$para['member_id'] = $_SESSION['member_id'];
		$para['goods_id_in'] = $goods_id;
		$verify_count = intval($model_store_goods->countGoods($para));
		if($input_goods_count !== $verify_count) {
			showDialog(Language::get('para_error'),'','html','error');
		}

		$state	= $model_store_goods->dropGoods($goods_id);
		if($state) {
			showDialog($lang['store_goods_index_flea_del_success'],'index.php?act=member_flea','succ');
		} else {
			showDialog($lang['store_goods_index_flea_del_fail'],'index.php?act=member_flea','error');
		}
	}
	
	public function edit_goodsOp() {
		$lang	= Language::getLangContent();
		
		$model_store_goods	= Model('flea');
		$goods_array		= $model_store_goods->listGoods(array('goods_id'=>intval($_GET['goods_id'])),'','flea.*');
		Tpl::output('goods',$goods_array[0]);
		Tpl::output('goods_id',$goods_array[0]['goods_id']);

		$goods_image_path	= ATTACH_FLEAS.'/'.$_SESSION['member_id'].'/';	
		$goods_image		= $model_store_goods->getListImageGoods(array(
			'image_store_id'=>$_SESSION['member_id'],
			'item_id'=>$goods_array[0]['goods_id'],
			'image_type'=>12
		));
		if(is_array($goods_image) and !empty($goods_image)) {
			$goods_image_1	= $goods_image_path.$goods_array[0]['goods_image'];
			$image_key = 0;
			foreach ($goods_image as $key => $val) {
				$val['file_name']	= $goods_image_path.$val['file_name'];
				$val['file_thumb']	= $goods_image_path.$val['file_thumb'];
				if($val['file_wm']=='') {
					$val['file_wm']	= $val['file_name'];
				}else{
					$val['file_wm']	= $goods_image_path.$val['file_wm'];
				}
				$val['file_name']	= $val['file_wm'];
				$goods_image[$key]	= $val;
				if($goods_image_1==$val['file_thumb'] || $goods_image_1==$val['file_wm']) $image_key = $key;
			}
			if($image_key > 0) {
				$goods_image_0	= $goods_image[0];
				$goods_image[0]	= $goods_image[$image_key];
				$goods_image[$image_key]	= $goods_image_0;
			}
		}
		Tpl::output('goods_image',$goods_image);
		Tpl::output('goods_image_path',$goods_image_path);
		
		$model_class		= Model('flea_class');
		$goods_class		= $model_class->getTreeClassList(1);
		Tpl::output('goods_class',$goods_class);
		Tpl::output('item_id',$goods_array[0]['goods_id']);
		
		$this->get_member_info();
		self::profile_menu('add_goods','goods_edit',array('menu_key'=>'goods_edit','menu_name'=>$lang['store_goods_index_edit_flea'],'menu_url'=>'#'));
		Tpl::output('menu_sign','flea');
		Tpl::output('menu_sign_url','index.php?act=member_flea');
		Tpl::output('menu_sign1','edit_flea');
		
		$this->area_show();
		Tpl::showpage('store_flea_goods_add');
	}
	
	public function edit_save_goodsOp() {
		$lang	= Language::getLangContent();
		$goods_id	= intval($_POST['goods_id']);
		if ($_POST['form_submit'] == 'ok' &&  $goods_id!= 0){
			
			$obj_validate = new Validate();
			$obj_validate->validateparam = array(
			array("input"=>$_POST["goods_name"],"require"=>"true","message"=>$lang['store_goods_index_flea_name_null']),
			array("input"=>$_POST["goods_price"],"require"=>"true","validator"=>"Double","message"=>$lang['store_goods_index_flea_price_null'])
			);
			$error = $obj_validate->validate();
			if ($error != ''){
				showMessage($lang['error'].$error,'','html','error');
			}
			
			$model_store_goods	= Model('flea');
			$goods_array			= array();
			$goods_array['goods_name']		= $_POST['goods_name'];
			if (intval($_POST['cate_id']) != 0) {
				$goods_array['gc_id']			= $_POST['cate_id'];
				$goods_array['gc_name']			= $_POST['cate_name'];
			}
			$goods_array['flea_quality']	= $_POST['sh_quality'];
			$goods_array['flea_pname']		= $_POST['flea_pname'];
			$goods_array['flea_pphone']		= $_POST['flea_pphone'];
			$goods_array['flea_area_id']	= $_POST['area_id'];
			$goods_array['flea_area_name']	= $_POST['area_info'];
			$goods_array['goods_tag']		= $_POST['goods_tag'];
			$goods_array['goods_price']		= $_POST['goods_price'];
			$goods_array['goods_store_price']= $_POST['price'][0] != '' ? $_POST['price'][0] : $_POST['goods_store_price'];
			$goods_array['goods_show']		= '1';
			$goods_array['goods_commend']	= $_POST['goods_commend'];
			$goods_array['goods_body']		= $_POST['goods_body'];
			$goods_array['goods_keywords']		= $_POST['seo_keywords'];
			$goods_array['goods_description']   = $_POST['seo_description'];
			$state = $model_store_goods->updateGoods($goods_array,$goods_id);
			if($state) {
				
				if(!empty($_POST['goods_file_id'][0])) {
					$image_info	= $model_store_goods->getListImageGoods(array('upload_id'=>intval($_POST['goods_file_id'][0])));
					$goods_image	= $image_info[0]['file_thumb'];
					$model_store_goods->updateGoods(array('goods_image'=>$goods_image),$goods_id);
				}
				showMessage($lang['store_goods_index_flea_goods_edit_success'],'index.php?act=member_flea');
			} else {
				showMessage($lang['store_goods_index_flea_goods_edit_fail'],'index.php?act=member_flea','html','error');
			}
		}
	}
	
	public function image_uploadOp() {
		$lang	= Language::getLangContent();
		if ($_GET['upload_type'] == 'uploadedfile') {
			if($_POST['file_id'] != ''){
				$model_store_goods	= Model('flea');
				$drop_stata			= $model_store_goods->dropImageGoods(array('upload_id'=>intval($_POST['file_id'])));
			}
			
			$upload = new UploadFile();
			$upload->set('default_dir',ATTACH_FLEAS.DS.$_SESSION['member_id'].DS.$upload->getSysSetPath());
			$upload->set('max_size',C('image_max_filesize'));
			$thumb_width	= C('thumb_small_width').','.C('thumb_mid_width').','.C('thumb_max_width').','.C('thumb_tiny_width').',240';
			$thumb_height	= C('thumb_small_height').','.C('thumb_mid_height').','.C('thumb_max_height').','.C('thumb_tiny_height').',50000';

			$upload->set('thumb_width',	$thumb_width);
			$upload->set('thumb_height',$thumb_height);
			$upload->set('thumb_ext',	'_small,_mid,_max,_tiny,_240');	
			
			$result = $upload->upfile('file');
			if ($result){
				$_POST['pic'] 		= $upload->getSysSetPath().$upload->file_name;
				$_POST['pic_thumb'] = $upload->getSysSetPath().$upload->thumb_image;
			}else {
				echo "<script type='text/javascript'>alert('". $upload->error ."');history.back();</script>";				
				exit;
			}
		
			$img_path = $_POST['pic'];
		
			
			list($width, $height, $type, $attr) = getimagesize(BasePath.DS.'upload'.DS.'flea'.DS.'goods'.DS.$_SESSION['member_id'].DS.$img_path);
			
			
			$model_upload = Model('flea_upload');
			$insert_array = array();
			$image_type	  = array('goods_image'=>12,'desc_image'=>13);
			$insert_array['file_name']	= $_POST['pic'];
			$insert_array['file_thumb']	= $_POST['pic_thumb'];
			$insert_array['file_size']	= intval($_FILES['file']['size']);
			$insert_array['upload_time']= time();
			$insert_array['item_id']	= intval($_POST['item_id']);
			$insert_array['store_id']	= $_SESSION['member_id'];
			$insert_array['upload_type']= $image_type['goods_image'];
			$result2 = $model_upload->add($insert_array);
			
			$data = array();
			$data['file_id']	= $result2;
			$data['file_name']	= $_POST['pic_thumb'];
			$data['file_path']	= $_POST['pic_thumb'];
			$data['instance']	= 'goods_image';
			$data['id']			= $_POST['id'];
			
			
			$output = json_encode($data);
			echo "<script type='text/javascript'>window.parent.add_uploadedfile('" . $output . "');</script>";
			
		}
		Tpl::showpage('flea_upload_image','null_layout');
	}
	
	public function check_classOp() {
		if($_GET['required'] == 'false' and $_GET['cate_id'] == '0'){
			echo 'true';
			exit;
		}
		
		$model_class		= Model('flea_class');;
		$sub_class			= $model_class->getClassList(array('gc_parent_id'=>intval($_GET['cate_id'])));
		if(is_array($sub_class) and !empty($sub_class)) {
			echo 'false';
		} else {
			echo 'true';
		}
	}
	
	public function favoritesOp(){
		
		$lang	= Language::getLangContent();
		
		$favorites_class = Model('flea_favorites');
		
		
		if (!empty($_GET['drop']) && $_GET['drop'] == ok && !empty($_GET['fav_id'])){
			$fav_arr = explode(',',$_GET['fav_id']);
			if (!empty($fav_arr) && is_array($fav_arr)){
				
				foreach ($fav_arr as $fav_id){
					if (intval($fav_id) > 0){
						if (!$favorites_class->delFavorites(intval($fav_id),'flea')){
							showDialog($lang['flea_favorite_del_fail'],'','error');
						}
					}
				}
			}else {
				if (intval($_GET['fav_id']) > 0){
					if (!$favorites_class->delFavorites(intval($_GET['fav_id']),'flea')){
						showDialog($lang['flea_favorite_del_fail'],'','error');
					}
				}
			}
			showDialog($lang['flea_favorite_del_success'],'index.php?act=member_flea&op=favorites','succ');
		}
		
		$page	= new Page();
		$page->setEachNum(10);
		$page->setStyle('admin');
		
		$favorites_list = $favorites_class->getFavoritesList(array('member_id'=>$_SESSION['member_id'],'fav_type'=>'flea'),$page);
		if (!empty($favorites_list) && is_array($favorites_list)){
			$favorites_id = array();
			$favorites_key = array();
			foreach ($favorites_list as $key=>$favorites){
				$fav_id = $favorites['fav_id'];
				$favorites_id[] = $fav_id;
				$favorites_key[$fav_id] = $key;
			}
			
			$type_class = Model('flea');
			$type_list = $type_class->listGoods(array('goods_id_in'=>"'".implode("','",$favorites_id)."'"),'',
					'goods_id,goods_name,goods_image,goods_store_price,member_name,member_id');
			if (!empty($type_list) && is_array($type_list)){
				foreach ($type_list as $key=>$fav){
					$fav_id = $fav['goods_id'];
					$key = $favorites_key[$fav_id];
					$favorites_list[$key]['flea'] = $fav;
				}
			}
				
		}
		
		$this->get_member_info();
		
		self::profile_menu('favorites','favorites');
		Tpl::output('menu_sign','flea_favorites');
		Tpl::output('favorites_list',$favorites_list);
		Tpl::output('show_page',$page->show());
		Tpl::output('menu_sign_url','index.php?act=member_flea&op=favorites');
		Tpl::output('menu_sign1','flea_favorites_list');
		Tpl::showpage("favorites_flea_index");
	}
	
	public function addfavoritesOp(){
		
		$lang	= Language::getLangContent('UTF-8');
		if (intval($_GET['fav_id']) > 0){
			
			$favorites_class = Model('flea_favorites');
			$model_flea = Model('flea');
			$flea_info = $model_flea->listGoods(array(
				'goods_id'=>intval($_GET['fav_id'])
			));
			if ($flea_info[0]['member_id'] == $_SESSION['member_id']){
				echo json_encode(array('done'=>false,'msg'=>$lang['flea_favorite_no_my_product']));
				die;
			}
			
			$check_rss = $favorites_class->checkFavorites(intval($_GET['fav_id']),'flea',$_SESSION['member_id']);
			if (!$check_rss){
				$condition['flea_collect_num']['value']=1;
				$condition['flea_collect_num']['sign']='increase';
				$flea_info = $model_flea->updateGoods($condition,$_GET['fav_id']);
				
				$add_rs = $favorites_class->addFavorites(array('member_id'=>$_SESSION['member_id'],'fav_id'=>intval($_GET['fav_id']),'fav_type'=>'flea','fav_time'=>time()));
				if (!$add_rs){
					echo json_encode(array('done'=>false,'msg'=>$lang['flea_favorite_collect_fail']));
				}
			}
			echo json_encode(array('done'=>true,'msg'=>$lang['flea_favorite_collect_success']));
		}else {
			echo json_encode(array('done'=>false,'msg'=>$lang['flea_favorite_collect_fail']));
		}
	}
	
	private function profile_menu($menu_type,$menu_key='',$array=array()) {
		$lang	= Language::getLangContent();
		$menu_array		= array();
		switch ($menu_type) {
			case 'goods':
				$menu_array	= array(
					1=>array('menu_key'=>'flea_list',	'menu_name'=>$lang['nc_member_path_flea_list'],			'menu_url'=>'index.php?act=member_flea')
				);
				break;
			case 'add_goods':
				$menu_array = array(
					1=>array('menu_key'=>'flea_list',	'menu_name'=>$lang['nc_member_path_flea_list'],			'menu_url'=>'index.php?act=member_flea'),
					2=>array('menu_key'=>'goods_add',	'menu_name'=>$lang['nc_member_path_add_flea_goods'],	'menu_url'=>'index.php?act=member_flea&op=add_goods')
				);
				break;
			case 'favorites':
				$menu_array = array(
					1=>array('menu_key'=>'favorites',	'menu_name'=>$lang['nc_member_path_flea_favorites_list'],	'menu_url'=>'index.php?act=member_flea&op=favorites')
				);
		}
		if(!empty($array)) {
			$menu_array[] = $array;
		}
		Tpl::output('member_menu',$menu_array);
		Tpl::output('menu_key',$menu_key);
	}


	private function area_show(){
		
		$area_model=Model('flea_area');
		$condition['flea_area_parent_id']='0';
		$condition['field']='flea_area_id,flea_area_name';
		$area_one_level=$area_model->getListArea($condition);
		Tpl::output('area_one_level',$area_one_level);
	}
}