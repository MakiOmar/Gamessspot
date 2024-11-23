<?php

namespace App\Http\Controllers;

use App\Models\CardCategory;
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
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categories = CardCategory::all();
        return view('manager.card-categories', compact('categories'));
    }
    public function sell()
    {
        $categories = CardCategory::whereHas('cards', function ($query) {
            $query->where('status', true); // Only include categories with active cards
        })
        ->with(['cards' => function ($query) {
            $query->where('status', true); // Load only active cards
        }])
        ->get();
        $storeProfiles = StoresProfile::all();

        return view('manager.sell-cards', compact('categories', 'storeProfiles'));
    }


    /**
     * Show the form for creating a new card category.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('card_categories.create');
    }
    /**
     * Store a newly created card category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  $id
     * @return mixed
     */
    protected function validatation(Request &$request, $id = null)
    {
        $name_validation = 'required|string|unique:card_categories,name';
        $data = $request;
        return $request->validate([
            'name' => $id ? $name_validation . ',' . $id : $name_validation,
            'price' => 'required|numeric|min:0',
            'poster_image' => 'nullable|image|mimes:webp,jpeg,png,jpg,gif|max:2048',
        ]);
    }
    /**
     * Store a newly created card category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validatation($request);

        $data = $request->only('name', 'price');

        // Use ImageUploadService for poster image upload
        if ($request->hasFile('poster_image')) {
            $data['poster_image'] = $this->imageUploadService->upload($request->file('poster_image'), 'posters');
        }

        $category = CardCategory::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Card Category created successfully.',
            'data' => $category
        ]);
    }



    /**
     * Show the specified card category.
     *
     * @param  \App\Models\CardCategory  $cardCategory
     * @return \Illuminate\Http\Response
     */
    public function show(CardCategory $cardCategory)
    {
        return view('card_categories.show', compact('cardCategory'));
    }

    /**
     * Show the form for editing the specified card category.
     *
     * @param  \App\Models\CardCategory  $cardCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(CardCategory $cardCategory)
    {
        return response()->json([
            'id' => $cardCategory->id,
            'name' => $cardCategory->name,
            'price' => $cardCategory->price,
            'poster_image' => $cardCategory->poster_image,
        ]);
    }


    /**
     * Update the specified card category in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CardCategory  $cardCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, CardCategory $cardCategory)
    {
        $this->validatation($request, $cardCategory->id);

        $data = $request->only('name', 'price');

        // Use ImageUploadService for poster image upload
        if ($request->hasFile('poster_image')) {
            // Optionally delete the old image if it exists
            if ($cardCategory->poster_image && file_exists(public_path($cardCategory->poster_image))) {
                unlink(public_path($cardCategory->poster_image));
            }

            // Upload the new poster image using ImageUploadService
            $data['poster_image'] = $this->imageUploadService->upload($request->file('poster_image'), 'posters');
        }

        $cardCategory->update($data);

        return response()->json([
        'success' => true,
        'message' => 'Card Category updated successfully.',
        'data' => $cardCategory
        ]);
    }



    /**
     * Remove the specified card category from storage.
     *
     * @param  \App\Models\CardCategory  $cardCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(CardCategory $cardCategory)
    {
        try {
            $cardCategory->delete();

            return response()->json([
            'success' => true,
            'message' => 'Card Category deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
            'success' => false,
            'message' => 'Failed to delete Card Category.',
            'error' => $e->getMessage()
            ], 500);
        }
    }
}
