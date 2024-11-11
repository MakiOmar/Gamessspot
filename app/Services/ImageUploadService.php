<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageUploadService
{
    /**
     * Handle image upload and return the saved path.
     *
     * @param \Illuminate\Http\UploadedFile $imageFile
     * @param string $directory
     * @return string
     */
    public function upload(UploadedFile $imageFile, string $directory): string
    {
        $originalName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $imageFile->getClientOriginalExtension();

        // Generate a unique filename with timestamp
        $imageName = $originalName . '_' . time() . '.' . $extension;

        // Move the file to the specified directory
        $imageFile->move(public_path("assets/uploads/{$directory}"), $imageName);

        // Return the relative path to be stored in the database
        return "assets/uploads/{$directory}/" . $imageName;
    }
}
