<?php

include 'AbstractOrderedItemset.php';
class Itemset extends AbstractOrderedItemset
{


    public $itemset = array();
    public $transactionsIds = array();

    function  __construct($item)
    {
        if (is_array($item))
            $this->itemset = $item;
        else
            array_push($this->itemset, $item);
    }

    function getAbsoluteSupport()
    {
        return count($this->transactionsIds);
    }

    function getItems()
    {
        return $this->itemset;
    }

    function get($index)
    {
        return $this->itemset[$index];
    }

    function setTIDs($listTransactionIds)
    {
        $this->transactionsIds = $listTransactionIds;
    }

    function size()
    {
        return count($this->itemset);
    }

    function getTransactionsIds()
    {
        return $this->transactionsIds;
    }

    function cloneItemSetMinusAnItemset($itemsetToNotKeep)
    {
        // create a new itemset
        $newItemset = array();
        $i = 0;
        // for each item of this itemset
        for ($j = 0; $j < count($this->itemset); $j++) {
            if ($itemsetToNotKeep->contains($this->itemset[$j]) == false)
                // copy the item except if it is not an item that should be excluded
                $newItemset[$i++] = $this->itemset[$j];
        }
        return new Itemset($newItemset);
    }

    function cloneItemSetMinusOneItem($itemsetToRemove)
    {
        // create the new itemset
        $newItemset = array();
        $i = 0;
        // for each item in this itemset
        for ($j = 0; $j < count($this->itemset); $j++) {
            // copy the item except if it is the item that should be excluded
            if ($this->itemset[$j] != $itemsetToRemove) {
                $newItemset[$i++] = $this->itemset[$j];
            }
        }
        return new Itemset($newItemset);
    }
}
