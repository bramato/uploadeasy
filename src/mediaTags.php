<?php

namespace Bramato\Uploadeasy;

use Illuminate\Database\Eloquent\Model;

class mediaTags extends Model
{
    protected $table = 'media_tags';

    public static function addTag($idTag, $idMedia, $Confidence)
    {
        $tags = new mediaTags();
        $ck = $tags->idMedia($idMedia)->idTag($idTag)->first();
        if (! $ck) {
            $tags->idMedia = $idMedia;
            $tags->idTag = $idTag;
            $tags->Confidence = $Confidence;
            $tags->save();
            $id = $tags->id;
        } else {
            $id = $ck->id;
        }

        return $id;
    }

    public function scopeIdMedia($query, $idMedia)
    {
        return $query->where('idMedia', $idMedia);
    }

    public function scopeIdTag($query, $idTag)
    {
        return $query->where('idTag', $idTag);
    }
}
