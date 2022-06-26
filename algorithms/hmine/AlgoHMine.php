<?php

include 'ItemNameConverter.php';
include 'Row.php';

class AlgoHMine
{
    /** the number of patterns generated */
    public $patternCount = 0;
    public $dataset = [];

    private $itemsetBuffer = [];
    public $frequentitems = [];

    public $cells = array();

    /** the minSupport threshold **/
    public $minSupport = 0;

    private $mapItemRow;
    public $nameConverter;

    /** Special parameter to set the maximum size of itemsets to be discovered */
    public $maxItemsetSize = 2147483647;

    public function __construct($dataset, $support, $length)
    {

        $this->minSupport = $support;
        $this->dataset = $dataset;
        $this->maxItemsetSize = $length;
    }

    public function run()
    {
        $dataset = $this->dataset;

        // initialize the buffer for storing the current itemset
        $this->itemsetBuffer = [];

        // reset memory logger
        // MemoryLogger.getInstance().reset();

        // record the start time of the algorithm


        $mapItemToSupport = array();

        // this variable will count the number of item occurence in the database
        $itemOccurrencesCount = 0;
        // this variable will count the number of transactions


        if ($this->maxItemsetSize >= 1) {

            foreach ($dataset as $list) {

                foreach ($list as $item) {

                    if (!array_key_exists($item, $mapItemToSupport))
                        $mapItemToSupport += [$item => 1];
                    else
                        $mapItemToSupport[$item] =  $mapItemToSupport[$item] + 1;
                }
            }
        }

        // Create a list of table rows for the initial HStructure
        $rowList = array();
        $mapItemRow = array();

        $this->cells = array();

        foreach ($mapItemToSupport as $item => $support) {

            if ($support >= $this->minSupport) {
                // create a row for this item and add it to the HStruct table
                $rowItem = new Row($item);
                $rowItem->support = $support;  // set its support (a.k.a ubItem value)
                array_push($rowList, $rowItem); // add the row to the list of row
                $mapItemRow += [$item => $rowItem];
            }
        }

            // $this->lexicographicalOrder($rowList);
        ;
        usort($rowList, function ($a, $b) use ($mapItemToSupport, $rowList) {
            $tmp = $mapItemToSupport[$a->item] - $mapItemToSupport[$b->item];
            return ($tmp == 0) ? $a->item - $b->item : $tmp;
        });
        // We rename the items according to the lexicographical order.
        // This is an optimization that will allow us very fast comparison
        // of items according to the total order.
        $this->nameConverter = new ItemNameConverter();
        // for each item



        foreach ($rowList as $row) {
            // we rename the item with a new name
            
            $row->item = $this->nameConverter->assignNewName($row->item);
        echo $row->item;}


        if ($this->maxItemsetSize >= 1) {

        
            $this->cells[0] = -1;
            // This variable is the current insertion position in the cell array
            // We start at 0
            $currentCellIndex = 0;


            foreach ($dataset as $list) {
                $transactionBegin = $currentCellIndex;
                foreach ($list as $item) {
                    if ($mapItemToSupport[$item] >= $this->minSupport) {
                        // add it to the current transaction in the list
                        // of transactions, where each item is represented by a cell

                        $this->cells[$currentCellIndex++] = $this->nameConverter->toNewName($item);
                    }
                }


                // record the position of the last item of the current transaction
                // in the cell array
                $transactionEnd = $currentCellIndex - 1;

                // sort the transaction by ascending order of support

                $this->sort($this->cells, $transactionBegin, $transactionEnd + 1);

                // insert a -1 after the transaction in the cell array to
                // separate it from the next transaction
                $this->cells[$currentCellIndex++] = -1;

                //print_r($this->cells);
                // for each item left in the transaction
                // we will update its row in the HStruct table
                for ($i = $transactionBegin; $i <= $transactionEnd; $i++) {

                    //foreach ($this->cells as $i => $item) {
                    $item = $this->cells[$i];
                    // get the row of this item in the current HStruct table
                    $row = $mapItemRow[$this->nameConverter->toOldName($item)];

                    // add the pointer to the list of pointers in the HStruct for this item
                    array_push($row->pointers, $i);
                }
            }
            //array_push($this->cells,0);
            //print_r($this->cells);

        }

        if ($this->maxItemsetSize >= 1) {

            $this->hmine($this->itemsetBuffer, 0, $rowList);
        }
        return $this->frequentitems;
    }

    private function hmine($prefix, $prefixLength, $rowList)
    {


        foreach ($rowList as $row) {

            // create the new projected row list 
            $newRowList = array();
            $this->mapItemRow  = array();
            foreach ($row->pointers as $pointer) {
                $transactionBegin = $pointer;

                // if there is nothing after the item, we don't need
                // to create a new row
                $transactionBegin++;
                //echo '------------->'.$this->cells[$transactionBegin].'<br>';
                if ($this->cells[$transactionBegin] == -1) {
                    continue;
                }
                //print_r($this->mapItemRow);

                // find the end of the transaction
                // and calculate the reamining support
                $transactionEnd = -1;
                for ($pos = $transactionBegin;; $pos++) {
                    if ($this->cells[$pos] == -1) {
                        $transactionEnd = $pos - 1;
                        break;
                    }
                }

                // otherwise, we create the projected row
                // For each item in the transaction

                for ($pos = $transactionBegin; $pos <= $transactionEnd; $pos++) {
                    $item = $this->cells[$pos];
                    $rowItem = null;
                    //if ($rowItem == null) {
                    if (!array_key_exists($item, $this->mapItemRow)) {
                        $rowItem = new Row($item);

                        $this->mapItemRow += [$item => $rowItem];
                    } else {
                        $rowItem = $this->mapItemRow[$item];
                    }

                    $rowItem->support++;

                    // add new pointer
                    array_push($rowItem->pointers, $pos);
                }
            }

            // add all the promising row and sort them
            foreach ($this->mapItemRow as $currentRow) {
                if ($currentRow->support >= $this->minSupport) {
                    array_push($newRowList, $currentRow);
                }
            }

            // output this itemset
            $this->writeOut($this->itemsetBuffer, $prefixLength, $row->item, $row->support);

            if (count($newRowList) != 0) {

                usort($newRowList, function ($a, $b) {
                    if (is_numeric($a) && is_numeric($b)) {
                        return $a['item'] - $b['item'];
                    }
                    return $a->item - $b->item;
                });
                $this->itemsetBuffer[$prefixLength] = $row->item;

                // Recursive call to mine larger itemsets using the new prefix
                if ($prefixLength + 2 <= $this->maxItemsetSize) {
                    $this->hmine($this->itemsetBuffer, $prefixLength + 1, $newRowList);
                }
            }
        }
    }

    private function writeOut($prefix, $prefixLength, $item, $support)
    {

        $this->patternCount++; // increase the number of high support itemsets found
        $tmp = [];

        for ($i = 0; $i < $prefixLength; $i++) {
            array_push($tmp, $this->nameConverter->toOldName($prefix[$i]));
        }
        array_push($tmp, $this->nameConverter->toOldName($item));

        array_push($this->frequentitems, $tmp);
    }


    private function sort(&$array, $from, $to)
    {
        $slice = array_slice($array, $from, $to - $from);
        sort($slice);
        $index = 0;
        for ($i = 0; $i < count($array); $i++) {
            if ($i >= $from && $i < $to) {
                $array[$i] = $slice[$index];
                $index++;
            }
        }
    }
}
