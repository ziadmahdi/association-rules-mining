<?php

include 'tree.php';
class FPGrowth
{
    public $support = 0;
    private $dataset;



    public function __construct($dataset, $support)
    {
        $this->support = $support;

        $this->dataset = $dataset;
    }
    public function run()
    {
        return $this->patterns = $this->toFrequentItemset($this->findFrequentPatterns($this->dataset, $this->support));
    }

    private function findFrequentPatterns($transactions, $support_threshold)
    {
        $tree = new FPTree($transactions, $support_threshold, null, null);
        return $tree->minePatterns($support_threshold);
    }
    private function toFrequentItemset($array)
    {

        $freq = [];
        $i = 0;
        foreach ($array as $key => $_) {

            $items = explode(",", $key);
            if (!array_key_exists($i, $freq)) {
                $freq += [
                    $i => []
                ];
            }
            foreach ($items as $item) {
                array_push($freq[$i], $item);
            }
            $i++;
        }

        return $freq;
    }
}
