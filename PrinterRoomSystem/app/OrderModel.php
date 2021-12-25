<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use PDF;
use Validator;
use App\UserModel;

class OrderModel extends Model
{
    public static function getOrderCodeTranslator()
    {
      $translate['pagePerPaper']['1']="หน้าเดียว";
      $translate['pagePerPaper']['2']="หน้า-หลัง";

      $translate['WorkType']['1']="เอกสารการสอน";
      $translate['WorkType']['2']="เอกสารทั่วไป";
      $translate['WorkType']['3']="ข้อสอบ";
      return $translate;
    }

    public static function addOrder($orderData)
    {
      session()->forget('orderReportPDF');

      $valid=true;

      $validator=Validator::make($orderData,[
        'OrganizationIDIndex' => 'required|integer|between:1,'.strval(count(UserModel::getOrganizationIDList())),
        'WorkType'            => 'required|integer|between:1,3',
        'brownPageOrigin'     => 'required|integer|min:0',
        'brownCopy'           => 'required|integer|min:0',
        'brownPagePerPaper'   => 'required|integer|between:1,2',
        'whitePageOrigin'     => 'required|integer|min:0',
        'whiteCopy'           => 'required|integer|min:0',
        'whitePagePerPaper'   => 'required|integer|between:1,2',
        'colorPageOrigin'     => 'required|integer|min:0',
        'colorCopy'           => 'required|integer|min:0',
        'colorPagePerPaper'   => 'required|integer|between:1,2'
      ]);

      if ($validator->fails())$valid=false;
      else if (
        (intval($orderData['brownPageOrigin'])==0||intval($orderData['brownCopy'])==0)&&
        (intval($orderData['whitePageOrigin'])==0||intval($orderData['whiteCopy'])==0)&&
        (intval($orderData['colorPageOrigin'])==0||intval($orderData['colorCopy'])==0))$valid=false;
      else if (
        ((intval($orderData['brownPageOrigin'])*intval($orderData['brownCopy'])==0)&&
        (intval($orderData['brownPageOrigin'])!=0||intval($orderData['brownCopy'])!=0))
        ||
        ((intval($orderData['whitePageOrigin'])*intval($orderData['whiteCopy'])==0)&&
        (intval($orderData['whitePageOrigin'])!=0||intval($orderData['whiteCopy'])!=0))
        ||
        ((intval($orderData['colorPageOrigin'])*intval($orderData['colorCopy'])==0)&&
        (intval($orderData['colorPageOrigin'])!=0||intval($orderData['colorCopy'])!=0))
        )$valid=false;

      if ($valid)
      {
        $orderDetail['brownPageOrigin']   = $orderData['brownPageOrigin'];
        $orderDetail['brownCopy']         = $orderData['brownCopy'];
        $orderDetail['brownPagePerPaper'] = $orderData['brownPagePerPaper'];

        $orderDetail['whitePageOrigin']   = $orderData['whitePageOrigin'];
        $orderDetail['whiteCopy']         = $orderData['whiteCopy'];
        $orderDetail['whitePagePerPaper'] = $orderData['whitePagePerPaper'];

        $orderDetail['colorPageOrigin']   = $orderData['colorPageOrigin'];
        $orderDetail['colorCopy']         = $orderData['colorCopy'];
        $orderDetail['colorPagePerPaper'] = $orderData['colorPagePerPaper'];

        DB::table('Order')->insert(
          ['MemberID'       => UserModel::userData()->id,
           'OrganizationID' => UserModel::getOrganizationIDList()[$orderData['OrganizationIDIndex']-1],
           'BrownPaper'     => ceil(((double)$orderData['brownPageOrigin'])/$orderData['brownPagePerPaper'])*$orderData['brownCopy'],
           'WhitePaper'     => ceil(((double)$orderData['whitePageOrigin'])/$orderData['whitePagePerPaper'])*$orderData['whiteCopy'],
           'ColorPaper'     => ceil(((double)$orderData['colorPageOrigin'])/$orderData['colorPagePerPaper'])*$orderData['colorCopy'],
           'WorkType'       => $orderData['WorkType'],
           'Detail'         => json_encode($orderDetail)]
        );
        session(['orderReportPDF'=>PDF::loadView('pdfForm',[
          'orderData'=>$orderData,
          'userData'=>UserModel::userData(),
          'organizationName'=>OrganizationModel::getOrganizationFromID(UserModel::getOrganizationIDList()[$orderData['OrganizationIDIndex']-1]),
          'translate'=>self::getOrderCodeTranslator()])->stream()]);
        return true;
      }
      else
      {
        session(['orderFailed'=>true]);
        return false;
      }
    }

    public static function isLastOrderFailed()
    {
      return (session()->exists('orderFailed')&&session('orderFailed'));
    }

    public static function clearLastOrderFailed()
    {
      session()->forget('orderFailed');
    }

    public static function isOrderReportPDF()
    {
      return session()->exists('orderReportPDF');
    }

    public static function getOrderReportPDF()
    {
      $reportPDF=session('orderReportPDF');
      session()->forget('orderReportPDF');
      return $reportPDF;
    }

    public static function getUserOrderHistory($id)
    {
      return DB::table('Order')->where('MemberID',$id)->get();
    }

    public static function getOrganizationOrderHistory($id)
    {
      return DB::table('Order')->where('OrganizationID',$id)->get();
    }

