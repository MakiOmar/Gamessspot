<?php

namespace App\Http\Controllers;

use App\Models\CardCategory;
use App\Models\GalleryImage;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use App\Services\ImageUploadService;
use App\Models\StoresProfile;

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
        $categories = CardCategory::with('galleryImages')->get();
        return view('manager.card-categories', compact('categories'));
    }

    public function getCategories()
    {
        return CardCategory::whereHas('cards', function ($query) {
            $query->where('status', true);
        })
        ->with([
            'cards' => function ($query) {
                $query->where('status', true);
            },
            'galleryImages',
        ])
        ->get()
        ->map(function (CardCategory $category) {
            $category->setAttribute('gallery', $category->galleryApiPayload());
            $category->setAttribute('reviews', $category->reviewsApiPayload());
            // Ensure description is present on list payloads
            $category->makeVisible(['description']);
            return $category;
        });
    }

    public function sell()
    {
        $categories = $this->getCategories();
        $storeProfiles = StoresProfile::all();

        return view('manager.sell-cards', compact('categories', 'storeProfiles'));
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
        $cardCategory->load(['galleryImages', 'cards' => function ($query) {
            $query->where('status', true);
        }]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cardCategory->id,
                'name' => $cardCategory->name,
                'price' => $cardCategory->price,
                'description' => $cardCategory->description,
                'poster_image' => $cardCategory->poster_image
                    ? asset($cardCategory->poster_image)
                    : null,
                'gallery' => $cardCategory->galleryApiPayload(),
                'reviews' => $cardCategory->reviewsApiPayload(),
                'cards_count' => $cardCategory->cards->count(),
            ],
        ]);
    }

    public function searchCategories($search)
    {
        return CardCategory::whereHas('cards', function ($query) {
            $query->where('status', true);
        })
        ->where('name', 'like', '%' . $search . '%')
        ->with([
            'cards' => function ($query) {
                $query->where('status', true);
            },
            'galleryImages',
        ])
        ->get();
    }

    public function searchSellCategories(Request $request)
    {
        $query = $request->input('q');
        $categories = $this->searchCategories($query);
        $storeProfiles = StoresProfile::all();

        return view('manager.sell-cards', [
            'categories' => $categories,
            'storeProfiles' => $storeProfiles,
            'query' => $query,
        ]);
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
        ]);
    }

    public function store(Request $request)
    {
        $this->validatation($request);

        $data = $request->only('name', 'price');
        $data['description'] = HtmlSanitizer::sanitize($request->input('description'));

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
        ]);
    }

    public function update(Request $request, CardCategory $cardCategory)
    {
        $this->validatation($request, $cardCategory->id);

        $data = $request->only('name', 'price');
        $data['description'] = HtmlSanitizer::sanitize($request->input('description'));

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
