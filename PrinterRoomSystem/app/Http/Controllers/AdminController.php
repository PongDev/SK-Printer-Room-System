<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserModel;
use App\OrganizationModel;
use App\OrderModel;
use Storage;
use PDF;

class AdminController extends Controller
{
    const HISTORY_LIMIT_PER_PAGE=20;

    public function __construct()
    {
      $this->middleware('loginAuth');
      $this->middleware('requireAdmin');
    }

    public function index()
    {
      return view('admin/admin',[
        'userData'=>UserModel::userData(),
        'userRank'=>UserModel::rankidtostr(UserModel::userData()->Rank),
        'rankList'=>UserModel::getRankList(),
        'organizationList'=>OrganizationModel::getOrganizationList(),
        'userDataList'=>self::getUserDataList(),
        'organizationDataList'=>self::getOrganizationDataList()
      ]);
    }

    public function history()
    {
      return view('admin/history',[
        'userData'=>UserModel::userData(),
        'userRank'=>UserModel::rankidtostr(UserModel::userData()->Rank),
        'rankList'=>UserModel::getRankList(),
        'organizationList'=>OrganizationModel::getOrganizationList(),
        'userDataList'=>self::getUserDataList(),
        'organizationDataList'=>self::getOrganizationDataList(),
        'usrFilter'=>request()->query('usrFilter')
      ]);
    }

    public function getJS($jsFileName)
    {
      switch($jsFileName)
      {
        case "app.js":
          return response(Storage::get('admin/js/app.js'),200)
                  ->header('Content-Type','application/javascript;charset=utf-8');
          break;
        case "dropdown.js":
          return response(Storage::get('admin/js/dropdown.js'),200)
                  ->header('Content-Type','application/javascript;charset=utf-8');
          break;
        case "history.js":
          return response(Storage::get('admin/js/history.js'),200)
                  ->header('Content-Type','application/javascript;charset=utf-8');
          break;
        case "ui.js";
          return response(Storage::get('admin/js/ui.js'),200)
                  ->header('Content-Type','application/javascript;charset=utf-8');
          break;
        case "utilities.js":
          return response(Storage::get('admin/js/utilities.js'),200)
                  ->header('Content-Type','application/javascript;charset=utf-8');
          break;
      }
      return redirect('/');
    }

    public function addUser()
    {
      if (ctype_digit(request()->post('rank'))===false)return "false";

      return UserModel::addUser(
        (string)request()->post('usr'),
        (string)request()->post('pwd'),
        (int)request()->post('rank'),
        (string)request()->post('displayname'),
        (array)request()->post('organization')
      )?"true":"false";
    }

    public function editUser()
    {
      if (ctype_digit(request()->post('rank'))===false)return "false";

      return UserModel::updateUser(
        (string)request()->post('usr'),
        (string)request()->post('pwd'),
        (int)request()->post('rank'),
        (string)request()->post('displayname'),
        (array)request()->post('organization')
      )?"true":"false";
    }

    public function deleteUser()
    {
      return UserModel::deleteUser(request()->post('usr'))?"true":"false";
    }

    public function getUserDataList()
    {
      return array_map(function($data){
        unset($data->Password);
        unset($data->Create_Time);
        unset($data->Last_Login);
        $data->OrganizationIDList=json_decode($data->OrganizationIDList);
        $userPaperSummary=OrderModel::getUserPaperSummary($data->id);
        $data->BrownPaper=$userPaperSummary->BrownPaper;
        $data->WhitePaper=$userPaperSummary->WhitePaper;
        $data->ColorPaper=$userPaperSummary->ColorPaper;
        return $data;
      },UserModel::getUserDataList()->toArray());
    }

    public function getOrganizationDataListFromID($id)
    {
      $organizationPaperSummary=OrderModel::getOrganizationPaperSummary($id);
      $data=new \stdClass;
      $data->id=$id;
      $data->Name=OrganizationModel::getOrganizationFromID($id);

      $data->WorkType1BrownPaper=$organizationPaperSummary->WorkType1BrownPaper;
      $data->WorkType1WhitePaper=$organizationPaperSummary->WorkType1WhitePaper;
      $data->WorkType1ColorPaper=$organizationPaperSummary->WorkType1ColorPaper;

      $data->WorkType2BrownPaper=$organizationPaperSummary->WorkType2BrownPaper;
      $data->WorkType2WhitePaper=$organizationPaperSummary->WorkType2WhitePaper;
      $data->WorkType2ColorPaper=$organizationPaperSummary->WorkType2ColorPaper;

      $data->WorkType3BrownPaper=$organizationPaperSummary->WorkType3BrownPaper;
      $data->WorkType3WhitePaper=$organizationPaperSummary->WorkType3WhitePaper;
      $data->WorkType3ColorPaper=$organizationPaperSummary->WorkType3ColorPaper;

      $data->BrownPaperSummary=$organizationPaperSummary->BrownPaperSummary;
      $data->WhitePaperSummary=$organizationPaperSummary->WhitePaperSummary;
      $data->ColorPaperSummary=$organizationPaperSummary->ColorPaperSummary;
      return $data;
    }

    public function getOrganizationDataList()
    {
      $organizationList=OrganizationModel::getOrganizationList();

      return array_map(function($key,$value){
        return self::getOrganizationDataListFromID($key);
      },array_keys($organizationList),$organizationList);
    }

    public function getHistory()
    {
      return OrderModel::getAllOrderHistory(
        (string)request()->post("User"),
        (string)request()->post("Display"),
        (array)request()->post("Organize"),
        (array)request()->post("WorkType"),
        (int)request()->post("OrderBy"),
        (bool)request()->post("SortAsc"),
        self::HISTORY_LIMIT_PER_PAGE,
        (int)request()->post("Page"));
    }

    public function getHistoryMaxPage()
    {
      return ceil(OrderModel::countAllOrderHistory(
        (string)request()->post("User"),
        (string)request()->post("Display"),
        (array)request()->post("Organize"),
        (array)request()->post("WorkType"))/self::HISTORY_LIMIT_PER_PAGE);
    }

    public function getOrganizationReport()
    {
      $id=intval(request()->post("id"));
      if (!OrganizationModel::isOrganizationIDExist($id))return "false";
      session()->forget('organizationReportPDF');
      session(['organizationReportPDF'=>PDF::loadView('admin/organizationReportForm',[
        'organizationName'=>OrganizationModel::getOrganizationFromID($id),
        'organizationDataList'=>self::getOrganizationDataListFromID($id)
        ])->stream()]);
      return "true";
    }

    public function organizationReport()
    {
      if (session()->exists('organizationReportPDF'))
      {
        $organizationReportPDF=session('organizationReportPDF');
        session()->forget('organizationReportPDF');
        return $organizationReportPDF;
      }
      else return redirect("/");
    }

    public function clearOrderHistory()
    {
      OrderModel::clearOrderHistory();
    }
}
