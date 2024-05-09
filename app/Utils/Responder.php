<?php

namespace App\Utils;

use JsonSerializable;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

class Responder
{
    /**
     * Return a new JSON response with mixed(object|JsonSerializable) data
     *
     * @param int $status
     * @param mixed $data
     * @param string|null $message
     * @return Illuminate\Http\JsonResponse
     */
    public static function send(
        int $status,
        $data = [],
        string $message = null
    ): JsonResponse {
        switch(true){
            case ($data instanceOf LengthAwarePaginator) :
                return self::sendLengthAwarePaginatorData($status, $data, $message);
            break;
            case ($data instanceOf ResourceCollection && $data->resource instanceOf LengthAwarePaginator) :
                return self::sendResourceCollectionPaginatorData($status, $data, $message);
            break;
            default:
            return self::sendUnpaginatedData($status, $data, $message);
        }
    }


    /**
     * Return a new JSON response with paginated data
     *
     * @param int $status
     * @param Illuminate\Pagination\LengthAwarePaginator $data
     * @param string|null $message
     * @return Illuminate\Http\JsonResponse
     */
   public static function sendLengthAwarePaginatorData(
    int $status,
    LengthAwarePaginator $data,
    string $message = null
   ): JsonResponse {
    $data = $data->toArray();
    $response = ['status' => $status,
                'data' => $data['data'],
                'meta' => collect($data)->except('data'),
                "message" => ucfirst($message),
                ];
    return response()->json($response, $status);
   }

   public static function sendResourceCollectionPaginatorData(
    int $status,
    $data,
    string $message = null
   ): JsonResponse {
    $data->additional([
        'status' => $status,
        "message" => ucfirst($message)
    ]);
    return $data->response()->setStatusCode($status);
   }

    /**
     * Return a new JSON response with mixed(object|JsonSerializable) data
     *
     * @param int $status
     * @param mixed $data
     * @param string|null $message
     * @return Illuminate\Http\JsonResponse
     */
    public static function sendUnpaginatedData(
        int $status,
        $data = [],
        string $message = null
    ): JsonResponse {
        $response = [
            'status' => $status,
            'data' => $data,
            "message" => ucfirst($message)
        ];
        return response()->json($response, $status);
    }

    /**
     * Return a new JSON response with error string
     *
     * @param int $status
     * @param string $error
     * @param string|null $message
     * @return Illuminate\Http\JsonResponse
     */
    public static function sendError(
        int $status,
        string $error,
        string $message = null
    ): JsonResponse {
        $response = [
            'status' => $status,
            'error' => $error,
            "message" => ucfirst($message)
        ];
        return response()->json($response, $status);
    }

    /**
     * Return a new JSON response with errors array
     *
     * @param int $status
     * @param \JsonSerializable $erorrs
     * @param string|null $message
     * @return Illuminate\Http\JsonResponse
     */
    public static function sendErrors(
        int $status,
        JsonSerializable $errors,
        string $message = null
    ): JsonResponse {
        $response = [
            'status' => $status,
            "errors" => $errors,
            "message" => ucfirst($message)
        ];
        return response()->json($response, $status);
    }
}
