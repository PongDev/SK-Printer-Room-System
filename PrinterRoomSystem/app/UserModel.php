<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use Validator;
use App\OrganizationModel;

class UserModel extends Model
{
    const RANKID=[
      0 => "Admin",
      1 => "Member"
    ];

    public static function login($usr,$pwd)
    {
      $usrdata=DB::table('User')->where('Username',$usr)->first();
      if ($usrdata!=NULL)
      {
        $pwdhsh=$usrdata->Password;
        if (password_verify($pwd,$pwdhsh))
        {
          DB::table('User')->where('Username',$usr)->update(['Last_Login'=>DB::raw('NOW()')]);
          session(['loginID'=>$usrdata->Username]);
        }
      }

      if (!self::isLogin())session(['loginFailed'=>true]);
    }

    public static function logout()
    {
      session()->flush();
    }

    public static function isLogin()
    {
      return session()->exists('loginID');
    }

    public static function userData()
    {
      return DB::table('User')->where('Username',session('loginID'))->first();
    }

    public static function getUserData($usr)
    {
      return DB::table('User')->where('Username',$usr)->first();
    }

    public static function isUserExist($usr)
    {
      return DB::table('User')->where('Username',$usr)->exists();
    }

    public static function isLastLoginFailed()
    {
      return (session()->exists('loginFailed')&&session('loginFailed'));
    }

    public static function clearLastLoginFailed()
    {
      session()->forget('loginFailed');
    }

    public static function getOrganizationIDList()
    {
      return json_decode(self::userData()->OrganizationIDList);
    }

    public static function getOrganizationList()
    {
      $organizationList=[];
      $organizationIDList=self::getOrganizationIDList();
      foreach ($organizationIDList as $idx => $organizationID)
      {
        $organizationList[$idx]=OrganizationModel::getOrganizationFromID($organizationID);
      }
      return $organizationList;
    }

    public static function rankidtostr($rankid)
    {
      if (isset(self::RANKID[$rankid])) return self::RANKID[$rankid];
      else return false;
    }

    public static function rankstrtoid($rankstr)
    {
      if (isset(array_flip(self::RANKID)[$rankstr]))return array_flip(self::RANKID)[$rankstr];
      else return false;
    }

    public static function getRankList()
    {
      return self::RANKID;
    }

    public static function addUser($usr,$pwd,$rank,$displayName,$organizationIDList)
    {
      if (gettype($rank)==="string")$rank=self::rankstrtoid($rank);

      $organizationIDList=OrganizationModel::toOrganizationIDListArray($organizationIDList);
      if ($organizationIDList===false)return false;

      if ($rank===false||
          gettype($rank)!=="integer"||
          !isset(self::RANKID[$rank])||
          !OrganizationModel::isOrganizationIDListValid($organizationIDList)||
          DB::table('User')->where('Username',$usr)->exists())return false;

      $data['Username']           = $usr;
      $data['Password']           = $pwd;
      $data['Rank']               = $rank;
      $data['DisplayName']        = $displayName;
      $data['OrganizationIDList'] = $organizationIDList;

      $validator=Validator::make($data,[
        'Username'             => 'required|string|unique:User,Username',
        'Password'             => 'required|string',
        'Rank'                 => 'required|integer',
        'DisplayName'          => 'required|string',
        'OrganizationIDList'   => 'array',
        'OrganizationIDList.*' => 'integer'
      ]);

      if ($validator->fails())return false;

      DB::table('User')->insert([
        'Username'           => $data['Username'],
        'Password'           => password_hash($data['Password'],PASSWORD_DEFAULT),
        'Rank'               => $data['Rank'],
        'DisplayName'        => $data['DisplayName'],
        'OrganizationIDList' => json_encode($data['OrganizationIDList'])
      ]);
      return true;
    }

    public static function updateUser($usr,$pwd,$rank,$displayName,$organizationIDList)
    {
      if (!isset($usr)||!self::isUserExist($usr))return false;

      $userData=self::getUserData($usr);

      if ($userData==NULL)return false;

      if ($pwd!=NULL)
      {
        unset($data);
        unset($validator);
        $data['Password']           = $pwd;
        $validator=Validator::make($data,[
          'Password'             => 'required|string'
        ]);
        if ($validator->fails())return false;
        $userData->Password=password_hash($data['Password'],PASSWORD_DEFAULT);
      }

      if ($rank!==NULL)
      {
        if (gettype($rank)==="string")$rank=self::rankstrtoid($rank);
        if ($rank===false||
          gettype($rank)!=="integer"||
          !isset(self::RANKID[$rank]))return false;

        unset($data);
        unset($validator);
        $data['Rank']               = $rank;
        $validator=Validator::make($data,[
          'Rank'                 => 'required|integer'
        ]);
        if ($validator->fails())return false;
        $userData->Rank=$data['Rank'];
      }

      if ($displayName!==NULL)
      {
        unset($data);
        unset($validator);
        $data['DisplayName']        = $displayName;
        $validator=Validator::make($data,[
          'DisplayName'          => 'required|string'
        ]);
        if ($validator->fails())return false;
        $userData->DisplayName=$data['DisplayName'];
      }

      if ($organizationIDList!==NULL)
      {
        $organizationIDList=OrganizationModel::toOrganizationIDListArray($organizationIDList);
        if ($organizationIDList===false)return false;

        if (!OrganizationModel::isOrganizationIDListValid($organizationIDList))return false;
        unset($data);
        unset($validator);
        $data['OrganizationIDList'] = $organizationIDList;
        $validator=Validator::make($data,[
          'OrganizationIDList'   => 'array',
          'OrganizationIDList.*' => 'integer'
        ]);
        if ($validator->fails())return false;
        $userData->OrganizationIDList=$data['OrganizationIDList'];
      }

      DB::table('User')->where('Username',$userData->Username)->update([
        'Password'           => $userData->Password,
        'Rank'               => $userData->Rank,
        'DisplayName'        => $userData->DisplayName,
        'OrganizationIDList' => $userData->OrganizationIDList
      ]);
      return true;
    }

    public static function deleteUser($usr)
    {
      if (!isset($usr)||!self::isUserExist($usr))return false;
      DB::table('User')->where('Username',$usr)->delete();
      return true;
    }

    public static function getUserDataList()
    {
      return DB::table('User')->get();
    }

    public static function getUserIDListWhereLike($usrname,$displayname)
    {
      if (!is_string($usrname)&&!is_string($displayname))return [];

      $userIDList=DB::table('User');

      if (is_string($usrname))$userIDList->where('Username','like','%'.$usrname.'%');
      if (is_string($displayname))$userIDList->where('DisplayName','like','%'.$displayname.'%');
      return $userIDList->pluck('id');
    }

    public static function changePassword($oldPwd,$newPwd)
    {
      if (self::isLogin())
      {
        $userData=self::userData();
        if (password_verify($oldPwd,$userData->Password))
        {
          return self::updateUser($userData->Username,$newPwd,NULL,NULL,NULL);
        }
      }
      else return false;
    }
}
