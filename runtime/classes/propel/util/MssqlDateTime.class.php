<?php
class MssqlDateTime extends DateTime
{
  public function __construct($datetime='now', DateTimeZone $tz = null)
  {
    //if the date is bad account for Mssql datetime format
    if ($datetime != 'now' && strtotime($datetime) === false)
    {
      $datetime = substr($datetime,0, -6).substr($datetime,-2);
    }

    if($tz instanceof DateTimeZone)
    {
      parent::__construct($datetime,$tz);
    }
    else
    {
      parent::__construct($datetime);
    }
  }
}