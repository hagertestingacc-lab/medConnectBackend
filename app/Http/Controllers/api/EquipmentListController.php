<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EquipmentListRequest;
use App\Http\Requests\EquipmentListItemRequest;
use App\Models\EquipmentList;
use App\Models\EquipmentListItem;
use App\Models\ProductPart\Product;
use Illuminate\Http\Request;

class EquipmentListController extends Controller
{
    public function index()
    {
        $doctor = request()->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        try {
            $lists = EquipmentList::where('doctor_id', $doctor->id)->get();

            return response()->json([
                'success' => true,
                'data' => $lists->map(function ($list) {
                    return [
                        'id' => $list->id,
                        'list_name' => $list->list_name,
                        'is_default' => $list->is_default,
                        'created_at' => $list->created_at,
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(EquipmentListRequest $request)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        try {
            $data = $request->validated();
            $data['doctor_id'] = $doctor->id;

            // Check if list_name already exists for this doctor
            if (EquipmentList::where('doctor_id', $doctor->id)->where('list_name', $data['list_name'])->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => 'List name already exists.',
                ], 422);
            }

            $list = EquipmentList::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Equipment list created.',
                'data' => $list,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(EquipmentList $list)
    {
        $doctor = request()->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        if ($list->doctor_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized.',
            ], 403);
        }

        try {
            $list->load('items.product');

            $items = [];
            foreach ($list->items as $item) {
                if (! $item->product || $item->product->is_archive) {
                    continue;
                }

                $items[] = [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'is_ava' => $item->product->stock > 0,
                    'added_at' => $item->added_at,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $list->id,
                    'list_name' => $list->list_name,
                    'is_default' => $list->is_default,
                    'created_at' => $list->created_at,
                    'items' => $items,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(EquipmentListRequest $request, EquipmentList $list)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        if ($list->doctor_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized.',
            ], 403);
        }

        try {
            $data = $request->validated();

            // Check if list_name already exists for this doctor, excluding current
            if (EquipmentList::where('doctor_id', $doctor->id)->where('list_name', $data['list_name'])->where('id', '!=', $list->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => 'List name already exists.',
                ], 422);
            }

            $list->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Equipment list updated.',
                'data' => $list,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(EquipmentList $list)
    {
        $doctor = request()->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        if ($list->doctor_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized.',
            ], 403);
        }

        try {
            $list->delete();

            return response()->json([
                'success' => true,
                'message' => 'Equipment list deleted.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function allListsWithItems()
    {
        $doctor = request()->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        try {
            $lists = EquipmentList::where('doctor_id', $doctor->id)->with('items.product')->get();

            $data = [];
            foreach ($lists as $list) {
                $items = [];
                foreach ($list->items as $item) {
                    if (! $item->product || $item->product->is_archive) {
                        continue;
                    }

                    if ($item->product->status!="create_accepted" && $item->product->status!="edit_accepted") {
                        continue;
                    }

                    $items[] = [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product->name,
                        'is_ava' => $item->product->stock > 0,
                        'added_at' => $item->added_at,
                    ];
                }

                $data[] = [
                    'id' => $list->id,
                    'list_name' => $list->list_name,
                    'is_default' => $list->is_default,
                    'created_at' => $list->created_at,
                    'items' => $items,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addItem(EquipmentListItemRequest $request, EquipmentList $list)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        if ($list->doctor_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized.',
            ], 403);
        }

        try {
            $data = $request->validated();
            $data['list_id'] = $list->id;

            // Check if product exists
            $product = Product::find($data['product_id']);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product not found.',
                ], 404);
            }

            if ($product->is_archive) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product is not available now.',
                ], 422);
            }

            // Check if already in list
            if (EquipmentListItem::where('list_id', $list->id)->where('product_id', $data['product_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Product already in the list.',
                ], 422);
            }

            $item = EquipmentListItem::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Item added to list.',
                'data' => $item,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function removeItem(EquipmentList $list, $productId)
    {
        $doctor = request()->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        if ($list->doctor_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized.',
            ], 403);
        }

        try {
            $item = EquipmentListItem::where('list_id', $list->id)->where('product_id', $productId)->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'error' => 'Item not found in the list.',
                ], 404);
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from list.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function isInList(EquipmentList $list, $productId)
    {
        $doctor = request()->user()->doctor;

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => 'User does not have a doctor profile.',
            ], 403);
        }

        if ($list->doctor_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized.',
            ], 403);
        }

        try {
            $exists = EquipmentListItem::where('list_id', $list->id)->where('product_id', $productId)->exists();

            return response()->json([
                'success' => true,
                'in_list' => $exists,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}