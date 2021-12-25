<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\UserModel;
use App\OrderModel;

class MainController extends Controller
{
    public function __construct()
    {
      $this->middleware('loginAuth');
    }

    public function index()
    {
      if (OrderModel::isLastOrderFailed())
      {
        OrderModel::clearLastOrderFailed();
        return view('index',[
          'userData'=>UserModel::userData(),
          'userRank'=>UserModel::rankidtostr(UserModel::userData()->Rank),
          'organizationList'=>UserModel::getOrganizationList(),
          'openPDF'=>OrderModel::isOrderReportPDF(),
          'formError'=>'Invalid Input'
        ]);
      }
      else return view('index',[
        'userData'=>UserModel::userData(),
        'userRank'=>UserModel::rankidtostr(UserModel::userData()->Rank),
        'organizationList'=>UserModel::getOrganizationList(),
        'openPDF'=>OrderModel::isOrderReportPDF()
      ]);
    }

    public function getAccountSetting()
    {
      return view('accountSetting',[
        'userData'=>UserModel::userData(),
        'userRank'=>UserModel::rankidtostr(UserModel::userData()->Rank)
      ]);
    }

    public function getContact()
    {
      return view('contact',[
        'userData'=>UserModel::userData(),
        'userRank'=>UserModel::rankidtostr(UserModel::userData()->Rank)
      ]);
    }

    public function submitForm()
    {
      $orderData['OrganizationIDIndex'] = request()->post('org');
      $orderData['WorkType']            = request()->post('work');
      $orderData['brownPageOrigin']     = request()->post('brownPageOrigin');
      $orderData['brownCopy']           = request()->post('brownCopy');
      $orderData['brownPagePerPaper']   = request()->post('brownPagePerPaper');
      $orderData['whitePageOrigin']     = request()->post('whitePageOrigin');
      $orderData['whiteCopy']           = request()->post('whiteCopy');
      $orderData['whitePagePerPaper']   = request()->post('whitePagePerPaper');
      $orderData['colorPageOrigin']     = request()->post('colorPageOrigin');
      $orderData['colorCopy']           = request()->post('colorCopy');
      $orderData['colorPagePerPaper']   = request()->post('colorPagePerPaper');
      OrderModel::addOrder($orderData);
      return redirect('/');
    }

    public function getReport()
    {
      if (OrderModel::isOrderReportPDF())return OrderModel::getOrderReportPDF();
      else return redirect('/');
    }

    public function changePassword()
    {
      return UserModel::changePassword(
        request()->post('oldpwd'),
        request()->post('newpwd')
      )?"true":"false";
    }
}
