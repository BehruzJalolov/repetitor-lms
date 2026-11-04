<?php

namespace App\Http\Controllers;

use App\Support\MediaStorage;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function show(string $path): Response|StreamedResponse
    {
        return MediaStorage::response($path, 'public');
    }
}
