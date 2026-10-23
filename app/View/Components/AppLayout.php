<?php

namespace App\View\Components;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public string $computedTitle;
    public string $computedDescription;
    public string $computedImage;
    public string $computedType;
    public string $computedUrl;
    public ?Product $productModel;

    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $type = null,
        public mixed $product = null,
        public ?string $canonical = null,
    ) {
        $this->productModel = $product instanceof Product ? $product : null;

        $this->computedTitle = $title
            ? "{$title} · Novastaq Store"
            : 'Novastaq Store — Phones, Laptops, Software & AI Subscriptions';

        if ($this->productModel) {
            $desc = $this->productModel->short_description ?: strip_tags($this->productModel->description ?? '');
            $this->computedDescription = Str::limit(trim(preg_replace('/\s+/', ' ', $desc)), 160)
                ?: 'Buy genuine products and subscriptions securely with instant delivery from Novastaq.';

            $firstImg = $this->productModel->images->first();
            $this->computedImage = $firstImg ? $firstImg->url() : url('/logo.png');
            $this->computedType = 'product';
            $this->computedUrl = route('product', $this->productModel);
        } else {
            $this->computedDescription = $description
                ?: 'Shop genuine phones, laptops, electronics, software licenses, and AI subscriptions with secure checkout from Novastaq Technologies.';
            $this->computedImage = $image ?: url('/logo.png');
            $this->computedType = $type ?: 'website';
            $this->computedUrl = $canonical ?: request()->url();
        }
    }

    public function render(): View
    {
        return view('layouts.app');
    }
}
