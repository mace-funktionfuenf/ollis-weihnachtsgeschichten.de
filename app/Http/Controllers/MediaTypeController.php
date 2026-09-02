<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MediaType;
use Illuminate\Contracts\View\View;

class MediaTypeController extends Controller
{
    public function show(MediaType $mediaType): View
    {
        $mediaType->loadMissing('products');

        return view('pages.media-type', ['mediaType' => $mediaType]);
    }
}
