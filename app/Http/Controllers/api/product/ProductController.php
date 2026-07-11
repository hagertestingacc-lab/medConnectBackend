<?php

namespace App\Http\Controllers\api\product;

use App\Http\Controllers\Controller;
use App\Http\Requests\productRequest;
use App\Http\Requests\productUpdateRequest;
use App\Http\Requests\showParams;
use App\Models\Category;
use App\Models\Chat\Conversation;
use App\Models\ProductPart\Product;
use App\Models\SupplierPart\Supplier;
use App\Notifications\customNotification;
use App\Services\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class ProductController extends Controller
{    //by supplier
    use AuthorizesRequests;

    public function create(productRequest $request)
    {

        try {
            $user = $request->user();
            $validated = $request->validated();
            $productData = collect($validated)->except("images")->all();
            $productData["supplier_id"] = $user->supplier->id;
            $productData["is_rentable"] = isset($validated["price_daily"]);
            $product = Product::create($productData);
            if ($productData["is_rentable"]) {
                $product->rentalDetails()->create([
                    "price_daily" => $validated["price_daily"],
                    "minimum_rental_days" => $validated["minimum_rental_days"],
                    "maximum_rental_days" => $validated["maximum_rental_days"],
                    "available_units" => $validated["available_units"],
                    "stock_units" => $validated["available_units"],
                    "preparation_duration" => $validated["preparation_duration"],

                ]);
            }


            $errors = $this->uploadImage($request->file("images"), $product);
            if (\count($errors) > 0)
                response()->json([
                    "success" => false,
                    "error" => "failed to upload image",
                    "images" => $errors

                ], 422);

            return  response()->json([
                "success" => true,
                "message" => "Product created successfully ,Now admin review it  ",
            ], 201);
        } catch (\Exception $e) {

            return   response()->json([
                "success" => false,
                "error" => "An issue occured , please try again" . $e->getMessage(),

            ], 500);
        }
    }
    //by supplier

    public function delete(Product $product)
    {
        $this->authorize("productSupplier", $product);
        try {
            $this->deleteImage($product);
            $product->delete();

            return response()->json([
                "success" => true,
                "message" => "product deleted successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }

    //supplier
    public function update(productUpdateRequest $request, Product $product)
    {
        $rentDetails = [
            'price_daily',
            'min_days',
            'max_days',
            'available_units',
            'preparation_duration'
        ];
        try {
            $validated = $request->validated();
            $status = $product["status"];
            if ($status != "create_accepted" &&  $status != "edit_accepted")
                return response()->json([
                    "success" => false,
                    "error" => "Sorry u can not update the product currently, the product status is " . $status,
                ], 422);

            if ($request->hasFile("images"))
                $this->uploadImage($request->file("images"), $product);


            if ($product["is_rentable"] && array_intersect($rentDetails, array_keys($validated))) {
                if (isset($validated["available_units"])) {
                    $validated["stock_units"] = $validated["available_units"];
                    $rental = $product->rentalDetails;
                    $rentedCount = $rental->stock_units - $rental->available_units;
                    $validated["available_units"] = $validated["available_units"] - $rentedCount;
                    $product->rentalDetails()->update(
                        collect($validated)->only($rentDetails)->toArray()
                    );
                }
            } else if (isset($validated['price_daily'])) {
                $product->rentalDetails()->create(
                    collect($validated)->only($rentDetails)->toArray()
                );
                $product["is_rentable"] = true;
            }

            if (isset($validated["is_rentable"]))
                $product["is_rentable"] = $validated["is_rentable"];


            // Check if stock is being updated and current stock is zero
            if (isset($validated['stock']) && $product->stock == 0) {
                $notifications = $product->restockNotifications;
                foreach ($notifications as $notification) {
                    $notification->doctor->allUser->notifyNow(new customNotification("Product {$product->name} has been restocked."));
                }

                $product->restockNotifications()->delete();
            }
            $message = "product updates successfully";
            if (isset($validated['name']) || isset($validated['description'])) {
                $validated["status"] = "edit_pending";
                $message = "Your request under review";
            }

            $product->update($validated);


            // Update equipment list items availability
            if ((isset($validated['stock']) && $validated['stock'] == 0) || (isset($validated['is_archive']) && $validated['is_archive'])) {
                \App\Models\EquipmentListItem::where('product_id', $product->id)->update(['is_ava' => false]);
            }





            return response()->json([
                "success" => true,
                "message" => $message,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured" . $e->getMessage(),
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

        $request->validate([
            "is_recommended" => "boolean"
        ]);
        try {
            $query = Product::query();

            if ($request->filled("is_recommended") && $request["is_recommended"])
                $query = $this->applyDoctorSpecialtyFilter($query, $request)
                    ->withAvg('reviews', 'rating')
                    ->orderBy('reviews_avg_rating', 'desc');


            if (!(empty($filter_by) && empty($filter_value))) {
                $query->where($filter_by, $filter_value);
            }
            /*     else
            {
           $query = $this->applyDoctorSpecialtyFilter($query, $request);

            } */
            $products = $query->whereHas(
                "category",
                function ($query) {
                    $query->Active();
                }
            )
                ->with([
                    "image",
                    "rentalDetails",
                    "reviews",
                    "supplier" => function ($query) {
                        $query->select("id", "user_table_id", "company_name", "company_image_url");
                    },
                    "supplier.allUser" => function ($query) {
                        $query->select("id");
                    },
                ])
                ->where("is_archive", false)
                ->whereIn("status", ["create_accepted", "edit_accepted"])
                ->orderBy($sort_by, $sort_order)
                ->paginate($per_page);

            // Build plain arrays instead of mutating relations on the models
            $items = $products->getCollection()->map(function ($product) use ($request) {
                $supplier = $product->supplier;

                $productArray = $product->toArray();
                $productArray["supplier"] = array_merge($supplier?->toArray() ?? [], [
                    "allUserId" => $supplier?->allUser?->id,
                    "conversationId" => $this->resolveConversationId($supplier, $request),
                ]);

                return $productArray;
            });

            return response()->json([
                "success" => true,
                "message" => "products retrieved successfully",
                "data" => $items,
                "last_page" => $products->lastPage(),
                "per_page" => $products->perPage(),
                "total" => $products->total(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }
    //Supplier product by id on doctor side
    public function showByIdForDoctor(Request $request, $id)
    {
        try {
            $query = Product::query()->whereHas("category", function ($query) {
                $query->Active();
            })
                ->with([
                    "image",
                    "rentalDetails",
                    "reviews",
                    "supplier" => function ($query) {
                        $query->select("id", "user_table_id", "company_name", "company_image_url");
                    },
                    "supplier.allUser" => function ($query) {
                        $query->select("id");
                    }
                ]);

            $query = $this->applyDoctorSpecialtyFilter($query, $request);

            $product = $query->where("is_archive", false)
                ->whereIn("status", ["create_accepted", "edit_accepted"])
                ->where("id", $id)->first();

            if (!$product)
                return response()->json([
                    "success" => false,
                    "message" => "Product not found",
                ], 404);

            $supplier = $product->supplier;

            // Convert to array FIRST, then mutate the array
            $productArray = $product->toArray();
            $productArray["supplier"] = array_merge($supplier?->toArray() ?? [], [
                "allUserId" => $supplier?->allUser?->id,
                "conversationId" => $this->resolveConversationId($supplier, $request),
            ]);

            return response()->json([
                "success" => true,
                "message" => "product retrieved successfully",
                "data" => $productArray
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }


    //show for suppliers & admin
    public function show(showParams $params)
    {
        $filter_by = $params->query('filter_by');        // null if not present
        $filter_value = $params->query('filter_value');
        $sort_by = $params->query('sort_by', 'id');      // default 'id'
        $sort_order = $params->query('sort_order', 'asc'); // default 'asc'
        $per_page = $params->query('per_page', 15);
        try {
            $query = Product::query();
            if (!(empty($filter_by) && empty($filter_value))) {
                $query->where($filter_by, $filter_value);
            }
            $products = $query->with("image", "rentalDetails", "reviews")->orderBy($sort_by, $sort_order)->paginate($per_page);


            return response()->json([
                "success" => true,
                "message" => "products retrived successfully",
                "data" => $products->items(),
                "last_page" => $products->lastPage(),
                "per_page" => $products->perPage(),
                "total" => $products->total(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }

    //show for suppliers & admin

    public function showById($id)
    {

        try {

            $product = Product::with("image", "reviews", "rentalDetails")->where("id", $id)->first();

            if (!$product)
                return response()->json([
                    "success" => false,
                    "message" => "Product not found",
                ], 404);

            return response()->json([
                "success" => true,
                "message" => "product reetrived successfully",
                "data" => $product
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }

    //show for supplier's own products
    public function showForSupplier(Request $request, showParams $params)
    {
        $user = $request->user();
        if (!$user || !$user->supplier) {
            return response()->json([
                "success" => false,
                "message" => "Unauthorized or supplier not found",
            ], 401);
        }

        $filter_by = $params->query('filter_by');        // null if not present
        $filter_value = $params->query('filter_value');
        $sort_by = $params->query('sort_by', 'id');      // default 'id'
        $sort_order = $params->query('sort_order', 'asc'); // default 'asc'
        $per_page = $params->query('per_page', 15);
        try {
            $query = Product::where('supplier_id', $user->supplier->id);
            if (!(empty($filter_by) && empty($filter_value))) {
                $query->where($filter_by, $filter_value);
            }
            $products = $query->with("image", "rentalDetails", "reviews")->orderBy($sort_by, $sort_order)->paginate($per_page);


            return response()->json([
                "success" => true,
                "message" => "your products retrived successfully",
                "data" => $products->items(),
                "last_page" => $products->lastPage(),
                "per_page" => $products->perPage(),
                "total" => $products->total(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }

    //show for supplier's own product by id
    public function showByIdForSupplier(Request $request, $id)
    {
        $user = $request->user();
        if (!$user || !$user->supplier) {
            return response()->json([
                "success" => false,
                "message" => "Unauthorized or supplier not found",
            ], 401);
        }

        try {

            $product = Product::where('supplier_id', $user->supplier->id)->where("id", $id)->with("image", "reviews", "rentalDetails")->first();

            if (!$product)
                return response()->json([
                    "success" => false,
                    "message" => "Product not found",
                ], 404);

            return response()->json([
                "success" => true,
                "message" => "your product reetrived successfully",
                "data" => $product
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }


    //Supplier products + profile on doctor side
    public function showBySupplierProfile(Request $request, showParams $params, Supplier $supplier)
    {
        $filter_by = $params->query('filter_by');
        $filter_value = $params->query('filter_value');
        $sort_by = $params->query('sort_by', 'id');
        $sort_order = $params->query('sort_order', 'asc');
        $per_page = $params->query('per_page', 15);

        try {


            $query = $supplier->product()->with(["image", "rentalDetails", "reviews"]);
            /*             $query = $this->applyDoctorSpecialtyFilter($query, $request);
 */
            if (!(empty($filter_by) && empty($filter_value))) {
                $query->where($filter_by, $filter_value);
            }

            $products = $query->whereHas(
                "category",
                function ($query) {
                    $query->Active();
                }
            )
                ->where("is_archive", false)
                ->whereIn("status", ["create_accepted", "edit_accepted"])
                ->orderBy($sort_by, $sort_order)
                ->paginate($per_page);

            return response()->json([
                "success" => true,
                "message" => "supplier products retrieved successfully",
                "supplier" => [
                    "id" => $supplier->id,
                    "allUser_id" => $supplier->allUser?->id,
                    "company_name" => $supplier->company_name,
                    "company_image_url" => $supplier->company_image_url,
                    "certificate_name" => $supplier->certificate_name,
                    "conversation_id" => $this->resolveConversationId($supplier, $request)
                ],
                "data" => $products->items(),
                "last_page" => $products->lastPage(),
                "per_page" => $products->perPage(),
                "total" => $products->total(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }
    /*   public function showBySupplierProfile(showParams $params, Supplier $supplier)
    {
        $filter_by = $params->query('filter_by');        // null if not present
        $filter_value = $params->query('filter_value');
        $sort_by = $params->query('sort_by', 'id');      // default 'id'
        $sort_order = $params->query('sort_order', 'asc'); // default 'asc'
        $per_page = $params->query('per_page', 15);
        try {
            $query = $supplier->product()->with(["image", "rentalDetails", "supplier:id,company_name,company_image_url,certificate_name,certificate_image,governorate,phone", "reviews"]);

            if (!(empty($filter_by) && empty($filter_value))) {
                $query->where($filter_by, $filter_value);
            }
            $products = $query->whereHas(
                "category",
                function ($query) {
                    $query->Active();
                }
            )
                ->where("is_archive", false)
                ->whereIn("status", ["create_accepted", "edit_accepted"])
                ->orderBy($sort_by, $sort_order)
                ->paginate($per_page);


            return response()->json([
                "success" => true,
                "message" => "supplier products reetrived successfully",
                "data" => $products->items(),
                "last_page" => $products->lastPage(),
                "per_page" => $products->perPage(),
                "total" => $products->total(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }
 */



    //By admin
    public function updateStatus(request $request, $id)
    {
        $validated = $request->validate(
            [
                "status" => "required|in:create_pending,create_accepted,create_rejected,edit_pending,edit_accepted,edit_rejected"
            ],
            [
                "status.in" => "The avaliable status is create_accepted create_rejected edit_pending edit_accepted edit_rejected"
            ]
        );

        try {

            $product = Product::where("id", $id)->update(["status" => $validated["status"]]);
            if (!$product)
                return response()->json([
                    "success" => false,
                    "message" => "Product not found"
                ], 404);


            return response()->json([
                "success" => true,
                "message" => "Product updated successfully"
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }



    //By admin - supplier

    public function updateArchive(request $request, Product $product)
    {
        $validated = $request->validate([
            "is_archive" => "required|boolean"
        ]);

        try {

            $product = $product->update(["is_archive" => $validated["is_archive"]]);
            if (!$product)
                return response()->json([
                    "success" => false,
                    "message" => "Product not found"
                ], 404);


            return response()->json([
                "success" => true,
                "message" => "Product updated successfully"
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured" . $e->getMessage(),
            ], 500);
        }
    }



    //Search
    public function search(Request $request)
    {
        try {
            $query = Product::query()->whereHas(
                "category",
                function ($query) {
                    $query->Active();
                }
            );
/*             $query = $this->applyDoctorSpecialtyFilter($query, $request);
 */            $query = $query->with("image", "rentalDetails", "reviews")
                ->where("is_archive", false)
                ->whereIn("status", ["create_accepted", "edit_accepted"]);

            if ($request->filled('search')) {
                $query->where('name', 'LIKE', '%' . $request->search . '%');
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            // Filter by category name (partial match on related category)
            if ($request->filled('category_name')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('name', 'LIKE', '%' . $request->category_name . '%');
                });
            }

            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->filled('is_rentable')) {
                $query->where('is_rentable', filter_var($request->is_rentable, FILTER_VALIDATE_BOOLEAN));
            }

            $products = $query->paginate(15);
            return response()->json([
                "success" => true,
                "message" => "products retured successfully",
                "data" => $products->items(),
                "last_page" => $products->lastPage(),
                "per_page" => $products->perPage(),
                "total" => $products->total(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "error" => "An issue occured" . $e->getMessage(),
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

        return $query->whereHas('category', function ($q) use ($specialty) {
            $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($specialty) . '%'])
                ->orWhereRaw('LOWER(name) = ?', [strtolower($specialty)]);
        });
    }

    /*    private function getCategoryIdByName($name)
    {
        if (blank($name)) {
            return null;
        }

        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $category = Category::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($name) . '%'])
            ->orWhereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        return $category?->id ?? null;
    } */

    private function uploadImage($files, $product)
    {


        $coudinary = new  Cloudinary();
        $error = [];
        foreach ($files as $file) {
            $image =   $coudinary->upload($file->getRealPath(), [
                'public_id' => 'product' . uniqid(true),
                'use_filename' => true,
                'overwrite' => true
            ]);
            if ($image["success"]) {
                $product->image()->create([
                    "image" => $image["data"]["url"],
                    "cloudinary_image_id" => $image["data"]["public_id"]
                ]);
            } else
                array_push($error, $file->getRealPath());
        }
        return $error;
    }


    private function deleteImage(Product $product)
    {
        $images = $product->image()->get("cloudinary_image_id");
        if (\count($images) == 0)
            return;


        foreach ($images as $image) {

            $Cloudinary = new Cloudinary();
            $Cloudinary->destroy($image->cloudinary_image_id);
            $image->delete();
        }
    }

    private function resolveConversationId(Supplier $supplier, Request $request): ?int
    {
        $user = $request->user();
        $user2 = $supplier->allUser;

        /*    print_r($user2->id);
 */
        if (!$user || !$user2?->id) {
            return null;
        }

        $conversation = Conversation::where(function ($query) use ($user, $user2) {
            $query->where('participant_one', $user->id)
                ->where('participant_two', $user2->id);
        })->orWhere(function ($query) use ($user, $user2) {
            $query->where('participant_one', $user2->id)
                ->where('participant_two', $user->id);
        })->first();

        return $conversation?->id ?? null;
    }
}