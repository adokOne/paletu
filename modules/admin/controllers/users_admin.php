<?php defined('SYSPATH') or die('No direct script access.');
class Users_Admin extends Constructor
{
    protected $item_table   = 'users';
    protected $orderby      = 'username';
    protected $use_form     = TRUE;
    protected $use_combo    = FALSE;
    protected $use_logo     = TRUE;
    protected $logo_path = 'upload/avatars/';
    protected $logo_ext = '.jpg';
    protected $logo_prefix = '/pic_320';
    protected $where_statement = "id IN (Select user_id FROM roles_users Where role_id=2)";
    
    protected $grid_columns = array(
        "id",
        "username",
    	"name",
        "phone",
        "email",
        "password",
        "created_at",
        "last_login",
        "activation",
        "pwd_change",
        "email_change",
        "active",
        "moderated",
        "login_from",
    	"show_on_main",
    	"about",
        "has_logo"
    
    );


    protected function _todb(&$data, $id = FALSE)
    {
        if ($id > 0) {
            $password1 = trim($this->input->post('password1'));
            $password2 = trim($this->input->post('password2'));
            if ( ($password1 == $password2) && (strlen($password1) > 4) ) {
                $auth = Auth::instance();
		$salt = $auth->find_salt(ORM::factory('user', $id)->password);
                //print_r($data);die
		$data['password'] = $auth->hash_password($password1, $salt);
            }
            $this->db->update($this->item_table, (array)$data, "id = $id");
        } else {
            $id = $this->db->insert($this->item_table, (array)$data)->insert_id();
            $user  =  ORM::factory("user")->find($id);
            $user->password = 123456;
            $user->add(ORM::factory('role', 'member'));
            $user->save();
        }

        return $id;
    }
        
}
