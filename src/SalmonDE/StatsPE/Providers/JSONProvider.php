<?php
namespace SalmonDE\StatsPE\Providers;

use pocketmine\utils\Config;
use SalmonDE\StatsPE\Base;

class JSONProvider implements DataProvider
{
    private $entries = [];
    private $dataConfig = null;

    public function __construct(string $path){
        $this->initialize(['path' => $path]);
    }

    public function initialize(array $data){
        $this->dataConfig = new Config($data['path'], Config::JSON);
    }

    public function getName() : string{
        return 'JSONProvider';
    }

    public function addPlayer(\pocketmine\Player $player){
        foreach($this->getEntries() as $entry){ // Run through all entries and save the default values
            $this->saveData($player->getName(), $entry, $entry->getDefault());
        }
    }

    public function getData(string $player, Entry $entry){
        if($this->entryExists($entry->getName())){
            if(!$entry->shouldSave()){
                return;
            }
            $v = $this->dataConfig->getNested(strtolower($player).'.'.$entry->getName());
            if($entry->isValidType($v)){
                return $v;
            }
            Base::getInstance()->getLogger()->error($msg = 'Unexpected datatype returned "'.gettype($v).'" for entry "'.$entry->getName().'" in "'.self::class.'" by "'.__FUNCTION__.'"!');
        }
    }

    public function getAllData(string $player = null){
        if($player !== null){
            return $this->dataConfig->get(strtolower($player), null);
        }
        return $this->dataConfig->getAll();
    }

    public function saveData(string $player, Entry $entry, $value){
        if($this->entryExists($entry->getName()) && $entry->shouldSave()){
            if($entry->isValidType($value)){
                $this->dataConfig->setNested(strtolower($player).'.'.$entry->getName(), $value);
            }else{
                Base::getInstance()->getLogger()->error($msg = 'Unexpected datatype "'.gettype($value).'" given for entry "'.$entry->getName().'" in "'.self::class.'" by "'.__FUNCTION__.'"!');
            }
        }
    }

    public function addEntry(Entry $entry){
        if(!$this->entryExists($entry->getName()) && $entry->isValid()){
            $this->entries[$entry->getName()] = $entry;
            return true;
        }
        return false;
    }

    public function removeEntry(Entry $entry){
        if($this->entryExists($entry->getName()) && $entry->getName() !== 'Username'){
            unset($this->entries[$entry->getName()]);
        }
    }

    public function getEntries() : array{
        return $this->entries;
    }

    public function getEntry(string $entry){
        if(isset($this->entries[$entry])){
            return $this->entries[$entry];
        }
    }

    public function entryExists(string $entry) : bool{
        return isset($this->entries[$entry]);
    }

    public function countDataRecords() : int{
        return count($this->getAllData());
    }

    public function saveAll(){
        $this->dataConfig->save();
    }
}