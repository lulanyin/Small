<?php
namespace Small\http\files;

use Small\http\HttpController;
use Small\model\models\FilesModel;

class indexController extends HttpController{

    public function index(...$args)
    {
        // TODO: Implement index() method.
        $m = new FilesModel();
        //仅查询自己的文件
        $db = $m->mainQuery();
        if($this->user['uid']>0){
            $db->where("uid", $this->user['uid']);
        }elseif(property_exists($this, "uid")){
            $db->where("uid", $this->uid);
        }
        //文件管理器
        $year = $this->getQueryString("year");
        $year = is_numeric($year) && $year>0 && $year<=date("Y", time()) ? intval($year) : 0;
        if($year>0){
            $db->whereRaw("year(`time`)={$year}");
            $month = $this->getQueryString("month");
            $month = is_numeric($month) && $month>0 && $month<=12 ? intval($month) : 0;
            if($month>0){
                $db->whereRaw("month(`time`)={$month}");
                $day = $this->getQueryString("day");
                $day = is_numeric($day) && $day>0 && $day<=31 ? intval($day) : 0;
                if($day>0){
                    $db->whereRaw("day(`time`)={$day}");
                }
            }
            $page = $this->getQueryString("page", 1);
            $page = is_numeric($page) && $page>0 ? $page : 1;
            $rows = $db->rows();
            if($rows>0){
                $total = 20;
                $pages = ceil($rows/$total);
                $offset = $total*($page-1);
                $list = $db->orderBy("`time`", "desc")->select("`name`, `url`, `file_hash`, `time`")->get($total, $offset);
                foreach ($list as &$item){
                    $item['src'] = attachmentUrlRebuild($item['url']);
                    $item['value'] = $item['url'];
                    unset($item['url']);
                }
                $this->response([
                    'rows'  => $rows,
                    'page'  => intval($page),
                    'pages' => intval($pages),
                    'list'  => $list
                ]);
            }
        }
        $this->response([
            'rows'  => 0,
            'page'  => 1,
            'pages' => 0,
            'list'  => []
        ]);
    }
}