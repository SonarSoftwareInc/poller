<?php

namespace Poller\Services;

use Poller\Exceptions\SnmpException;
use Poller\Models\SnmpResponse;

class SnmpClient
{
    private array $options;
    private array $optionsArray;
    public function __construct(array $options)
    {
        $this->options = $options;
        $this->buildSnmpOptions();
    }

    public function walk(string $oid):SnmpResponse
    {
        //bulkwalk if v2/v3, regular if v1
        if ((int)$this->options['version'] === 1) {
            $cmd = '/usr/bin/snmpwalk '
                . implode(' ', $this->optionsArray)
                . ' '
                . escapeshellarg($this->options['host'])
                . ' '
                . escapeshellarg($oid);
        } else {
            $cmd = '/usr/bin/snmpbulkwalk '
                . implode(' ', $this->optionsArray)
                . ' '
                . escapeshellarg($this->options['host'])
                . ' '
                . escapeshellarg($oid);
        }

        exec($cmd, $output, $returnVar);
        if ($returnVar === 0) {
            return new SnmpResponse($output);
        } else {
            throw new SnmpException(implode(', ', $output));
        }
    }

    public function get($oids):SnmpResponse
    {
        if (is_array($oids)) {
            $oidString = implode(' ', $oids);
        } else {
            $oidString = $oids;
        }

        $cmd = '/usr/bin/snmpget -Cf '
            . implode(' ', $this->optionsArray)
            . ' '
            . escapeshellarg($this->options['host'])
            . ' '
            . escapeshellarg($oidString);

        exec($cmd, $output, $returnVar);
        if ($returnVar === 0) {
            return new SnmpResponse($output);
        } else {
            throw new SnmpException(implode(', ', $output));
        }
    }

    private function buildSnmpOptions()
    {
        $options = [
            '-v' . $this->convertVersion(),
            '-r0',
            '-t2',
            '-Oe',
            '-On',
            '-OQ',
            '-Ot',
            '-OU',
            '-O0',
            '-LO 0-4',
        ];

        if ($this->options['version'] !== 3) {
            $options[] = '-c' . escapeshellarg($this->options['community']);
        } else {
            $options[] = '-l' . escapeshellarg($this->convertSecLevel());
            $options[] = '-n' . escapeshellarg($this->options['context_name']);
            $options[] = '-a' . escapeshellarg($this->options['auth_mech']);
            $options[] = '-A' . escapeshellarg($this->options['auth_pwd']);
            if ($options['engine_id']) {
                $options[] = '-E' . escapeshellarg($options['engine_id']);
            }
            $options[] = '-x' . escapeshellarg($this->options['priv_mech']);
            $options[] = '-X' . escapeshellarg($this->options['priv_pwd']);
            $options[] = '-u' . escapeshellarg($this->options['user']);

        }

        $this->optionsArray = $options;
    }

    private function convertSecLevel():string
    {
        switch ($this->options['sec_level']) {
            case 'NO_AUTH_NO_PRIV':
                return 'noAuthNoPriv';
            case 'AUTH_NO_PRIV':
                return 'authNoPriv';
            default:
                return 'authPriv';
        }

    }

    private function convertVersion():string
    {
        switch ($this->options['version']) {
            case 1:
                return 1;
            case 2:
                return '2c';
            case 3:
                return 3;
        }
    }
}

/**
 * TODO:
 * Inject timeout (read/connect)
 * Capture errors somehow (return code?)
 * Bulk get
 */
