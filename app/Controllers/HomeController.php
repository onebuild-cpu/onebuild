<?php
namespace App\Controllers;

use PDO;
use App\Core\Controller;

class HomeController extends Controller
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    public function index(): void
    {
        $this->view('home/index', [
            'pageTitle' => 'Welcome to NewSite',
            'metaDescription' => 'Build Your Amazing New Website',
        ]);
    }
}
