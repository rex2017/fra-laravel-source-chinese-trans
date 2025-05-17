<?php
/**
 * Index控制器，自己加可以删除！
 */

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Models\UserModel;
use Illuminate\Support\Facades\Route;

class IndexController extends BaseController
{
    
	public function index() {
		echo '这是框架的默认index界面，欢迎你！<br/>9021!<br/>v6.20.44！';
	}
	
}
