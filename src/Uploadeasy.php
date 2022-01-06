<?php

namespace Bramato\Uploadeasy;

use Aws\Credentials\Credentials;
use Aws\ElasticTranscoder\ElasticTranscoderClient;
use Aws\Rekognition\RekognitionClient;
use Composer\Command\ValidateCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Webpatser\Uuid\Uuid;

class Uploadeasy
{
    public function form($id, Request $req)
    {
        App::setLocale(Auth::user()->lingua);
        $search = '';
        if (strlen($req->input('search')) > 3) {
            $search = $req->input('search');
        }
        $aws = aws_form();
        $url = $aws['url'];
        $inputs = $aws['inputsHTML'];
        $uuid = Uuid::generate()->string;
        $media = new media();

        if (strlen($search) > 3) {
            $images = $media->userId(Auth::id())->search($search)->type('img')->paginate(18);
        } else {
            $images = $media->userId(Auth::id())->type('img')->paginate(18);
        }
        $data = compact('url', 'inputs', 'uuid', 'id', 'images');

        return response()->json($data);
    }

    public function show($id)
    {
        $disk = Storage::disk('s3');
        $files = $disk->allFiles(config('filesystems.disks.'.config('uploadeasy.image_disk').'.dir').$id);
        $media = config('services.urlPath.s3root').$files[0];

        return redirect($media);
    }

    public function uploadcare($id, $command = null)
    {
        $disk = Storage::disk('s3');
        $files = $disk->allFiles(config('filesystems.disks.'.config('uploadeasy.image_disk').'.dir').$id);
        $response = Response::make(config('services.urlPath.s3root').$files[0]);
        $header = get_headers(config('services.urlPath.s3root').$files[0]);
        $response->header('Content-Type', 'image/png');
        //$response->header(get_headers(config('services.urlPath.s3root').$files[0]));
        return $this->get($id.'_old', $command);
        //return redirect(route(''));
    }

