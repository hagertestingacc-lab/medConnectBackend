<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

    <div id="fawaterkDivId"></div>
    

    <script>
        var pluginConfig = {
            envType: "test",
            hashKey: "HASH-KEY",
            style:{
              listing:"horizontal"
            },
            version:"0",
            requestBody: {
                "cartTotal": "50",
                "currency": "EGP",
                "customer": {
                    "first_name": "test",
                    "last_name": "fawaterk",
                    "email": "test@fawaterk.com",
                    "phone": "0123456789",
                    "address": "test address"
                },
                "redirectionUrls": {
                    "successUrl": "https://dev.fawaterk.com/success",
                    "failUrl": "https://dev.fawaterk.com/fail",
                    "pendingUrl": "https://dev.fawaterk.com/pending"
                },
                "cartItems": [{
                        "name": "this is test oop 112252",
                        "price": "25",
                        "quantity": "1"
                    },
                    {
                        "name": "this is test oop 112252",
                        "price": "25",
                        "quantity": "1"
                    }
                ],
                "payLoad": {
                  "custom_field1":"xyz",
                  "custom_field2":"xyz2"
                }
            }
        };
    </script>

    <script src="https://app.fawaterk.com/fawaterkPlugin/fawaterkPlugin.min.js"></script>
</body>
</html>