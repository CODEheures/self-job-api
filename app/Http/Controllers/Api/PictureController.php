<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use App\Http\Requests\PictureAdvertRequest;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PictureController extends Controller
{

    public function __construct() {
        $this->middleware('auth');
    }

    /**
     * post a picture and thumb
     * @param PictureAdvertRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function post(PictureAdvertRequest $request) {

        $alreadyUploadPictures = auth()->user()->pictureUrl;
        if (!is_null($alreadyUploadPictures) || strlen($alreadyUploadPictures)>0) {
            return response('ko', 422);
        }


        $client = new GuzzleClient();
        //1°) GET LOAD INFOS FROM PICTURE SERVICES

        //best load
        foreach (config('pictures.service.domains') as $domain) {
            $infos = $client->request('GET',
                $domain . config('pictures.service.urls.routeGetInfos'),
                [
                    'http_errors' => false,
                ]
            );
            if($infos->getStatusCode() == 200){
                $loadInfos[$domain] = json_decode($infos->getBody()->getContents(),true);
            }
        }

        try {
            $bestLoad = array_sort($loadInfos, function ($value, $key) {
                return $value['load'];
            });
            $bestLoad = array_keys($bestLoad)[0];
        } catch (\Exception $e) {
            //default load
            return response(trans('strings.view_advert_create_image_servers_not_available'),503);
            //$bestLoad = config('pictures.service.domains')[0];
        }


        $privateCsrfToken = 'selfJob_' . auth()->id();

        //2°) POST PICTURE FOR GET MD5
        $md5Response = $client->request('POST',
            $bestLoad . config('pictures.service.urls.routeGetMd5'),
            [
                'http_errors' => false,
                'multipart' => [
                    [
                        'name' => 'csrf',
                        'contents' => $privateCsrfToken],
                    [
                        'name' => 'addpicture',
                        'contents' => fopen($request->file('addpicture')->getRealPath(),'r')
                    ]
                ]
            ]
        );
        if($md5Response->getStatusCode() >= 400){ return response($md5Response->getBody(),500); }
        $md5Infos = json_decode($md5Response->getBody()->getContents());


        //3°) Process or delete
        $saveResponse = $client->request('POST',
            $bestLoad . config('pictures.service.urls.routeSavePicture'),
            [
                'http_errors' => false,
                'multipart' => [
                    [
                        'name' => 'watermark',
                        'contents' => fopen(config('pictures.watermark_path'),'r')
                    ],
                    [
                        'name' => 'csrf',
                        'contents' => $privateCsrfToken
                    ],
                    [
                        'name' => 'md5_name',
                        'contents' => $md5Infos->md5_name
                    ],
                    [
                        'name' => 'guess_extension',
                        'contents' => $md5Infos->guess_extension
                    ],
                    [
                        'name' => 'formats',
                        'contents' => json_encode(config('pictures.formats'))
                    ]
                ]
            ]
        );
        if($saveResponse->getStatusCode() >= 400){ return response($saveResponse->getBody(),500); }

        $picture = json_decode($saveResponse->getBody()->getContents(), true);
        $alreadyUploadPictures = $picture['normal'];

        $user = auth()->user();
        $user->pictureUrl = $alreadyUploadPictures;
        $user->save();

        return response()->json($alreadyUploadPictures);
    }

    /**
     * Delete a Picture
     * @param $type
     * @param $fileName
     * @return \Illuminate\Http\JsonResponse
     */
    public static function destroy(Request $request) {
        if ($request->filled('url') && filter_var($request->url, FILTER_VALIDATE_URL)){
            if (Advert::where('pictureUrl', $request->url)->count() === 0) {
                $client = new GuzzleClient();
                $delUrl = parse_url($request->url)['scheme'] . '://' . parse_url($request->url)['host'] . config('pictures.service.urls.routeDelete') . parse_url($request->url)['path'];
                $client->request('DELETE',
                    $delUrl,
                    [
                        'http_errors' => false,
                    ]
                );
            }
            $user = auth()->user();
            $user->pictureUrl = null;
            $user->save();
        }
        return response('ok',200);
    }

    /**
     *
     * Test if an picture url is available
     *
     * @param string $url
     * @return bool
     */
    public static function exist(string $url) {
        $client = new GuzzleClient();
        $exist = $client->request('GET',
            $url . '?test_exist=true',
            [
                'http_errors' => false
            ]
        );

        return $exist->getStatusCode()==200;

    }
}
