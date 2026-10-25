<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardArtPreviewRequest;
use App\Models\CardCategory;
use App\Models\GalleryImage;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use App\Services\ImageUploadService;
use App\Models\StoresProfile;
use App\Services\NextLoad\NextLoadCatalog;
use App\Services\CardArt\CardArtCatalog;
use App\Services\CardArt\CardArtPresenter;
use App\Services\CardArt\CardCatalogBrowser;

class CardCategoryController extends Controller
{
    protected $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    /**
     * Display a listing of the card categories.
     */
    public function index()
    {
        $cardArt = app(CardArtPresenter::class);
        $categories = CardCategory::with('galleryImages')->get()
            ->each(fn (CardCategory $category) => $category->setAttribute('card_art', $cardArt->forCategory($category)));

        return view('manager.card-categories', [
            'categories' => $categories,
            'artBrands' => array_map(fn (array $brand) => $brand['label'], CardArtCatalog::BRANDS),
            'artCountries' => CardArtCatalog::COUNTRIES,
        ]);
    }

    /**
     * Public sellable categories. Never eager-load `cards`: unsold codes must not leave the server.
     */
    public function getCategories(?string $search = null)
    {
        $catalog = app(NextLoadCatalog::class);
        $cardArt = app(CardArtPresenter::class);

        return CardCategory::query()
        ->when($search !== null && $search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
        ->where(function ($query) {
            $query->where(function ($local) {
                $local->where('supplier_mode', '!=', CardCategory::MODE_SUPPLIER_ONLY)
                    ->whereHas('cards', fn ($cards) => $cards->where('status', true));
            })->orWhere(function ($supplier) {
                $supplier->whereIn('supplier_mode', [CardCategory::MODE_LOCAL_FIRST, CardCategory::MODE_SUPPLIER_ONLY])
                    ->whereNotNull('next_load_product_id');
            });
        })
        ->withAvailableCount()
        ->with('galleryImages')
        ->get()
        ->each(function (CardCategory $category) use ($catalog) {
            $local = $category->usesLocalStock() ? (int) $category->available_count : 0;
            // Supplier stock is only looked up when local codes cannot serve the sale.
            $category->setAttribute('available_count', $local > 0 ? $local : $catalog->supplierStock($category));
        })
        ->filter(fn (CardCategory $category) => $category->available_count > 0)
        ->values()
        ->map(function (CardCategory $category) use ($cardArt) {
            $category->setAttribute('card_art', $cardArt->forCategory($category));
            $category->setAttribute('classification', $cardArt->classify($category));
            $category->setAttribute('gallery', $category->galleryApiPayload());
            $category->setAttribute('reviews', $category->reviewsApiPayload());
            // Ensure description is present on list payloads
            $category->makeVisible(['description']);
            // Only an active POS link is exposed; null makes the shop use the shared gift-card SKU.
            $category->setAttribute('pos_variation_id', $category->activePosVariationId());
            return $category;
        });
    }

    public function sell(Request $request, CardCatalogBrowser $browser)
    {
        return $this->sellView($request, $browser, null);
    }

    public function sellApi()
    {
        return response()->json([
            'status' => true,
            'data' => $this->getCategories(),
        ]);
    }

    /**
     * Public API: single card category with description, gallery, and approved reviews.
     */
    public function showApi(CardCategory $cardCategory)
    {
        $cardCategory->load('galleryImages');
        $cardCategory->loadCount(['cards as available_count' => fn ($query) => $query->where('status', true)]);
        $local = $cardCategory->usesLocalStock() ? (int) $cardCategory->available_count : 0;
        $available = $local > 0 ? $local : app(NextLoadCatalog::class)->supplierStock($cardCategory);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cardCategory->id,
                'name' => $cardCategory->name,
                'price' => $cardCategory->price,
                'pos_variation_id' => $cardCategory->activePosVariationId(),
                'description' => $cardCategory->description,
                'poster_image' => $cardCategory->poster_image
                    ? asset($cardCategory->poster_image)
                    : null,
                'card_art' => app(CardArtPresenter::class)->forCategory($cardCategory),
                'classification' => app(CardArtPresenter::class)->classify($cardCategory),
                'gallery' => $cardCategory->galleryApiPayload(),
                'reviews' => $cardCategory->reviewsApiPayload(),
                'cards_count' => $available,
            ],
        ]);
    }

    public function searchCategories($search)
    {
        return $this->getCategories((string) $search);
    }

    public function searchSellCategories(Request $request, CardCatalogBrowser $browser)
    {
        return $this->sellView($request, $browser, (string) $request->input('q', ''));
    }

    /**
     * Sell page: brand tiles first; ?brand=&country= narrows the grid; ?q= or ?all=1 shows the full grid.
     */
    private function sellView(Request $request, CardCatalogBrowser $browser, ?string $query)
    {
        $categories = $this->getCategories($query);
        $groups = $browser->groups($categories);
        $brand = $this->browseKey($request->query('brand'), [...CardArtCatalog::brandKeys(), CardArtCatalog::OTHER_BRAND]);
        $country = $brand ? $this->browseKey($request->query('country'), [
            ...CardArtCatalog::countryKeys(), CardArtCatalog::GLOBAL_COUNTRY, CardArtCatalog::OTHER_COUNTRY,
        ]) : null;
        $brandGroup = collect($groups)->firstWhere('brand', $brand);
        if ($brandGroup && $country === null && count($brandGroup['countries']) === 1) {
            $country = $brandGroup['countries'][0]['key'];
        }

        return view('manager.sell-cards', [
            'categories' => $brand ? $browser->filter($categories, $brand, $country) : $categories,
            'groups' => $groups,
            'brandGroup' => $brandGroup,
            'brand' => $brand,
            'country' => $country,
            'browsing' => blank($query) && $brand === null && ! $request->boolean('all'),
            'storeProfiles' => StoresProfile::all(),
            'query' => $query,
        ]);
    }

    /** @param  list<string>  $allowed */
    private function browseKey(mixed $value, array $allowed): ?string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }

    public function create()
    {
        return view('card_categories.create');
    }

    protected function validatation(Request &$request, $id = null)
    {
        $name_validation = 'required|string|unique:card_categories,name';
        return $request->validate([
            'name' => $id ? $name_validation . ',' . $id : $name_validation,
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'poster_image' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif|max:2048',
            'gallery_images' => 'nullable|array|max:' . GalleryImage::MAX_PER_PRODUCT,
            'gallery_images.*' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif|max:2048',
            'delete_gallery_ids' => 'nullable|array',
            'delete_gallery_ids.*' => 'integer',
        ] + CardArtPreviewRequest::artRules());
    }

    /**
     * Saved category fields shared by store and update (brand/country/face value overrides included).
     *
     * @return array<string, mixed>
     */
    private function categoryData(Request $request): array
    {
        return $request->only('name', 'price')
            + ['description' => HtmlSanitizer::sanitize($request->input('description'))]
            + CardArtPreviewRequest::normalizedArt($request);
    }

    public function store(Request $request)
    {
        $this->validatation($request);

        $data = $this->categoryData($request);

        if ($request->hasFile('poster_image')) {
            $data['poster_image'] = $this->imageUploadService->upload($request->file('poster_image'), 'posters');
        }

        $category = CardCategory::create($data);
        $category->syncGalleryFromRequest($request, $this->imageUploadService);

        return response()->json([
            'success' => true,
            'message' => 'Card Category created successfully.',
            'data' => $category->load('galleryImages'),
        ]);
    }

    public function show(CardCategory $cardCategory)
    {
        return view('card_categories.show', compact('cardCategory'));
    }

    public function edit(CardCategory $cardCategory)
    {
        $cardCategory->load('galleryImages');

        return response()->json([
            'id' => $cardCategory->id,
            'name' => $cardCategory->name,
            'price' => $cardCategory->price,
            'description' => $cardCategory->description,
            'poster_image' => $cardCategory->poster_image,
            'gallery' => $cardCategory->galleryApiPayload(),
            'art_brand' => $cardCategory->art_brand,
            'art_country' => $cardCategory->art_country,
            'art_value' => $cardCategory->art_value,
        ]);
    }

    public function update(Request $request, CardCategory $cardCategory)
    {
        $this->validatation($request, $cardCategory->id);

        $data = $this->categoryData($request);

        if ($request->hasFile('poster_image')) {
            if ($cardCategory->poster_image && file_exists(public_path($cardCategory->poster_image))) {
                unlink(public_path($cardCategory->poster_image));
            }
            $data['poster_image'] = $this->imageUploadService->upload($request->file('poster_image'), 'posters');
        }

        $cardCategory->update($data);
        $cardCategory->deleteGalleryIds($request->input('delete_gallery_ids', []));
        $cardCategory->syncGalleryFromRequest($request, $this->imageUploadService);

        return response()->json([
            'success' => true,
            'message' => 'Card Category updated successfully.',
            'data' => $cardCategory->fresh()->load('galleryImages'),
        ]);
    }

    public function destroy(CardCategory $cardCategory)
    {
        try {
            $cardCategory->galleryImages()->get()->each->delete();
            $cardCategory->delete();

            return response()->json([
                'success' => true,
                'message' => 'Card Category deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete Card Category.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
