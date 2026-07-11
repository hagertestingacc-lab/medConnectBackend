<?php

use App\Http\Controllers\api\AdminController;
use App\Http\Controllers\api\AllUserController;
use App\Http\Controllers\api\CategoryController;
use App\Http\Controllers\api\Auth\DoctorAuthController;
use App\Http\Controllers\api\Auth\LogoutController;
use App\Http\Controllers\api\ChatController;
use App\Http\Controllers\api\customRequest\customRequestController;
use App\Http\Controllers\api\customRequest\OfferRequestController;
use App\Http\Controllers\api\DoctorLicenseController;
use App\Http\Controllers\api\Auth\SupplierAuthController;
use App\Http\Controllers\api\checkout\CartController;
use App\Http\Controllers\api\checkout\OrderController;
use App\Http\Controllers\api\Auth\PasswordsController;
use App\Http\Controllers\api\product\ProductController;
use App\Http\Controllers\api\product\ProductImage;
use App\Http\Controllers\api\product\ReviewController;
use App\Http\Controllers\api\RestockNotificationController;
use App\Http\Controllers\api\SupplierController;
use App\Http\Controllers\api\VerificationDoctorController;
use App\Http\Controllers\api\checkout\PaymentController;
use App\Http\Controllers\api\EquipmentListController;
use App\Http\Requests\customRequest;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;





// Send verification email
Route::get('/email/verify/{id}/{hash}', [VerificationDoctorController::class, 'verify'])
    ->name('verification.verify') // important: the notification uses this name
    ->middleware(['signed']); // ensures the URL hasn't been tampered with


//resending
Route::post('/email/resendExpired', [VerificationDoctorController::class, 'resendExpired'])
    ->middleware(['throttle:6,1']) // limit to 6 attempts per minute
    ->name('verification.send');


Route::prefix("v1")->group(function () {



    Route::middleware('auth:sanctum')->group(function () {
        Route::get("/show", [DoctorAuthController::class, 'show']);
    });
});





// Doctor
Route::prefix("v1/doctor")->group(function () {
    Route::post("/register", [DoctorAuthController::class, 'register']);
    Route::post("/login", [DoctorAuthController::class, 'login']);
    Route::get("/licenses/{page}/{per_page}", [DoctorLicenseController::class, 'getLicenses']);

    Route::middleware(['auth:sanctum', 'doctorAuth'])->group(function () {
        Route::get("/profile", [AllUserController::class, 'getdoctor']);
        Route::post("/update/address", [AllUserController::class, 'updateDoctorAddress']);
        Route::post("/update/image", [AllUserController::class, 'updateDoctorImage']);
        Route::delete("/delete/image", [AllUserController::class, 'deleteDoctorImage']);
    });
});


//Supplier
Route::prefix("v1/supplier")->group(function () {
    Route::post("/register", [SupplierAuthController::class, 'register']);
    Route::post("/login", [SupplierAuthController::class, 'login']);

    //show the supplier profile on doctor side
    Route::get("/profile/{id}", [SupplierController::class, "showByIDForDoctor"]);

    Route::middleware(['auth:sanctum', 'supplierAuth'])->group(function () {
        Route::get('/account', [SupplierController::class, 'getProfile']);
    });

    Route::middleware(['auth:sanctum', "adminSupplierAuth"])->group(function () {
        //show the supplier profile on admin-supplier side

        Route::get("/show", [SupplierController::class, "show"]);
        Route::get("/show/id", [SupplierController::class, "showById"]);
    });
    //supplier status Controller
    Route::post("/status/{id}", [SupplierController::class, "updateStatus"])->middleware(["adminAuth", "auth:sanctum"]);
});

//Admin
Route::prefix("v1/admin")->group(function () {

    Route::post("/register", [AdminController::class, 'register']);
    Route::post("/login", [AdminController::class, 'login']);

    Route::middleware("auth:sanctum")->group(function () {});
});

//User
Route::prefix("v1/user")->group(function () {
    Route::get("/{page}/{per_page}/{filterByRole?}", [AllUserController::class, 'getAllUser']);

    //get all users
    Route::middleware('auth:sanctum')->group(function () {});
});

//Category
Route::prefix("v1/category")->group(function () {
    //show all
    Route::get("/doctor/show", [CategoryController::class, 'showForDoctor'])->middleware(['auth:sanctum', "doctorAuth"]);
    //show by id
    Route::get("/doctor/show/{id}", [CategoryController::class, 'showByIdForDoctor']);

    Route::middleware(['auth:sanctum', "adminAuth"])->group(
        function () {
            Route::post("/create", [CategoryController::class, "create"]);
            Route::post("/update/{id}", [CategoryController::class, "update"]);
            Route::delete("/delete/{id}", [CategoryController::class, "delete"]);
        }
    );

    Route::middleware(['auth:sanctum', "adminSupplierAuth"])->group(function () {
        Route::get("/show", [CategoryController::class, 'show']);
        Route::get("/show/{id}", [CategoryController::class, 'showById']);
    });
});


