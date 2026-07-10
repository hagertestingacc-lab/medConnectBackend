<?php
namespace App\Responces;



class DoctorResponces
{
    public function successResponseMessage(string $message,$statusCode)
{

    return response()->json([
        'success'=>true,
        'message' => $message,
    ], $statusCode);
}
    public function successResponseMessageToken(string $message,$token, $statusCode)
{

    return response()->json([
        'message' => $message,
        'token'=>$token,
    ], $statusCode);
}
    public function successResponseFullData( string $message ,  $data,$token,$statusCode)
{

    return response()->json([
        'message' => $message ,
        'data' => $data,
        'token'=>$token
    ], $statusCode);
}

public function errorResponseData( $message,$data, int $statusCode)
{
    return response()->json([
                'success'=>false,
        'error' => $message,
        'data'=>$data
    ], $statusCode);
}
public function errorResponseMessage( $message, int $statusCode)
{
    return response()->json([
        'success'=>false,
        'error' => $message,
    ], $statusCode);
}

}
