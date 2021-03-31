<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class UserController extends Controller {
	
	public function show($id) {
		echo $id;
		$users = DB::table('users')->get();
		var_dump($users);
	}
	
}
