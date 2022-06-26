<?php



include 'Itemset.php';

class AlgoINDIRECT
{
    // variables for the tid (transaction ids) set of items
    public $mapItemTIDS = array();

    // Parameters
    public $minSuppRelative;
    public $minconf = 0;
    public $tsRelative = 0;

    // the size of the database
    private $tidcount = 0;
    public $dataset = [];

    public function __construct($path, $minconf, $minsupp, $ts)
    {
        echo $path;
        $file = file($path);
        foreach ($file as $row) {
            $tmp = explode(' ', trim($row));
            array_push($this->dataset, $tmp);
        }
        $file = null;
        $this->minconf = $minconf;
        $this->minSuppRelative = $minsupp;
        $this->tsRelative = $ts;
    }

    private function lexicographicalOrder(&$array)
    {
        $sorted = [];
        $tmp = [];

        foreach ($array as $k => $r) {
            sort($array[$k]);
        }
        foreach ($array as $k => $r) {
            sort($r);
            $aa = '';
            foreach ($r as $n) {
                $aa .= sprintf('%06d', $n) . ' ';
            }
            $tmp += [
                $k => $aa,
            ];
        }
        asort($tmp);

        foreach ($tmp as $k => $r) {
            array_push($sorted, $array[$k]);
        }

        $array = $sorted;
    }

    public function  run()
    {
        $input = $this->dataset;
        $start = microtime(true);
        // save minconf


        $this->lexicographicalOrder($input);

        //exit();

        foreach ($input as  $value) {
            foreach ($value as  $item) {
                // get the current tids set of this item

                // if no set, create a new one
                if (!array_key_exists($item, $this->mapItemTIDS)) {
                    $tids = [];
                    $this->mapItemTIDS += [
                        $item => $tids,
                    ];
                }
                // add the current transaction id to the set
                array_push($this->mapItemTIDS[$item], $this->tidcount);
            }
            $this->tidcount++;
        }

        $this->minSuppRelative = (int) ceil($this->minSuppRelative * $this->tidcount);
        $this->tsRelative = (int) ceil($this->tsRelative * $this->tidcount);

        // This algorithm use an Apriori-style generation (level by level)
        // To build level 1, we keep only the frequent items.
        $k = 1;
        // create the variable to store itemset from level 1
        $level =  array();
        // For each item
        foreach ($this->mapItemTIDS as $item => $value) {
            // If the current item is frequent
            if ($value >= $this->minSuppRelative) {
                // add the item to this level
                $itemset = new Itemset($item);
                $itemset->setTIDs($value);
                array_push($level, $itemset);
            } else {
                // otherwise the item is not frequent we don't 
                // need to keep it into memory.
                array_slice($this->mapItemTIDS, $item, $item);
            }
        }

        // Sort itemsets of size 1 according to lexicographical order.



        // Now we recursively find larger itemset to generate rules
        // starting from k = 2 and until there is no more candidates.
        $k = 2;
        while (count($level) != 0) {
            // We build the level k+1 with all the candidates that have
            // a support higher than the minsup threshold.
            $level = $this->generateCandidateSizeK($level, $k); // We keep only the last level... 
            $k++;
        }
        echo '<br><br>Duration: ' . ((microtime(true) - $start) * 1000) . ' ms<br>';
    }

