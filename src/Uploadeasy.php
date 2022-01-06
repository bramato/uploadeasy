<?php

namespace Bramato\Uploadeasy;

use Aws\Credentials\Credentials;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Uploadeasy
{
    function resize($url,$size){
        $sizeDim=explode ('x',$size);
        if(count ($sizeDim)>1){
            $w=$sizeDim[0];
            $h=$sizeDim[1];
        }else{
            $h=0;
            $w=$sizeDim[0];
        }
        $s3 = Storage::disk('s3');
        $media=config ('filesystems.disks.s3.endpoint').'/'.config('filesystems.disks.s3.dir').$url;

        if ($h<1){
            $img = Image::cache(function($image) use($media,$w) {
                $image->make($media)->resize($w, null, function ($constraint) {
                    $constraint->aspectRatio();
                })->encode('png');
            },'3');
        }else{
            $img = Image::cache(function($image) use($media,$w,$h) {
                $image->make($media)->resize($w,$h)->encode('png');
            },'86400');
        }
        //$img = Image::make ($media);
        //$img->resize(320, 240);
        // create response and add encoded image data
        $response = Response::make($img);
        // set content-type
        $response->header('Content-Type', 'image/png');
        return $response;
    }
    function imageShop($params=false,$returnurl=false){

        if($params===false){
            $current_params = Route::current()->parameters();
        }else{
            $current_params = $params;
        }

        $id=$current_params['id'];
        $command=$current_params['command'];
        $resize['status']=false;
        $blur['status']=false;
        $greyscale['status']=false;
        $crop['status']=false;
        $encodeImg['status']=false;
        $scaleCrop['status']=false;
        $crop['status']=false;
        $setfill['status']=false;
        $header='image/png';

        if(strpos($id,'http')>0) {
            $media = file_get_contents(urldecode($id));
            $filenameElaborated=encrypt(urldecode($id)).'-';
        }else{
            $s3 = Storage::disk('s3');
            $files = $s3->allFiles('media/' . $id);
            if(count($files)>0) {
                $media = config('services.urlPath.s3root') . $files[0];
            }else{
                $media = 'https://procedeasy.s3-eu-west-1.amazonaws.com/media/e8df8806-489e-44b8-8dba-6ed66b7b588e/mercury.png';
            }

            $filenameElaborated=$id.'-';
        }
        $a_commands=explode ('/-/',$command);
        foreach ($a_commands as $item) {
            $a_command = explode ('/', $item);
            if (count ($a_command) > 1) {
                switch ($a_command[0]) {
                    case 'resize':
                        $resize['status'] = true;
                        $sizeDim = explode ('x', $a_command[1]);
                        if (count ($sizeDim) > 1) {
                            $resize['val']['w'] = $sizeDim[0];
                            $resize['val']['h'] = $sizeDim[1];
                        } else {
                            $resize['val']['h'] = 0;
                            $resize['val']['w'] = $sizeDim[0];
                        }
                        break;
                    case 'blur':
                        $blur['status'] = true;
                        $blur['val']=$a_command[1];
                        break;
                    case 'encode':
                        $encodeImg['status']=true;
                        $encodeImg['val']=$a_command[1];
                        if(count($a_command)>2){
                            $encodeImg['q']=$a_command[2];
                        }else{
                            $encodeImg['q']=90;
                        }

                        break;
                    case  'crop':
                        $crop['status']=true;
                        $sizeDim= explode ('x',$a_command[1]);
                        if (count ($sizeDim) > 1) {
                            $crop['val']['w'] = $sizeDim[0];
                            $crop['val']['h'] = $sizeDim[1];
                        } else {
                            $crop['val']['h'] = 0;
                            $crop['val']['w'] = $sizeDim[0];
                        }
                        if (count($a_command)>2){
                            $crop['position']=$a_command[2];
                        }else{
                            $crop['position']=false;
                        }
                        break;
                    case 'scale-crop':
                    case 'scale_crop':
                        $scaleCrop['status']=true;
                        $sizeDim= explode ('x',$a_command[1]);
                        if (count ($sizeDim) > 1) {
                            $scaleCrop['val']['w'] = $sizeDim[0];
                            $scaleCrop['val']['h'] = $sizeDim[1];
                        } else {
                            $scaleCrop['val']['h'] = 0;
                            $scaleCrop['val']['w'] = $sizeDim[0];
                        }
                        break;
                    case 'setfill':
                        $setfill['status']=true;
                        $setfill['color']=$a_command[1];
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
        if ($setfill['status']){
            $filenameElaborated.='sf_'.$setfill['color'];
        }
        if ($resize['status']){
            if ($resize['val']['h']<1){
                $filenameElaborated.='rs_'.$resize['val']['w'];
            }else{
                $filenameElaborated.='rs_'.$resize['val']['w'].'x'.$resize['val']['h'];
            }

        }
        if ($scaleCrop['status']){

            if ($scaleCrop['val']['h']<1){
                $filenameElaborated.='sc_'.$scaleCrop['val']['w'];
            }else{
                $filenameElaborated.='sc_'.$scaleCrop['val']['w'].'x'.$scaleCrop['val']['h'];
            }

        }
        if ($crop['status']){
            $filenameElaborated.='cr_'.$crop['val']['w'];
            if($crop['position']!=false){

                if ($crop['val']['h']<1){
                    $filenameElaborated.='cr_'.$crop['val']['w'].'p-'.$crop['position'];
                }else{
                    $filenameElaborated.='cr_'.$crop['val']['w'].'x'.$crop['val']['h'].'p-'.$crop['position'];
                }
            }else{
                if ($crop['val']['h']<1){
                    $filenameElaborated.='cr_'.$crop['val']['w'];
                }else{
                    $filenameElaborated.='cr_'.$crop['val']['w'].'x'.$crop['val']['h'];
                }
            }

        }
        if($blur['status']){
            if($blur['val']>100){
                $filenameElaborated.='blur100';
            }else{
                $filenameElaborated.='blur'.$blur['val'];
            }
        }
        if ($greyscale['status']){
            $filenameElaborated.='greyscale';
        }
        if ($encodeImg['status']){
            switch ($encodeImg['val']){
                case 'jpg':
                    $filenameElaborated.='q'.$encodeImg['q'].'.jpg';
                    break;
                case 'png':
                case 'gif':
                    $filenameElaborated.='.'.$encodeImg['val'];
                    break;
                default:
                    $filenameElaborated.='.png';
            }
        }else{
            $filenameElaborated.='.png';
        }
        //DD(config('filesystems.disks.s3.dir').'encoded/'.$filenameElaborated);
        if(!$s3->exists(config('filesystems.disks.s3.dir').'encoded/'.$filenameElaborated)){
            $img = Image::cache(function($image) use($crop, $setfill, $media,$resize,$greyscale,$blur,$encodeImg,$scaleCrop,$header) {
                $media=str_replace(' ','%20',$media);
                $image->make($media);
                if ($setfill['status']){
                    $image->fill('#'.$setfill['color'])->insert($media);
                }
                if ($resize['status']){
                    if ($resize['val']['h']<1){
                        $image->resize($resize['val']['w'], null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                    }else{
                        $image->resize($resize['val']['w'],$resize['val']['h']);
                    }

                }
                if ($scaleCrop['status']){

                    if ($scaleCrop['val']['h']<1){
                        $image->fit($scaleCrop['val']['w']);
                    }else{
                        $image->fit($scaleCrop['val']['w'], $scaleCrop['val']['h'], function ($constraint) {
                            $constraint->upsize();
                        });
                    }

                }
                if ($crop['status']){
                    $image->resize($crop['val']['w'], null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    if($crop['position']!=false){

                        if ($crop['val']['h']<1){
                            $image->resizeCanvas($crop['val']['w'], null,$crop['position']);
                        }else{
                            $image->resizeCanvas($crop['val']['w'],$crop['val']['h'],$crop['position']);
                        }
                    }else{
                        if ($crop['val']['h']<1){
                            $image->resizeCanvas($crop['val']['w'], null);
                        }else{
                            $image->resizeCanvas($crop['val']['w'],$crop['val']['h']);
                        }
                    }

                }
                if($blur['status']){
                    if($blur['val']>100){
                        $image->blur(100);
                    }else{
                        $image->blur($blur['val']);
                    }
                }
                if ($setfill['status']){

                }
                if ($greyscale['status']){
                    $image->greyscale();
                }
                if ($encodeImg['status']){
                    switch ($encodeImg['val']){
                        case 'jpg':
                            $image->encode($encodeImg['val'],$encodeImg['q']);
                            break;
                        case 'png':
                        case 'gif':
                            $image->encode($encodeImg['val']);
                            break;
                        default:
                            $image->encode('png');
                    }
                }else{
                    $image->encode('png');
                }
            },'120');
            $s3->put(config('filesystems.disks.s3.dir').'encoded/'.$filenameElaborated,$img,'public');
// create response and add encoded image data
            //$response = Response::make($img);
            // set content-type




            //$response->header('Content-Type', $header);



        }
        if($returnurl){
            return $s3->url('media/encoded/'.$filenameElaborated);
        }else {
            $img = Image::cache(function($image) use($s3,$filenameElaborated){
                $media= file_get_contents($s3->url(config('filesystems.disks.s3.dir').'encoded/'.$filenameElaborated));
                $image->make($media);
            },'25000');
            $response = Response::make($img);
            $response->header('Content-Type', $header);
//return redirect($s3->url('media/encoded/'.$filenameElaborated));
            return $response;
        }

    }

    function recognize($url='1575471244739.jpg'){
        $credentials= new Credentials( config ('filesystems.disks.s3.key'),config('filesystems.disks.s3.secret'));

        $client = new RekognitionClient([
                                            'region'    => 'eu-west-1',
                                            'version'   => 'latest',
                                            'credentials'=> $credentials,
                                        ]);
        $return = $client->detectLabels ([
                                             'Image' => [
                                                 'S3Object'=>[
                                                     'Bucket'=>config('filesystems.disks.s3.bucket'),
                                                     'Name'=>$url,
                                                     'Region'=>config('filesystems.disks.s3.region')
                                                 ]
                                             ]
                                         ]);

        $return_array= $return->toArray ();
        $labels=$return_array['Labels'];
        $return_html='';
        foreach ($labels as $label){
            $new_badge=view ('templates.little_component.badge',['title'=>$label['Name']]);
            $return_html=$return_html.' '.$new_badge;
        }

        return $labels;
    }
}
