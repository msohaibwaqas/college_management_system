<?php

namespace App\Http\Controllers;

use App\Services\IdCard\IdCardService;
use Illuminate\Http\Request;

class IdCardController extends Controller
{
    public function __construct(
        protected IdCardService $idCardService
    ) {}

    public function index()
    {
        return view('students.id_cards.index');
    }

    public function generate(Request $request)
    {
        $cards = $this->idCardService->generateCards($request);
        $students = $cards; // Backward compatibility with blade template

        return view('students.id_cards.print', compact('cards', 'students'));
    }
}
