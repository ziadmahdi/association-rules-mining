<?php
require 'algorithms/eclat/eclat.php';
require 'algorithms/apriori/apriori.php';
require 'algorithms/fbgrowth/fbgrowth.php';
require 'algorithms/hmine/AlgoHMine.php';
require 'algorithms/indirect/indirectAlgo.php';

class DataMine
{
    public $support = 1;
    public $confidence = 0;
    public $ts = 0;
    public $path;
    public $dataset = [];
    public $duration = 0;
    public $assRulesDuration = 0;
    public $algorithm;
    // --- Association Rules
    public $table = [];
    public $allthings = [];
    public $keys = [];
    public $phase = 1;
    public $allsups = [];
    // --- /
    public $rules = [];
    public $frequentItemSets = [];
    public $memory = 0;

    public function __construct($path, $suparator, $support, $confidence, $ts, $algorithm)
    {
        $start = memory_get_peak_usage();
        if ($algorithm != 'Indirect') {

            foreach (file($path) as $t) {
                $row = explode($suparator, trim($t));
                sort($row);
                array_push($this->dataset, $row);
                unset($row);
            }
        } else
            $this->path = $path;
        echo $path;
        $this->support = (int) ceil($support * count($this->dataset));
        $this->confidence = $confidence;
        $this->ts = $ts;
        $this->algorithm = $algorithm;
        $this->mine();
        $this->memory = round((memory_get_peak_usage() - $start) / 1024);
    }

    public function mine()
    {
        $algo = [];

        $this->makeTable();

        switch ($this->algorithm) {
            case "Apriori":
                $algo = new Apriori($this->dataset, $this->support, $this->allthings, $this->allsups, $this->table);
                break;
            case "Eclate":
                $algo = new Eclate($this->dataset, $this->support);
                break;
            case "FPGrowth":
                $algo = new FPGrowth($this->dataset, $this->support);
                break;
            case "Hmine":
                $algo = new AlgoHMine($this->dataset, $this->support, 100);

                break;
            case "Indirect":

                $algo = new AlgoINDIRECT($this->path, $this->confidence, $this->support, $this->ts);

                break;
            default:
                $algo = new Apriori($this->dataset, $this->support, $this->allthings, $this->allsups, $this->table);
                break;
        }

        $start = microtime(true);
        $this->frequentItemSets = $algo->run();
        $this->duration = round((microtime(true) - $start) * 1000, 4);


        $start = microtime(true);
        $this->generateAssociationRules($this->frequentItemSets, $this->confidence);
        $this->assRulesDuration = round((microtime(true) - $start) * 1000, 4);
    }


    protected function generateAssociationRules()
    {
        $assRules = new AssociationRules(
            $this->frequentItemSets,
            $this->support,
            $this->confidence,
            $this->keys,
            $this->allsups,
            $this->table,
        );
        $this->rules = $assRules->process();
    }

    private function makeTable()
    {

        $table = $tmpDataset = array();
        $array   = array();
        $counter = 1;

        $num = count($this->dataset);
        for ($i = 0; $i < $num; $i++) {
            $tmp  = $this->dataset[$i];
            $num1 = count($tmp);
            $x    = array();
            for ($j = 0; $j < $num1; $j++) {
                $x = trim($tmp[$j]);
                if ($x === '') {
                    continue;
                }

                if (!isset($this->keys['v->k'][$x])) {
                    $this->keys['v->k'][$x]         = $counter;
                    $this->keys['k->v'][$counter]   = $x;
                    $counter++;
                }

                if (!isset($array[$this->keys['v->k'][$x]])) {
                    $array[$this->keys['v->k'][$x]] = 1;
                    $this->allsups[$this->keys['v->k'][$x]] = 1;
                } else {
                    $array[$this->keys['v->k'][$x]]++;
                    $this->allsups[$this->keys['v->k'][$x]]++;
                }

                $table[$i][$this->keys['v->k'][$x]] = 1;
            }
            $tmpDataset[$i] = array_keys($table[$i]);
        }

        $tmp = array();
        foreach ($array as $item => $sup) {
            if ($sup >= $this->support) {

                $tmp[] = array($item);
            }
        }

        $this->allthings[$this->phase] = $tmp;
        $this->dataset = $tmpDataset;
        $this->table = $table;
    }

    public function printRules()
    {
        $tmp = [];
        $result = '';

        foreach ($this->rules as $a => $rules) {
            foreach ($rules as $b => $confidence) {
                $tmp += [
                    $a . ' => ' . $b => $confidence,
                ];
            }
        }
        arsort($tmp);
        foreach ($tmp as $k => $c) {
            $result .= $k . ' = ' . $c . '%<br>';
        }
        return $result;
    }
    public function printFrequentItemSets()
    {
        $freq = '';
        foreach ($this->frequentItemSets as $items) {
            $freq .= '{';
            foreach ($items as $i => $item) {

                $freq .= $item;
                if (count($items) - 1 > $i) $freq .= ',';
            }
            $freq .= '}<br>';
        }
        return $freq;
    }
}
