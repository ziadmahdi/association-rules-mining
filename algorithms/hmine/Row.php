<?php

class Row
{
    /** the item **/
    public $item;
    /** its support **/
    public $support = 1;
    /** the list of pointers to items in transactions */
    public $pointers = array();

    public function __construct($item)
    {
        $this->item = $item;
    }

 
    public function toString()
    {
        $temp = $this->item + " s:" + $this->support + " pointers: " + $this->pointers;
        return $temp;
    }
}
