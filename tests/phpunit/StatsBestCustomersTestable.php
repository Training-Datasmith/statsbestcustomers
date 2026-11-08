<?php

class StatsBestCustomersTestable extends statsbestcustomers
{
    const GRID_ENGINE_SENTINEL = 'GRID_ENGINE_SENTINEL';

    public static $engineCalls = array();
    public static $csvExportCalls = array();

    public static function resetRecording()
    {
        self::$engineCalls = array();
        self::$csvExportCalls = array();
    }

    public function engine($params)
    {
        self::$engineCalls[] = $params;

        return self::GRID_ENGINE_SENTINEL;
    }

    protected function csvExport($datas)
    {
        self::$csvExportCalls[] = $datas;
    }
}
