<?php

class Eclate
{

    public $minsupp;
    public $frequentitems;
    public $dataset;

    public function __construct($dataset, $minsupp)
    {

        $this->dataset = $dataset;
        $this->minsupp = $minsupp;
    }

    public function run()
    {
        $this->process($this->dataset);
        return $this->frequentitems;
    }

    private function process($dataset)
    {
        $itemTidsets = [];
        $itemPairCount = [];
        // Create vertical representation of the transactions and count item pairs.
        foreach ($dataset as $tid => $tx) {
            foreach ($tx as $k => $item1) {
                if (!array_key_exists($item1, $itemTidsets)) {
                    $itemTidsets[$item1] = [];
                }
                array_push($itemTidsets[$item1], $tid);

                foreach (array_slice($tx, $k + 1, count($tx)) as $item2) {

                    list($item1, $item2) = $this->MinMax($item1, $item2);

                    if (!array_key_exists($item1, $itemPairCount)) {
                        $itemPairCount += [
                            $item1 => [],
                        ];
                    }
                    if (!array_key_exists($item2, $itemPairCount[$item1])) {
                        $itemPairCount[$item1] += [
                            $item2 => 0,
                        ];
                    }
                    $itemPairCount[$item1][$item2] += 1;
                }
            }
            sort($tx);
        }


        $freqItemsets = [];
        $atoms = [];

        // Determine frequent 1-itemsets and insert them into the atom set.
        foreach ($itemTidsets as $item => $tidset) {
            if (count($tidset) >= $this->minsupp) {
                $itemset = [];
                array_push($itemset, $item);
                array_push($freqItemsets, $itemset);
                $atom = [
                    'itemset' => $itemset,
                    'tidset' => $tidset,
                ];
                array_push($atoms, $atom);
            }
        }



        // Sort the atoms by the order of increasing tidset size. This reduces the number of generated atoms.
        uksort($atoms, function ($a, $b) use ($atoms) {
            return count($atoms[$a]['tidset']) - count($atoms[$b]['tidset']);
        });


        for ($i = 0; $i < count($atoms); $i++) {
            $atom1 = $atoms[$i];
            $newAtoms = [];

            for ($j = $i + 1; $j < count($atoms); $j++) {
                $atom2 = $atoms[$j];

                list($item1, $item2) = $this->MinMax($atom1['itemset'][0], $atom2['itemset'][0]);

                if (array_key_exists($item1, $itemPairCount)) {
                    $counts = $itemPairCount[$item1];

                    if (array_key_exists($item2, $counts)) {
                        $count = $counts[$item2];

                        if ($count >= $this->minsupp) {
                            array_push($freqItemsets, [
                                $item1,
                                $item2,
                            ]);
                            array_push($newAtoms, [
                                'itemset' => [$item1, $item2],
                                'tidset' => array_intersect($atom1['tidset'], $atom2['tidset']),
                            ]);
                        }
                    }
                }
            }
            $adres = &$freqItemsets;
            $freqItemsets = $this->eclat($newAtoms, $this->minsupp, $adres);
        }

        foreach ($freqItemsets as $id => $inner) {
            sort($inner);
            $freqItemsets[$id] = $inner;
        }

        $this->frequentitems = $freqItemsets;
    }

    private function eclat($atoms, $min, $freqItemsets)
    {
        // Perform the eclat algorithm by recursively combining atoms to larger itemsets.
        foreach ($atoms as $k => $atom1) {
            $newAtoms = [];

            foreach (array_slice($atoms, $k + 1, count($atoms)) as $atom2) {

                $tidset = array_intersect($atom1['tidset'], $atom2['tidset']);
                if (count($tidset) >= $min) {
                    $itemset = array_unique(array_merge($atom1['itemset'], $atom2['itemset']), SORT_REGULAR);

                    array_push($freqItemsets, $itemset);
                    //$freqItemsets = append(*freqItemsets, itemset)
                    array_push($newAtoms, [
                        'itemset' => $itemset,
                        'tidset' => $tidset,
                    ]);
                }
            }

            $freqItemsets = $this->eclat($newAtoms, $min, $freqItemsets);
        }
        return $freqItemsets;
    }

    private function MinMax($a, $b)
    {

        if ($a < $b) {
            return [$a, $b];
        }
        return [$b, $a];
    }
}
