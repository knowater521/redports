<?php

namespace Redports\Node\Poudriere;

/**
 * Provides information about a poudriere Jail.
 *
 * @author     Bernhard Froehlich <decke@bluelife.at>
 * @copyright  2015 Bernhard Froehlich
 * @license    BSD License (2 Clause)
 *
 * @link       https://freebsd.github.io/redports/
 */
class Jail
{
    protected $binpath = '/usr/local/bin/poudriere';

    protected $_jailname;
    protected $_version;
    protected $_arch;
    protected $_method;
    protected $_path;
    protected $_fs;
    protected $_updated;
    protected $_queue;

    public function __construct($jailname)
    {
        $this->_load($jailname);
    }

    protected function _load($jailname)
    {
        exec(sprintf('%s jail -i -j %s', $this->binpath, $jailname), $output, $retval);

        if ($retval != 0 || count($output) < 1) {
            return false;
        }

        foreach ($output as $line) {
            $parts = explode(':', $line, 2);
            $tmp = explode(' ', $parts[0], 2);

            $key = trim($tmp[0].' '.$tmp[1]);
            $value = trim($parts[1]);

            switch ($key) {
            case 'Jail name':
               $this->_jailname = $value;
            break;
            case 'Jail version':
               $this->_version = $value;
            break;
            case 'Jail arch':
               $this->_arch = $value;
            break;
            case 'Jail method':
               $this->_method = $value;
            break;
            case 'Jail mount':
               $this->_path = $value;
            break;
            case 'Jail fs':
               $this->_fs = $value;
            break;
            case 'Jail updated':
               $this->_updated = $value;
            break;
         }
        }

        unset($output);
        exec(sprintf('zfs get -o value redports:queue %s', $this->_fs), $output, $retval);

        if ($retval == 0 && count($output) == 2) {
            $value = trim($output[1]);

            if ($value != '-' && $value != 'none') {
                $this->_queue = $value;
            }
        }

        return true;
    }

    public function setQueue($queue)
    {
        if (!$this->_fs) {
            return false;
        }

        exec(sprintf('zfs set redports:queue=%s %s', $queue, $this->_fs));

        return true;
    }

    public function unsetQueue()
    {
        if (!$this->_fs) {
            return false;
        }

        exec(sprintf('zfs inherit -Sr redports:queue %s', $this->_fs));

        return true;
    }

    public function getJailname()
    {
        return $this->_jailname;
    }

    public function getVersion()
    {
        return $this->_version;
    }

    public function getArch()
    {
        return $this->_arch;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function getPath()
    {
        return $this->_path;
    }

    public function getFilesystem()
    {
        return $this->_fs;
    }

    public function getUpdated()
    {
        return $this->_updated;
    }

    public function getQueue()
    {
        return $this->_queue;
    }

    public function getPortstree()
    {
        return new Portstree($this->_jailname);
    }
}