//Products
Route::prefix("v1/product")->group(function () {
    //takes product name , catg id , cat name (optionally for each one )

    Route::middleware("auth:sanctum","doctorAuth")->group(function(){
        Route::get("/search", [ProductController::class, 'search']);

    Route::get("/doctor/show/{id}", [ProductController::class, 'showByIdForDoctor']);
    Route::get("/doctor/show", [ProductController::class, 'showForDoctor']);
    });
    //show the supplier profile & his products on doctor side
    Route::get("/supplier-profile/show/{supplier}", [ProductController::class, 'showBySupplierProfile'])->middleware(['auth:sanctum', "doctorAuth"]);
    Route::middleware(['auth:sanctum', "supplierAuth"])->group(
        function () {
            Route::get("/show", [ProductController::class, 'showForSupplier']);
            Route::get("/show/{id}", [ProductController::class, 'showByIdForSupplier']);

            Route::post("/create", [ProductController::class, "create"]);

            Route::post("/update/{product}", [ProductController::class, "update"]);
            Route::delete("/delete/{product}", [ProductController::class, "delete"]);
            Route::delete("/image/delete/{id}", [ProductImage::class, "delete"]); //unTested

        }
    );

    Route::middleware(['auth:sanctum', "adminSupplierAuth"])->group(function () {
        Route::post("/archive/{product}", [ProductController::class, 'updateArchive']);
    });

    Route::middleware(['auth:sanctum', "adminAuth"])->group(function () {
        Route::post("/status/{product}", [ProductController::class, 'updateStatus']);
        Route::get("/admin/show", [ProductController::class, 'show']);
        Route::get("/admin/show/{id}", [ProductController::class, 'showById']);

    });
});

// Restock Notifications
Route::prefix('v1/restock-notification')->middleware(['auth:sanctum'])->group(function () {
    Route::middleware('doctorAuth')->group(function () {
        Route::post('/request/{product}', [RestockNotificationController::class, 'store']);
        Route::delete('/undo/{product}', [RestockNotificationController::class, 'undo']);
        Route::get('/is-notify/{product}', [RestockNotificationController::class, 'isNotify']);
    });

    /*  Route::middleware('supplierAuth')->group(function () {
        Route::post('/notify/{product}', [RestockNotificationController::class, 'notify']);
    }); */
});

Route::prefix('v1/cart')->middleware(['auth:sanctum', 'doctorAuth'])->group(function () {
    Route::get('/show', [CartController::class, 'show']);
    Route::post('/add/{product}', [CartController::class, 'addItem']);
    Route::post('/update/{cart}', [CartController::class, 'updateQuantity']);
    Route::delete('/delete/{cart}', [CartController::class, 'deleteItem']);
    /* Route::post('/update-dates/{cart}', [CartController::class, 'updateRentalDates']);
 */
});

Route::post("v1/validateRent/{product}", [CartController::class, "validateRent"])->middleware(["auth:sanctum"]);




//Reset-Password , Logout
Route::prefix("v1")->group(function () {
    Route::post("/password/forget", [PasswordsController::class, 'forgetPassword']);
    Route::post("/otp/verify", [PasswordsController::class, 'verifyOtp']);


    Route::middleware('auth:sanctum')->group(function () {

        Route::post("/password/reset", [PasswordsController::class, 'resetPasswords'])->middleware("passwordAuth");

        Route::post("/logout", [LogoutController::class, 'logout']);
    });
});


Route::prefix("v1/customRequest")->group(function () {


    Route::middleware('auth:sanctum')->group(function () {

        Route::middleware("doctorAuth")->group(
            function () {
                Route::post("/create", [customRequestController::class, 'create']);
                Route::get("/doctor/show", [customRequestController::class, 'showBydoctor']);
                Route::post("/cancel/{customRequest}", [customRequestController::class, 'cancel']);
                Route::delete("/delete/{customRequest}", [customRequestController::class, 'delete']);
            }
        );
        Route::middleware("supplierAuth")->group(
            function () {
                Route::get("/supplier/show", [customRequestController::class, 'showForSupplier']);
            }
        );
        Route::middleware("adminAuth")->group(function () {
            Route::get("/admin/show", [customRequestController::class, 'showAll']); //unTested
        });
    });
});



