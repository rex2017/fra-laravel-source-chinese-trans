<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class UserController extends Controller {
	
	protected $users;
	
    //public function __construct(UserRepository $users) {
    //    $this->users = $users;
    //}
	
	public function aaa() {
		echo 'AAA';
	}
	
    public function show($id) {
		var_dump($id);
		exit;
        $user = $this->users->find($id);
        return view('user.profile', ['user' => $user]);
    }
	
}