    public static function getAllOrderHistory($usrname,$displayname,$organizationList,$workTypeList,$orderBy,$sortAsc,$limitPerPage,$page)
    {
      if (
        !is_string($usrname)&&
        !is_string($displayname)&&
        !is_array($organizationList)&&
        !is_array($workTypeList))return [];

      $history=DB::table('Order')
                ->join('User','Order.MemberID','=','User.id')
                ->join('Organization','Order.OrganizationID','=','Organization.id')
                ->select('User.Username','User.DisplayName','Organization.Name as OrganizationName','Order.*');

      if (is_string($usrname)||is_string($displayname))
        $history->whereIn('MemberID',UserModel::getUserIDListWhereLike($usrname,$displayname));
      if (is_array($organizationList))
        $history->whereIn('OrganizationID',$organizationList);
      if (is_array($workTypeList))
        $history->whereIn('WorkType',$workTypeList);
      if (is_int($page)&&is_int($limitPerPage))
      {
        $page=max($page,1);
        $limitPerPage=max($limitPerPage,1);
        $history->offset(($page-1)*$limitPerPage);
        $history->limit($limitPerPage);
      }

      switch($orderBy)
      {
        case 1:
          $history->orderBy('Time',$sortAsc?'asc':'desc');
          break;
        case 2:
          $history->orderBy('Username',$sortAsc?'asc':'desc');
          break;
        case 3:
          $history->orderBy('DisplayName',$sortAsc?'asc':'desc');
          break;
        case 4:
          $history->orderBy('BrownPaper',$sortAsc?'asc':'desc');
          break;
        case 5:
          $history->orderBy('WhitePaper',$sortAsc?'asc':'desc');
          break;
        case 6:
          $history->orderBy('ColorPaper',$sortAsc?'asc':'desc');
          break;
      }
      $historyDataList=$history->get();
      $workTypeTranslator=self::getOrderCodeTranslator()['WorkType'];
      foreach ($historyDataList as &$value)
      {
        $value->WorkType=$workTypeTranslator[$value->WorkType];
      }
      unset($value);
      return $historyDataList;
    }

    public static function countAllOrderHistory($usrname,$displayname,$organizationList,$workTypeList)
    {
      if (
        !is_string($usrname)&&
        !is_string($displayname)&&
        !is_array($organizationList)&&
        !is_array($workTypeList))return [];

        $history=DB::table('Order')
                  ->join('User','Order.MemberID','=','User.id')
                  ->join('Organization','Order.OrganizationID','=','Organization.id')
                  ->select('User.Username','User.DisplayName','Organization.Name as OrganizationName','Order.*');

      if (is_string($usrname)||is_string($displayname))
        $history->whereIn('MemberID',UserModel::getUserIDListWhereLike($usrname,$displayname));
      if (is_array($organizationList))
        $history->whereIn('OrganizationID',$organizationList);
      if (is_array($workTypeList))
        $history->whereIn('WorkType',$workTypeList);
      return $history->count();
    }

    public static function getUserPaperSummary($id)
    {
      $data=new \stdClass;
      $data->BrownPaper=(int)DB::table('Order')->where('MemberID',$id)->sum('BrownPaper');
      $data->WhitePaper=(int)DB::table('Order')->where('MemberID',$id)->sum('WhitePaper');
      $data->ColorPaper=(int)DB::table('Order')->where('MemberID',$id)->sum('ColorPaper');
      return $data;
    }

    public static function getOrganizationPaperSummary($id)
    {
      $data=new \stdClass;

      $data->WorkType1BrownPaper=(int)DB::table('Order')->where([['OrganizationID',$id],['WorkType','1']])->sum('BrownPaper');
      $data->WorkType1WhitePaper=(int)DB::table('Order')->where([['OrganizationID',$id],['WorkType','1']])->sum('WhitePaper');
      $data->WorkType1ColorPaper=(int)DB::table('Order')->where([['OrganizationID',$id],['WorkType','1']])->sum('ColorPaper');

      $data->WorkType2BrownPaper=(int)DB::table('Order')->where([['OrganizationID',$id],['WorkType','2']])->sum('BrownPaper');
      $data->WorkType2WhitePaper=(int)DB::table('Order')->where([['OrganizationID',$id],['WorkType','2']])->sum('WhitePaper');
      $data->WorkType2ColorPaper=(int)DB::table('Order')->where([['OrganizationID',$id],['WorkType','2']])->sum('ColorPaper');

      $data->WorkType3BrownPaper=(int)DB::table('Order')->where([['OrganizationID',$id],['WorkType','3']])->sum('BrownPaper');
      $data->WorkType3WhitePaper=(int)DB::table('Order')->where([['OrganizationID',$id],['WorkType','3']])->sum('WhitePaper');
      $data->WorkType3ColorPaper=(int)DB::table('Order')->where([['OrganizationID',$id],['WorkType','3']])->sum('ColorPaper');

      $data->BrownPaperSummary=(int)DB::table('Order')->where('OrganizationID',$id)->sum('BrownPaper');
      $data->WhitePaperSummary=(int)DB::table('Order')->where('OrganizationID',$id)->sum('WhitePaper');
      $data->ColorPaperSummary=(int)DB::table('Order')->where('OrganizationID',$id)->sum('ColorPaper');

      return $data;
    }

    public static function getAllOrganizationPaperSummary()
    {
      $data=[];
      $organizationList=OrganizationModel::getOrganizationList();

      foreach ($organizationList as $id => $name)
      {
        $data[$id]=self::getOrganizationPaperSummary($id);
        $data[$id]->OrganizationName=$name;
      }
      return $data;
    }

    public static function clearOrderHistory()
    {
      DB::table('Order')->truncate();
    }
}