    public function resize($url, $size)
    {
        $sizeDim = explode('x', $size);
        if (count($sizeDim) > 1) {
            $w = $sizeDim[0];
            $h = $sizeDim[1];
        } else {
            $h = 0;
            $w = $sizeDim[0];
        }
        $disk = Storage::disk(config('uploadeasy.image_disk'));
        $media=config ('filesystems.disks.'.config('uploadeasy.image_disk').'.endpoint').'/'.config('filesystems.disks.'.config('uploadeasy.image_disk').'.dir').$url;
        if ($h < 1) {
            $img = Image::cache(function ($image) use ($media, $w) {
                $image->make($media)->resize($w, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->encode('png');
            }, '3');
        } else {
            $img = Image::cache(function ($image) use ($media, $w, $h) {
                $image->make($media)->resize($w, $h)->encode('png');
            }, '86400');
        }
        //$img = Image::make ($media);
        //$img->resize(320, 240);
        // create response and add encoded image data
        $response = Response::make($img);
        // set content-type
        $response->header('Content-Type', 'image/png');

        return $response;
    }

    public function get($params = false, $returnurl = false)
    {
        $route= Route::current();
        if (($params === false)) {
            $current_params = $route->parameters();
        } else {
            $current_params = $params;
        }
        //id immagine
        $id = $current_params['id'];
        //command
        $command = $current_params['command'];
        $resize['status'] = false;
        $blur['status'] = false;
        $greyscale['status'] = false;
        $crop['status'] = false;
        $encodeImg['status'] = false;
        $scaleCrop['status'] = false;
        $crop['status'] = false;
        $setfill['status'] = false;
        $header = 'image/png';

        //ID è un url?
        if (strpos($id, 'http') > 0) {
            try {
                $media = file_get_contents(urldecode($id));
                $filenameElaborated = encrypt(urldecode($id)).'-';
            }catch (\Exception $e){
                Log::error('Recupero immagine (id.'.$id.')fallito:'.$e->getMessage());
            }
        } else {
            $disk = Storage::disk(config('uploadeasy.image_disk'));
            $files = $disk->allFiles('media/' . $id);
            if (count($files) > 0) {
                $media = config('services.urlPath.s3root') . $files[0];
            } else {
                $media = config('uploadeasy.placeholder');
            }

            $filenameElaborated = $id.'-';
        }
        $a_commands = explode('/-/', $command);
        foreach ($a_commands as $item) {
            $a_command = explode('/', $item);
            if (count($a_command) > 1) {
                switch ($a_command[0]) {
                    case 'resize':
                        $resize['status'] = true;
                        $sizeDim = explode('x', $a_command[1]);
                        if (count($sizeDim) > 1) {
                            $resize['val']['w'] = $sizeDim[0];
                            $resize['val']['h'] = $sizeDim[1];
                        } else {
                            $resize['val']['h'] = 0;
                            $resize['val']['w'] = $sizeDim[0];
                        }

                        break;
                    case 'blur':
                        $blur['status'] = true;
                        $blur['val'] = $a_command[1];
                        break;
                    case 'encode':
                        $encodeImg['status'] = true;
                        $encodeImg['val'] = $a_command[1];
                        if (count($a_command) > 2) {
                            $encodeImg['q'] = $a_command[2];
                        } else {
                            $encodeImg['q'] = 90;
                        }
                        break;
                    case  'crop':
                        $crop['status'] = true;
                        $sizeDim = explode('x', $a_command[1]);
                        if (count($sizeDim) > 1) {
                            $crop['val']['w'] = $sizeDim[0];
                            $crop['val']['h'] = $sizeDim[1];
                        } else {
                            $crop['val']['h'] = 0;
                            $crop['val']['w'] = $sizeDim[0];
                        }
                        if (count($a_command) > 2) {
                            $crop['position'] = $a_command[2];
                        } else {
                            $crop['position'] = false;
                        }

                        break;
                    case 'scale-crop':
                    case 'scale_crop':
                        $scaleCrop['status'] = true;
                        $sizeDim = explode('x', $a_command[1]);
                        if (count($sizeDim) > 1) {
                            $scaleCrop['val']['w'] = $sizeDim[0];
                            $scaleCrop['val']['h'] = $sizeDim[1];
                        } else {
                            $scaleCrop['val']['h'] = 0;
                            $scaleCrop['val']['w'] = $sizeDim[0];
                        }

                        break;
                    case 'setfill':
                        $setfill['status'] = true;
                        $setfill['color'] = $a_command[1];

                        break;
                }
            } else {
                switch ($a_command[0]) {
                    case 'greyscale':
                        $greyscale['status'] = true;

                        break;
                }
            }
        }
        //Create name file
        if ($setfill['status']) {
            $filenameElaborated .= 'sf_'.$setfill['color'];
        }
        if ($resize['status']) {
            if ($resize['val']['h'] < 1) {
                $filenameElaborated .= 'rs_'.$resize['val']['w'];
            } else {
                $filenameElaborated .= 'rs_'.$resize['val']['w'].'x'.$resize['val']['h'];
            }
        }
        if ($scaleCrop['status']) {
            if ($scaleCrop['val']['h'] < 1) {
                $filenameElaborated .= 'sc_'.$scaleCrop['val']['w'];
            } else {
                $filenameElaborated .= 'sc_'.$scaleCrop['val']['w'].'x'.$scaleCrop['val']['h'];
            }
        }
        if ($crop['status']) {
            $filenameElaborated .= 'cr_'.$crop['val']['w'];
            if ($crop['position'] != false) {
                if ($crop['val']['h'] < 1) {
                    $filenameElaborated .= 'cr_'.$crop['val']['w'].'p-'.$crop['position'];
                } else {
                    $filenameElaborated .= 'cr_'.$crop['val']['w'].'x'.$crop['val']['h'].'p-'.$crop['position'];
                }
            } else {
                if ($crop['val']['h'] < 1) {
                    $filenameElaborated .= 'cr_'.$crop['val']['w'];
                } else {
                    $filenameElaborated .= 'cr_'.$crop['val']['w'].'x'.$crop['val']['h'];
                }
            }
        }
        if ($blur['status']) {
            if ($blur['val'] > 100) {
                $filenameElaborated .= 'blur100';
            } else {
                $filenameElaborated .= 'blur'.$blur['val'];
            }
        }
        if ($greyscale['status']) {
            $filenameElaborated .= 'greyscale';
        }
        if ($encodeImg['status']) {
            switch ($encodeImg['val']) {
                case 'jpg':
                    $filenameElaborated .= 'q'.$encodeImg['q'].'.jpg';

                    break;
                case 'png':
                case 'gif':
                    $filenameElaborated .= '.'.$encodeImg['val'];

                    break;
                default:
                    $filenameElaborated .= '.png';
            }
        } else {
            $filenameElaborated .= '.png';
        }
        //DD(config('filesystems.disks.'.config('uploadeasy.image_disk').'encoded/'.$filenameElaborated);
        if (! $disk->exists(config('filesystems.disks.'.config('uploadeasy.image_disk').'.dir').'encoded/'.$filenameElaborated)) {
            $img = Image::cache(function ($image) use ($crop, $setfill, $media, $resize, $greyscale, $blur, $encodeImg, $scaleCrop, $header) {
                $media = str_replace(' ', '%20', $media);
                $image->make($media);
                if ($setfill['status']) {
                    $image->fill('#'.$setfill['color'])->insert($media);
                }
                if ($resize['status']) {
                    if ($resize['val']['h'] < 1) {
                        $image->resize($resize['val']['w'], null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    } else {
                        $image->resize($resize['val']['w'], $resize['val']['h']);
                    }
                }
                if ($scaleCrop['status']) {
                    if ($scaleCrop['val']['h'] < 1) {
                        $image->fit($scaleCrop['val']['w']);
                    } else {
                        $image->fit($scaleCrop['val']['w'], $scaleCrop['val']['h'], function ($constraint) {
                            $constraint->upsize();
                        });
                    }
                }
                if ($crop['status']) {
                    $image->resize($crop['val']['w'], null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    if ($crop['position'] != false) {
                        if ($crop['val']['h'] < 1) {
                            $image->resizeCanvas($crop['val']['w'], null, $crop['position']);
                        } else {
                            $image->resizeCanvas($crop['val']['w'], $crop['val']['h'], $crop['position']);
                        }
                    } else {
                        if ($crop['val']['h'] < 1) {
                            $image->resizeCanvas($crop['val']['w'], null);
                        } else {
                            $image->resizeCanvas($crop['val']['w'], $crop['val']['h']);
                        }
                    }
                }
                if ($blur['status']) {
                    if ($blur['val'] > 100) {
                        $image->blur(100);
                    } else {
                        $image->blur($blur['val']);
                    }
                }
                if ($setfill['status']) {
                }
                if ($greyscale['status']) {
                    $image->greyscale();
                }
                if ($encodeImg['status']) {
                    switch ($encodeImg['val']) {
                        case 'jpg':
                            $image->encode($encodeImg['val'], $encodeImg['q']);

                            break;
                        case 'png':
                        case 'gif':
                            $image->encode($encodeImg['val']);

                            break;
                        default:
                            $image->encode('png');
                    }
                } else {
                    $image->encode('png');
                }
            }, '120');
            $disk->put(config('filesystems.disks.'.config('uploadeasy.image_disk').'.dir').'encoded/'.$filenameElaborated, $img, 'public');
            // create response and add encoded image data
            //$response = Response::make($img);
            // set content-type




            //$response->header('Content-Type', $header);
        }
        if ($returnurl) {
            return $disk->url('media/encoded/'.$filenameElaborated);
        } else {
            $img = Image::cache(function ($image) use ($disk, $filenameElaborated) {
                $media = file_get_contents($disk->url(config('filesystems.disks.'.config('uploadeasy.image_disk').'.encoded/'.$filenameElaborated)));
                $image->make($media);
            }, '25000');
            $response = Response::make($img);
            $response->header('Content-Type', $header);
            //return redirect($disk->url('media/encoded/'.$filenameElaborated));
            return $response;
        }
    }

    public function mediaList($type, $paginate, Request $req)
    {
        $search = '';
        if (strlen($req->input('search')) > 3) {
            $search = $req->input('search');
        }
        $media = new \App\models\media();
        if (strlen($search) > 3) {
            $listMedia = $media->userId(Auth::id())->search($search)->type($type)->paginate($paginate);
        } else {
            $listMedia = $media->userId(Auth::id())->type($type)->paginate($paginate);
        }

        return response()->json($listMedia);
    }

    public function avatar($id, $size = 256)
    {
        return Redirect::to(getImgPng($id, 256));
    }

    public function bravatar($id, $size = 256)
    {
        return Redirect::to('https://bramatar.com/avatar/palace/'.$size.'/'.$id.'.png');
    }

    public function cover($id, $width = 1300, $r = 0.5)
    {
        return Redirect::to(getCoverJpg($id, $width, $r));
    }

    public function aws()
    {
        $aws = aws_form();
        $uuid = Uuid::generate()->string;

        return response()->json(compact('aws', 'uuid'));
    }

    public function save(Request $request)
    {
        $request->validate([
                               'key' => 'required',
                           ]);
        $uuid = $request->input('uuid');
        $key = $request->input('key');
        $size = $request->input('size');
        $typeTarget = $request->input('typeTarget');
        $originalFileName = $request->input('originalname');
        //$filename = str_replace('.' . $request->input('extention'), '', $request->input('newname'));
        $url = config('services.urlPath.s3root').$key;
        $tags = [];
        $type = $request->input('type');
        //$tags = $this->recognize ($request->input('file'));
        $return = [];
        $return['url'] = $url;
        $return['uuid'] = $uuid;
        $return['tags'] = $tags;
        $media = new \App\models\media();
        $media->uuidMedia = $uuid;
        $media->type = $type;
        $media->size = $size;
        $media->idUser = Auth::id();
        $media->dominio = $this->dominio;
        if ($type === 'video') {
            $media->awsJobId = $this->transcode($key, $uuid);
        }
        if ($type === 'file') {
            $file = new files();
            $file->dominio = $this->dominio;
            $file->idAuthor = Auth::id();
            $file->typeTarget = $typeTarget;
            $file->title = $originalFileName;
            $file->idMedia = $uuid;

            $file->save();
        }
        $media->filename = $originalFileName;
        $media->key = $key;
        $media->save();

        $idMedia = $media->id;
        $return['idMedia'] = $idMedia;
        $return['filename'] = $originalFileName;
        $return['key'] = $key;
        $return['uuid'] = $uuid;
        $return['size'] = $size;

        foreach ($tags as $tag) {
            $idTag = tags::addTag($tag);
            $idMediaTag = mediaTags::addTag($idTag, $idMedia, $tag['Confidence']);
        }


        //$json_tags=json_encode ($tags,true);
        // $recordImage= media::saveMedia ($url,'',$json_tags,1);
        //$return_json=json_encode ($return);
        return Response::json($return);
        //return view ('image',compact('url','tags'));
    }

    public function recognize($url = '1575471244739.jpg')
    {
        $credentials = new Credentials(config('filesystems.disks.s3.key'), config('filesystems.disks.s3.secret'));

        $client = new RekognitionClient([
                                            'region' => 'eu-west-1',
                                            'version' => 'latest',
                                            'credentials' => $credentials,
                                        ]);
        $return = $client->detectLabels([
                                             'Image' => [
                                                 'S3Object' => [
                                                     'Bucket' => config('filesystems.disks.s3.bucket'),
                                                     'Name' => $url,
                                                     'Region' => config('filesystems.disks.s3.region'),
                                                 ],
                                             ],
                                         ]);

        $return_array = $return->toArray();
        $labels = $return_array['Labels'];
        $return_html = '';
        foreach ($labels as $label) {
            $new_badge = view('templates.little_component.badge', ['title' => $label['Name']]);
            $return_html = $return_html.' '.$new_badge;
        }

        return $labels;
    }

    public function transcode($key, $filename)
    {
        $elasticTranscoder = ElasticTranscoderClient::factory([
                                                                  'credentials' => [
                                                                      'key' => config('filesystems.disks.video.key'),
                                                                      'secret' => config('filesystems.disks.video.secret'),
                                                                  ],
                                                                  'region' => config('filesystems.disks.video.region'),
                                                                  'version' => 'latest',
                                                              ]);
        $job = $elasticTranscoder->createJob([

                                                 'PipelineId' => env('AWS_ELASTIC_PIPELINE'),

                                                 'OutputKeyPrefix' => config('filesystems.disks.video.dir') . $this->keysite .'/transcoded/',

                                                 'Input' => [
                                                     'Key' => $key,
                                                     'FrameRate' => 'auto',
                                                     'Resolution' => 'auto',
                                                     'AspectRatio' => 'auto',
                                                     'Interlaced' => 'auto',
                                                     'Container' => 'auto',
                                                 ],

                                                 'Outputs' => [
                                                     [
                                                         'Key' => $filename.'.mp4',
                                                         'Rotate' => 'auto',
                                                         'PresetId' => env('AWS_ELASTIC_MP4'),
                                                     ],
                                                     [
                                                         'Key' => $filename . '.webm',
                                                         'Rotate' => 'auto',
                                                         'PresetId' => env('AWS_ELASTIC_WEBM'),
                                                     ],
                                                 ],
                                             ]);
        $jobData = $job->get('Job');
        // you can save the job ID somewhere, so you can check
        // the status from time to time.
        return $jobData['Id'];
    }

    public function getDraftFiles($schoolId, $typeTarget)
    {
        $files = files::where('dominio', $this->dominio)->where('typeTarget', $typeTarget)->where('idTarget', null)->where('idAuthor', Auth::id())->get();
        $ret = [];
        foreach ($files as $x => $file) {
            $ret[$x]['uuid'] = $file->idMedia;
            $ret[$x]['key'] = $file->fileData->key;
            $ret[$x]['filename'] = $file->fileData->fileName;
            $ret[$x]['type'] = $file->fileData->type;
            $ret[$x]['size'] = $file->fileData->size;
            $ret[$x]['id'] = $file->id;
        }

        return Response::json($ret);
    }

    public function deleteDraftFile($schoolId, $idFile)
    {
        $file = files::where('dominio', $this->dominio)->where('id', $idFile)->where('idTarget', null)->where('idAuthor', Auth::id())->delete();

        return Response::json($file);
    }
}
