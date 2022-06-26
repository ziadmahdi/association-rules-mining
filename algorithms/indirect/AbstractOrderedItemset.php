<?php


include 'AbstractItemset.php';
abstract class AbstractOrderedItemset extends AbstractItemset
{
    
    abstract function getAbsoluteSupport();
   
    abstract function size();
   
    abstract function get($position);
   
    function getLastItem()
    {
        return $this->get($this->size() - 1);
    }

    function toString()
    {
        if ($this->size() == 0) {
            return "EMPTYSET";
        }
        // use a string buffer for more efficiency
        $r = "";
        // for each item, append it to the StringBuilder
        for ($i = 0; $i < $this->size(); $i++) {
            $r .= $this->get($i);
            $r .= ' ';
        }
        return $r;
    }
    
    function getRelativeSupport($nbObject)
    {
        // Divide the absolute support by the number of transactions to get the relative support
        return ((float)$this->getAbsoluteSupport()) / ((float)$nbObject);
    }
    
    function contains($item)
    {
        for ($i = 0; $i < $this->size(); $i++) {
            if ($this->get($i) == $item) {
                return true;
            } else if ($this->get($i) > $item) {
                return false;
            }
        }
        return false;
    }
    
    function containsAll($itemset2)
    {
        // first we check the size
        if ($this->size() < $itemset2->size()) {
            return false;
        }
        // we will use this variable to remember where we are in this itemset
        $i = 0;
        // for each item in itemset2, we will try to find it in this itemset
        for ($j = 0; $j < $itemset2->size(); $j++) {
            $found = false;
            // flag to remember if we have find the item at position j
            // we search in this itemset starting from the current position i
            while ($found == false && $i < $this->size()) {
                // if we found the current item from itemset2, we stop searching
                if ($this->get($i) == $itemset2->get($j)) {
                    $found = true;
                } else if ($this->get($i) > $itemset2->get($j)) {
                    return false;
                }
                $i++;
            }
            // if the item was not found in the previous loop, return false
            if (!$found) {
                return false;
            }
        }
        return true;
    }
    
    function isEqualTo($itemset2)
    {
        // If they don't contain the same number of items, we return false
        if ($this->size() != $itemset2->size()) {
            return false;
        }
        // We compare each item one by one from i to size - 1.
        for ($i = 0; $i < $itemset2->size(); $i++) {
            // if different, return false
            if (!$itemset2->get($i) == $this->get($i)) {
                return false;
            }
        }
        // All the items are the same, we return true.
        return true;
    }
   
    function isEqualToArray($itemsetArray)
    {
        // If they don't contain the same number of items, we return false
        if ($this->size() != count($itemsetArray)) {
            return false;
        }
        // We compare each item one by one from i to size - 1.
        for ($i = 0; $i < count($itemsetArray); $i++) {
            // if different, return false
            if ($itemsetArray[$i] != $this->get($i)) {
                return false;
            }
        }
        // All the items are the same, we return true.
        return true;
    }
    
    function allTheSameExceptLastItemV2($itemset2)
    {
        // if they don't contain the same number of item, return false
        if ($itemset2->size() != $this->size()) {
            return false;
        }
        // Otherwise, we have to compare item by item
        for ($i = 0; $i < $this->size() - 1; $i++) {
            // if they are not the last items, they should be the same
            // otherwise return false
            if (!$this->get($i) == $itemset2->get($i)) {
                return false;
            }
        }
        // All items are the same. We return true.
        return true;
    }
    
    function allTheSameExceptLastItem($itemset2)
    {
        // if these itemsets do not have the same size,  return null
        if ($itemset2->size() != $this->size()) {
            return NULL;
        }
        // We will compare all items one by one starting from position i =0 to size -1
        for ($i = 0; $i < $this->size(); $i++) {
            // if this is the last position
            if ($i == $this->size() - 1) {
                // We check if the item from this itemset is be smaller (lexical order)
                // and different from the one of itemset2.
                // If not, return null.
                if ($this->get($i) >= $itemset2->get($i)) {
                    return NULL;
                }
            } else if (!$this->get($i) == $itemset2->get($i)) {
                // if not, return null
                return NULL;
            }
        }
        // otherwise, we return the position of the last item
        return $itemset2->get($itemset2->size() - 1);
    }
}
