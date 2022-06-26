<?php


class ItemNameConverter
{

    public $newNamesToOldNames;
    public $oldNamesToNewNames;

    /** this variable is the next new name that will be given*/
    public $currentIndex;


    public function __construct()
    {
        // initialize the internal data structures
        $this->newNamesToOldNames = array();
        $this->oldNamesToNewNames = array();
        $this->currentIndex = 1;
    }


    public function assignNewName($oldName)
    {
        // we give the new name "currentIndex"
        $newName = $this->currentIndex;
        $this->oldNamesToNewNames += [$oldName => $newName];
        // we store the old name so that we may convert back to old name if needed
        $this->newNamesToOldNames[$newName] = $oldName;
        // we increase this variable so that the value + 1 will be the next new name
        // to be given
        $this->currentIndex++;
        // we return the new name
        return $newName;
    }

    public function toNewName($oldName)
    {
        return $this->oldNamesToNewNames[$oldName];
    }

    public function toOldName($newName)
    {

        return $this->newNamesToOldNames[$newName];
    }
}
