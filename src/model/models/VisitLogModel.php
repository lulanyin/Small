<?php
namespace Small\model\models{

    use Small\model\ModelBuilder;

    class VisitLogModel extends ModelBuilder{
        //主表名
        protected $tableName = "visit_log";

        public static function saveLog($step = "start"){
            return;
            $path = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : "";
            $path = empty($path) ? ($_SERVER['REQUEST_URI'] ?? "") : $path;
            $url = $path;
            $url = strlen($url)>255 ? substr($url, 0, 255) : $url;
            $data = [
                "url"       => $url,
                "ip"        => getIP(),
                "server"    => var_export($_SERVER, true),
                "step"      => $step
            ];
            $m = new VisitLogModel();
            $m->mainQuery()
                ->insert($data);
        }
    }
}