Route::prefix("v1/offerRequest")->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {

        Route::middleware(["supplierAuth"])->group(function () {
            Route::post("/create/{customRequest}", [OfferRequestController::class, 'create']);
            Route::get("/supplier/show", [OfferRequestController::class, 'showForSupplier']);
            Route::get("/supplier/show/order", [OfferRequestController::class, 'showOrder']);
            Route::get("/supplier/show/order/{offerRequest}", [OfferRequestController::class, 'showOrderById']);
            Route::post("/supplier/order/status/{offerRequest}", [OfferRequestController::class, 'assignStatus']); //unTested

        });

        Route::middleware(["doctorAuth"])->group(
            function () {

                Route::get("/doctor/show/{customRequest}", [OfferRequestController::class, 'showByIdForDoctor']);
                Route::post("/doctor/response/{offerRequest}", [OfferRequestController::class, 'offerResponseForDoctor']);
            }
        );
    });
});

Route::prefix("v1/product/review")->middleware(['auth:sanctum', 'doctorAuth'])->group(function () {
    Route::get('/show/{product}', [ReviewController::class, 'index']);
    Route::post('/add/{product}', [ReviewController::class, 'add']);
    Route::delete('/delete/{review}', [ReviewController::class, 'delete']);
});

Route::prefix("v1/payment")->middleware(["auth:sanctum", "doctorAuth"])->group(function () {
    Route::get("/methods", [PaymentController::class, 'getMethods']);
    Route::post("/", [PaymentController::class, 'excutePayment']);
    Route::post("/extend-rent", [PaymentController::class, 'excuteExtendRentalPayment']);
});


Route::prefix("v1/order")->middleware(["auth:sanctum","CancelExpiredOrders"])->group(function () {

    Route::prefix("/doctor")->middleware(["auth:sanctum","doctorAuth"])->group(function () {
        Route::get("/show", [OrderController::class, 'index']);
        Route::get("/show/{order}", [OrderController::class, 'show']);
        Route::post("/cancel/{order}", [OrderController::class, 'cancel']);
        Route::post("/issue/{order}", [OrderController::class, 'assignIssue']);
    });

    Route::prefix("/supplier")->middleware("supplierAuth")->group(function () {
        Route::get("/show", [OrderController::class, 'supplierIndex']);
        Route::get("/show/{order}", [OrderController::class, 'supplierShow']);
        Route::post("/status/{order}", [OrderController::class, 'assignStatus']);
        Route::post('/return/{order}', [OrderController::class, 'returnRentalProducts']);
        Route::post('/cancel/{order}', [OrderController::class, 'cancelItems']);

    });
     Route::middleware(['adminAuth'])->group(function () {
        Route::post('/admin/status/{order}', [OrderController::class, 'adminUpdateStatus']);
    });
});
Route::post('/webhook_json', [PaymentController::class, 'webhook']);
Route::get('/payment/success', function () {
    return view('payment.success');
});
Route::get('/payment/failed', function () {
    return view('payment.failed');
});
// Protected routes (require verified email)
/* Route::get('/profile', function () {



})->middleware(['auth', 'verified']);
*//*
Route::post("/doctor/register",[AuthController::class,'register'])
->middleware(doctorLicenseChecker::class); */

Route::prefix("v1/equipment-list")->middleware(['auth:sanctum', 'doctorAuth'])->group(function () {
    Route::get('/', [EquipmentListController::class, 'index']);
    Route::post('/', [EquipmentListController::class, 'store']);
    Route::get('/all-with-items', [EquipmentListController::class, 'allListsWithItems']);
    Route::get('/{list}', [EquipmentListController::class, 'show']);
    Route::post('/{list}/update', [EquipmentListController::class, 'update']);
    Route::delete('/{list}', [EquipmentListController::class, 'destroy']);
    Route::post('/{list}/add-item', [EquipmentListController::class, 'addItem']);
    Route::delete('/{list}/remove-item/{productId}', [EquipmentListController::class, 'removeItem']);
    Route::get('/{list}/is-in-list/{productId}', [EquipmentListController::class, 'isInList']);
});


//Chat
Route::prefix("v1/conversations")->middleware('auth:sanctum')->group(function () {

    Route::get('/contacts',  [ChatController::class, 'contacts']);
    Route::get('/', [ChatController::class, 'conversations']);
    Route::get('/{conversation}/messages', [ChatController::class, 'messages']);
    Route::post('/messages',   [ChatController::class, 'sendMessage']);
    Route::patch('{id}/read',   [ChatController::class, 'markAsRead']);
});
/*


Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
$request->fulfill();
return redirect('/home');
})->middleware(['auth', 'signed'])->name('verification.verify');
*/