<?php

namespace Bramato\Uploadeasy;

use App\Classes\breadcrumbs;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class media extends Model
{
    protected $table = 'media';
    public function scopeUserId($query,$IdUser){
        return $query->where('idUser',$IdUser);
    }
    public function scopeSearch($query,$search){
        return $query->rightjoin('media_tags','media_tags.idMedia','=','media.id')->leftjoin('tags','tags.id','=','media_tags.idTag')->where('tags.tag',$search)->where('media_tags.Confidence','>',70);
    }
    public function scopeType($query,$type){
        return $query->where('type',$type);
    }
    public function getThumbnailAttribute(){
        if(strlen ($this->thumbs)>0){
            $ret=$this->thumbs;
        }else{
            $ret=getCoverJpg ($this->uuidImage,200,1);
            $this->thumbs=$ret;
            $this->save();

        }
        return $ret;
    }
    public function getUuidImageAttribute(){
        return $this->uuidMedia;
    }
    public function getUsernameAttribute(){
        $user = User::where('id',$this->idUser)->first();
        return $user->name;
    }
    public function getUrlAttribute(){
        $azienda= aziende::where('id',$this->dominio)->first();
        $s3 = Storage::disk ('s3');
        $type=explode('.',$this->fileName);

        $url = $s3->temporaryUrl (config ('filesystems.disks.file.dir') . $azienda->keysite . '/' . $this->uuidMedia . '.'.$type[1], now ()->addMinutes (60),['ResponseContentDisposition'=>"attachment; filename=".$this->fileName]);

        return $url;
    }
    public function getIconAttribute(){
        switch ($this->type) {
            case 'doc':
            case 'docx':
                $icon = 'fad fa-file-word';
                break;
            case 'png':
            case 'gif':
            case 'jpg':
            case 'jpeg':
            case 'webl':
                $icon = 'fad fa-image-polaroid';
                break;
            case 'zip':
            case 'rar':
                $icon = 'fad fa-file-archive';
                break;
            case 'pdf':
                $icon = 'fad fa-file-pdf';
                break;
            default:
                $icon = 'fad fa-sticky-note';
        }
        return $icon;
    }
    public function getSizeInBytesAttribute(){
        $size=$this->size;
        $precision=2;
        if ($size > 0) {
            $size = (int)$size;
            $base = log ($size) / log (1024);
            $suffixes = array (' bytes', ' KB', ' MB', ' GB', ' TB');

            $ret= round (pow (1024, $base - floor ($base)), $precision) . $suffixes[floor ($base)];
        } else {
            $ret= $size;
        }
        return $ret;
    }
    public function getRemoveAttribute(){
        return false;
    }
}
