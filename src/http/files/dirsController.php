<?php
namespace Small\http\files;

use Small\http\HttpController;
use Small\model\models\FilesModel;

class dirsController extends HttpController{

    public function index(...$args)
    {
        // TODO: Implement index() method.
        $year = $this->getQueryString('year');
        $year = is_numeric($year) && $year>0 && $year<=date("Y", time()) ? intval($year) : 0;
        if($year!=0){
            $month = $this->getQueryString("month");
            $month = is_numeric($month) && $month>0 && $month<=12 ? intval($month) : 0;
        }

        $m = new FilesModel();
        $db = $m->mainQuery();
        if(!empty($year)){
            if(!empty($month)){
                //查询的是月份
                $db->select("count(`id`) as rows, day(`time`) as number, {$year} as year, {$month} as month, day(`time`) as day")
                    ->whereRaw("year(`time`)={$year}")
                    ->whereRaw("month(`time`)={$month}")
                    ->groupBy("day(`time`)")
                    ->orderBy("day(`time`)", "asc");
            }else{
                //查询的是月份
                $db->select("count(`id`) as rows, month(`time`) as number, {$year} as year, month(`time`) as month, 0 as day")
                    ->whereRaw("year(`time`)={$year}")
                    ->groupBy("month(`time`)")
                    ->orderBy("month(`time`)", "asc");
            }
        }else{
            //查询的是年份
            $db->select("count(`id`) as rows, year(`time`) as number, year(`time`) as year, 0 as month, 0 as day")
                ->groupBy("year(`time`)")
                ->orderBy("year(`time`)", "asc");
        }
        if($this->user['uid']>0){
            $db->where("uid", $this->user['uid']);
        }elseif(property_exists($this, "uid")){
            $db->where("uid", $this->uid);
        }
        //输出JSON，仅查询自己账号上传的
        $this->response($db->get());
    }
}