    private function generateCandidateSizeK($levelK_1, $level)
    {
        // create an empty list to store the candidate
        $nextLevel = array();
        // For each itemset I1 and I2 of level k-1
        for ($i = 0; $i < count($levelK_1); $i++) {
            $itemset1 = $levelK_1[$i];
            for ($j = $i + 1; $j < count($levelK_1); $j++) {
                $itemset2 = $levelK_1[$j];


                // we compare items of itemset1  and itemset2.
                // If they have all the same k-1 items and the last item of itemset1 is smaller than
                // the last item of itemset2, we will combine them to generate a candidate
                for ($k = 0; $k < $itemset1->size(); $k++) {
                    // if they are the last items
                    if ($k == $itemset1->size() - 1) {
                        // the one from itemset1 should be smaller (lexical order) 
                        // and different from the one of itemset2

                        if ($itemset1->getItems()[$k] >= $itemset2->get($k)) {
                            continue 2;
                        }
                    }
                    // if they are not the last items, and 

                    else if ($itemset1->getItems()[$k] < $itemset2->get($k)) {
                        continue 1; // we continue searching
                    } else if ($itemset1->getItems()[$k] > $itemset2->get($k)) {
                        continue 2;  // we stop searching:  because of lexical order
                    }
                }
                // =======   GENERATE ITEMSETS OF NEXT LEVEL AS IN APRIORI ======================
                $list = array();
                foreach ($itemset1->getTransactionsIds() as  $val1) {
                    if (in_array($val1, $itemset2->getTransactionsIds())) {
                        array_push($list, $val1);
                    }
                }


                if (count($list) >= $this->minSuppRelative) {
                    // Create a new candidate by combining 
                    // itemset1 and itemset2
                    //$newItemset[] = [$itemset1->size()+1];

                    $newItemset = $itemset1->itemset;
                    $newItemset[$itemset1->size()] = $itemset2->getItems()[$itemset2->size() - 1];
                    $candidate = new Itemset($newItemset);
                    $candidate->setTIDs($list);

                    // add the candidate to the set of candidate
                    array_push($nextLevel, $candidate);
                }
            }
        }

        if ($level > 2) {
            // WE NEED TO FIND TWO IEMSETS WITH ONLY TWO ITEMS a,b THAT ARE DIFFERENT
            // SO WE COMPARE EACH ITEMSET OF SIZE K WITH EACH OTHER ITEMSET OF SIZE K.
            for ($i = 0; $i < count($levelK_1); $i++) {
                for ($j = $i + 1; $j < count($levelK_1); $j++) {
                    $candidate1 = $levelK_1[$i];
                    $candidate2 = $levelK_1[$j];

                    // We check if the pair of itemset have only one item that is different.
                    foreach ($candidate1->getItems() as $a) {


                        // if candidate2 does not contain item a
                        if ($candidate2->contains($a) == false) {
                            $b = null;
                            // for each item of candidate 2
                            foreach ($candidate2->getItems() as $itemM) {
                                // if candidate1 does not contain that item
                                if ($candidate1->contains($itemM) == false) {
                                    if ($b != null) {
                                        continue 2;  // more than two items are different... we don't want that.
                                    }
                                    $b = $itemM;  // the item that is different
                                }
                            }
                            // if there is only one item that is different, then we call this method
                            // to check if we can create an indirect rule such that it would meet the
                            // ts threshold and the minconf threshold.
                            $this->getIndirectRule($candidate1, $a, $b);
                        }
                    }
                }
            }
        }
        return $nextLevel;
    }

    private function getIndirectRule($itemset, $a, $b)
    {
        // These sets are respectively the sets of ids
        // of transactions containing "a" and "b"
        $tidsA = $this->mapItemTIDS[$a];
        $tidsB = $this->mapItemTIDS[$b];
        // Calculate the support of {a,b} by doing
        // the intersection of these two sets.

        $supportAB = 0; // variable to count the number of IDs in that intersection
        // for each ID in tidFromA
        foreach ($tidsA as $tidFromA) {
            // if it appears in tidsB
            if (in_array($tidFromA, $tidsB)) {
                // increase the number of IDs shared by both
                $supportAB++;
            }
        }

        // if the support of {a,b} is lower than the "ts" threshold.
        if ($supportAB < $this->tsRelative) {
            // compute the support of Y U {a}
            $supAY = 0;
            // for each tid of transactions containing "a"
            foreach ($tidsA as $tidA) {
                // for each item in "itemset"
                foreach ($itemset->getItems() as $item) {
                    // if this item is not "a" and not "b"
                    if (!($item == $a) && !($item == $b)) {
                        // if this item appears in a transaction containing "a"
                        if (!in_array($tidA, $this->mapItemTIDS[$item])) {
                            continue 2;
                        }
                    }
                }
                $supAY++; // increase the support of Y U {a}
            }

            // Calculate the confidence of Y U {a}
            $confAY = $supAY / count($tidsA);

            // if the confidence is high enough
            if ($confAY >= $this->minconf) {
                // We do the same thing....
                // This time we compute the support of Y U {b}

                // variable to calculate the support of Y U {b}
                $supBY = 0;
                // for each tid of transactions containing "b"
                foreach ($tidsB as $tidB) {
                    // for each item in "itemset"
                    foreach ($itemset->getItems() as $item) {
                        // if this item is not "a" and not "b"
                        if (!($item == $a) && !($item == $b)) {
                            // if this item appears in a transaction containing "a"
                            if (!in_array($tidB, $this->mapItemTIDS[$item])) {
                                continue 2;
                            }
                        }
                    }
                    $supBY++; // increase the support of Y U {b}
                }
                // Calculate the confidence of Y U {b}
                $confBY = $supBY / count($tidsB);

                // if the confidence is high enough
                if ($confBY >= $this->minconf) {
                    // save the rule
                    $this->saveRule($a, $b, $itemset, $confAY, $confBY, $supAY, $supBY);
                }
            }
        }
    }

    private function saveRule($a, $b, $itemset, $confAY, $confBY, $supAY, $supBY)
    {

        echo "(a= " . $a . " b= " . $b . " | mediator= ";
        for ($i = 0; $i < $itemset->size(); $i++) {
            if (($itemset->get($i) != $a) && ($itemset->get($i) != $b)) {
                echo $itemset->get($i);
                echo " )";
            }
        }
        echo " #sup(a,mediator)= " . $supAY . " #sup(b,mediator)= " . $supBY . " #conf(a,mediator)= " . $confAY . " #conf(b,mediator)= " . $confBY;
        echo '<br>';
    }
}
