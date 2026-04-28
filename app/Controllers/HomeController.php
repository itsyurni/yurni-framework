<?php



namespace App\Controllers;

use App\Models\Post;
use yurni\Controller;
use yurni\Http\Request;

class HomeController extends Controller
{




    public function index(Request $request)
    {
     
        return $this->render('Home');
    }

}
