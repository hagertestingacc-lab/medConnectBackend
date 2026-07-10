<?php

namespace App\Http\Controllers\api;

use App\Exceptions\CustomExceptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\showParams;
use App\Models\Category;
use App\Services\Cloudinary;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    public function create(Request $request)
    {
        /*     echo "create";
 */
        $validate = $request->validate([
            "name" => "required|string|min:3|unique:category,name",
            "description" => "required|string|min:10",
            "image" => "required|image",
            "is_active" => "boolean"
        ]);
        $image = null;
        try {

            $image = $this->uploadImage($request->file("image"));



            Category::create([
                "name" => $validate["name"],
                "description" => $validate["description"],
                "image" => $image["data"]["url"],
                "cloudinary_image" => $image["data"]["public_id"],
                "is_active" => $validate["is_active"]
            ]);


            return  response()->json([
                "success" => true,
                "message" => "Category created successfully",
            ], 201);
        } catch (\Exception $e) {

            if (!empty($image)) {
                $Cloudinary = new Cloudinary();
                $Cloudinary->destroy($image["data"]["public_id"]);
            }

            return  response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $validate = $request->validate([
            "name" => "sometimes|string|min:3|unique:category,name",
            "description" => "sometimes|string|min:10",
            "is_active" => "sometimes|boolean",
            "image" => "sometimes|image",
        ]);
        try {
            $category = Category::find($id);

            if (!$category)
                response()->json([
                    "success" => false,
                    "message" => "Category not found",
                ], 404);


            $updateData = $validate;
            /* echo "validation";
 */
            print_r($validate);

            if ($request->hasFile('image')) {
                $image = $this->uploadImage(
                    $request->file('image'),
                    $category->cloudinary_image
                );
                $updateData['image'] = $image['data']['url'];
                $updateData['cloudinary_image'] = $image['data']['public_id'];
            }

            $category->update($updateData);

            return  response()->json([
                "success" => true,
                "message" => "Category updated successfully",
            ], 200);
        } catch (\Exception $e) {
            return  response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }

    public function delete(Request $request, $id)
    {
        try {

            $category = Category::find($id);

            if (!$category)
                response()->json([
                    "success" => false,
                    "message" => "Category not found",
                ], 404);
            if ($category->cloudinary_image) {

                $Cloudinary = new Cloudinary();
                $Cloudinary->destroy($category->cloudinary_image);
            }
            $category->delete();


            return  response()->json([
                "success" => true,
                "message" => "Category deleted successfully",
            ], 200);
        } catch (\Exception $e) {
            return  response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }
    public function showForDoctor(Request $request, showParams $params)
    {

        $filter_by = $params->query('filter_by');        // null if not present
        $filter_value = $params->query('filter_value');
        $sort_by = $params->query('sort_by', 'id');      // default 'id'
        $sort_order = $params->query('sort_order', 'asc'); // default 'asc'
        $per_page = $params->query('per_page', 15);


        try {

            $query = Category::query();
/*             $query = $this->applyDoctorSpecialtyFilter($query, $request);
 */
            if (!(empty($filter_by) && empty($filter_value))) {
                $query->where($filter_by, $filter_value);
            }

            $category = $query->orderBy($sort_by, $sort_order)->where("is_active", 1)->paginate($per_page, ['id', 'name', 'description', 'image', "is_active"]);

            return  response()->json([
                "success" => true,
                "message" => "Categories returned successfully",
                "data" => $category->items(),
                "last_page" => $category->lastPage(),
                "per_page" => $category->perPage(),
                "total" => $category->total(),
            ], 200);
        } catch (\Exception $e) {

            return  response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }
    public function showByIdForDoctor(Request $request, $id)
    {
        try {
            $query = Category::select(['id', 'name', 'description', "image", "is_active"])->where("is_active", 1);
/*             $query = $this->applyDoctorSpecialtyFilter($query, $request);
 */            $category = $query->find($id);

            if (!$category)
                return  response()->json([
                    "success" => false,
                    "error" => "category not found",
                ], 404);

            return  response()->json([
                "success" => true,
                "message" => "Category returned successfully",
                "data" => $category,

            ], 200);
        } catch (\Exception $e) {
            return  response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }
    public function show(request $request, showParams $params)
    {

        $filter_by = $params->query('filter_by');        // null if not present
        $filter_value = $params->query('filter_value');
        $sort_by = $params->query('sort_by', 'id');      // default 'id'
        $sort_order = $params->query('sort_order', 'asc'); // default 'asc'
        $per_page = $params->query('per_page', 15);


        try {

            $query = Category::query();

            if (!(empty($filter_by) && empty($filter_value))) {
                $query->where($filter_by, $filter_value);
            }

            $category = $query->orderBy($sort_by, $sort_order)->paginate($per_page, ['id', 'name', 'description', 'image', "is_active"]);

            return  response()->json([
                "success" => true,
                "message" => "Categories returned successfully",
                "data" => $category->items(),
                "last_page" => $category->lastPage(),
                "per_page" => $category->perPage(),
                "total" => $category->total(),
            ], 200);
        } catch (\Exception $e) {

            return  response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }
    public function showById($id)
    {
        try {
            $category = Category::select(['id', 'name', 'description', "image", "is_active"])->find($id);

            if (!$category)
                return  response()->json([
                    "success" => false,
                    "error" => "category does not exists",
                ], 400);

            return  response()->json([
                "success" => true,
                "message" => "Category returned successfully",
                "data" => $category,

            ], 200);
        } catch (\Exception $e) {
            return  response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }


    private function applyDoctorSpecialtyFilter($query, Request $request)
    {
        $user = $request->user();
        $specialty = $user?->doctor?->doctorLicense?->specialty;


        if (blank($specialty)) {
            return $query;
        }

        $specialty = trim($specialty);
        if ($specialty === '') {
            return $query;
        }

        return $query->where(function ($q) use ($specialty) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($specialty) . '%'])
                ->orWhereRaw('LOWER(name) = ?', [strtolower($specialty)]);
        });
    }

    private function uploadImage($file, $public_id = null)
    {
        /*     echo "upload";
 */


        if (!$file->isValid())
            return   response()->json([
                "success" => false,
                "error" => "upload a valid category image"

            ], status: 422);
        $Cloudinary = new Cloudinary();
        /* echo $file->getRealPath();
 */
        $result =  $Cloudinary->upload($file->getRealPath(), [
            'public_id' => $public_id ?? 'category' . uniqid(true),
            'use_filename' => true,
            'overwrite' => true
        ]);

        if (!$result["success"])
            throw new CustomExceptions("could not upload image", 500);

        return $result;
    }
}