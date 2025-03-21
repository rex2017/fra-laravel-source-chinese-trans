<?php
/**
 * 测试控制器，自己加可以删除！
 */

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Models\UserModel;

class TestController extends BaseController
{
    
	public function test() {
		//$user = DB::table('users2')->get();
		//var_dump($user);
		//$user = UserModel::all();
		//Storage::disk('app_log')->put('app_log.txt', 'Contents');
	}
	
}
