<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class OrganizationModel extends Model
{
    public static function toOrganizationIDListArray($arr)
    {
      if (gettype($arr)!=="array")return false;

      foreach ($arr as $key => &$value)
      {
        if (is_numeric($value)&&(is_int($value)||ctype_digit($value)))
        {
          $value=intval($value);
        }
        else
        {
          unset($value);
          return false;
        }
      }
      unset($value);

      sort($arr);
      return $arr;
    }

    public static function getOrganizationList()
    {
      $data=DB::table('Organization')->select('id','Name')->orderBy('id')->get();

      unset($organizationList);
      $organizationList=[];
      foreach ($data as $organization)
      {
        $organizationList[$organization->id]=$organization->Name;
      }
      return $organizationList;
    }

    public static function getOrganizationFromID($id)
    {
      return DB::table('Organization')->where('id',$id)->value('Name');
    }

    public static function isOrganizationIDExist($id)
    {
      if (gettype($id)!=="integer")return false;
      return DB::table('Organization')->where('id',$id)->exists();
    }

    public static function isOrganizationIDListValid($list)
    {
      if (gettype($list)!=="array")return false;
      $valid=true;
      foreach ($list as $id)
      {
        $valid&=self::isOrganizationIDExist($id);
      }
      return $valid;
    }
}
