<?php

namespace Redports\Master;

/**
 * Class to manage existing and to create new machines.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2015 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://freebsd.github.io/redports/
 */
class Machines
{
    protected $_db;

    public function __construct()
    {
        $this->_db = Config::getDatabaseHandle();
    }

    public function createMachine($name)
    {
        if ($this->exists($name)) {
            return false;
        }

        $machine = new Machine($name);
        $machine->save();

        $this->addMachine($machine->getName());

        return $machine;
    }

    public function addMachine($name)
    {
        if ($this->_db->sAdd('machines', $name) != 1) {
            return false;
        }

        return true;
    }

    public function getMachine($name)
    {
        if (!$this->exists($name)) {
            return false;
        }

        return new Machine($name);
    }

    public function deleteMachine($name)
    {
        $this->_db->sRemove('machines', $name);
        $this->_db->delete('machines:'.$name);

        return true;
    }

    public function exists($name)
    {
        return $this->_db->sIsMember('machines', $name);
    }
}
