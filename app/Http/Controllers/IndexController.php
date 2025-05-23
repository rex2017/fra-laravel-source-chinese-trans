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
		echo 'Laravel Version: 6.20.44<br/>';
		echo 'PHP Version: 7.2<br/>';
		echo 'Start Date: 2022-05-14<br/>';
		echo 'Date: 2019-09-04';
	}
	
}
