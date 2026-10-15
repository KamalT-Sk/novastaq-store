<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function download(Request $request, Order $order, OrderItem $item): StreamedResponse
    {
        abort_unless($order->isPaid(), 403, 'Order must be confirmed before downloading.');
        abort_unless($item->order_id === $order->id, 404);
        abort_unless($item->digital_file, 404, 'No download file attached.');

        $disk = Storage::disk('public');
        abort_unless($disk->exists($item->digital_file), 404, 'File not found on server.');

        $extension = pathinfo($item->digital_file, PATHINFO_EXTENSION);
        $downloadName = \Illuminate\Support\Str::slug($item->name).($extension ? '.'.$extension : '');

        return $disk->download($item->digital_file, $downloadName);
    }
}

